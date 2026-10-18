import logging

import anthropic
from celery import shared_task
from django.conf import settings
from django.db import transaction
from django.db.models import F
from django.utils import timezone

from core.callbacks import notify_core

from .llm import GenerationRefused, GenerationRequest, generate_items
from .models import GenerationJob, Question
from .planner import plan_chunks
from .taxonomy import CognitiveLevel

logger = logging.getLogger(__name__)

RETRYABLE = (anthropic.RateLimitError, anthropic.InternalServerError, anthropic.APIConnectionError)


def dispatch_job(job: GenerationJob, weights: dict[str, float]) -> None:
    chunks = plan_chunks(job.total_requested, weights, settings.AI["QUESTIONS_PER_CHUNK"])
    GenerationJob.objects.filter(pk=job.pk).update(chunks_total=len(chunks), status=GenerationJob.Status.RUNNING)
    for chunk in chunks:
        generate_chunk.delay(str(job.pk), chunk.level.value, chunk.count)


@shared_task(bind=True, autoretry_for=RETRYABLE, retry_backoff=True, retry_backoff_max=300, max_retries=5)
def generate_chunk(self, job_id: str, level: str, count: int) -> None:
    job = GenerationJob.objects.get(pk=job_id)
    existing = Question.objects.filter(job=job, cognitive_level=level).values_list("stem", flat=True)[:30]
    request = GenerationRequest(
        subject=job.subject,
        grade=job.grade,
        material=job.material,
        learning_objective=job.learning_objective,
        level=CognitiveLevel(level),
        count=count,
        question_type=job.question_type,
        options_count=job.options_count,
        avoid_stems=tuple(existing),
    )

    try:
        batch = generate_items(request)
    except RETRYABLE:
        if self.request.retries >= self.max_retries:
            _mark_chunk(job_id, failed=True, error=f"{level}: gagal setelah retry")
        raise
    except (GenerationRefused, ValueError, anthropic.APIStatusError) as exc:
        logger.warning("Chunk %s/%s gagal: %s", job_id, level, exc)
        _mark_chunk(job_id, failed=True, error=f"{level}: {exc}")
        return

    with transaction.atomic():
        Question.objects.bulk_create(
            Question(
                school_id=job.school_id,
                job=job,
                learning_objective_id=job.learning_objective_id,
                question_type=item.question_type,
                cognitive_level=level,  # level dari rencana, bukan klaim model
                indicator=item.indicator,
                stimulus=item.stimulus,
                stem=item.stem,
                options=[o.model_dump() for o in item.options],
                answer_key=item.answer_key,
                explanation=item.explanation,
                scoring_rubric=item.scoring_rubric,
                image_prompt=item.image_prompt,
            )
            for item in batch.items[:count]
        )
    _mark_chunk(job_id, failed=False)


def _mark_chunk(job_id: str, *, failed: bool, error: str = "") -> None:
    with transaction.atomic():
        job = GenerationJob.objects.select_for_update().get(pk=job_id)
        field = "chunks_failed" if failed else "chunks_done"
        GenerationJob.objects.filter(pk=job_id).update(**{field: F(field) + 1})
        job.refresh_from_db()
        if error:
            job.error = "\n".join(filter(None, [job.error, error]))
        if job.chunks_done + job.chunks_failed < job.chunks_total:
            job.save(update_fields=["error"])
            return
        if job.chunks_failed == 0:
            job.status = GenerationJob.Status.COMPLETED
        elif job.chunks_done == 0:
            job.status = GenerationJob.Status.FAILED
        else:
            job.status = GenerationJob.Status.PARTIAL
        job.finished_at = timezone.now()
        job.save(update_fields=["status", "error", "finished_at"])

    notify_core("generation.finished", {
        "job_id": str(job.pk),
        "school_id": job.school_id,
        "status": job.status,
        "questions": job.questions.count(),
    })
