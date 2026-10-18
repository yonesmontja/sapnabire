import time
from unittest import mock

import jwt
from django.conf import settings
from django.test import TestCase, override_settings
from rest_framework.test import APIClient

from generator.models import GenerationJob, Question
from generator.schemas import GeneratedBatch, GeneratedItem, Option

SCHOOL = "01JABCDEFGHJKMNPQRSTVWXYZ0"


def service_token(scopes=("assessment:generate",), school_id=SCHOOL, **overrides) -> str:
    conf = settings.SERVICE_JWT
    now = int(time.time())
    claims = {
        "iss": conf["ISSUER"], "aud": conf["AUDIENCE"], "sub": "42", "school_id": school_id,
        "role": "teacher", "scopes": list(scopes), "iat": now, "exp": now + 300, **overrides,
    }
    return jwt.encode(claims, conf["SECRET"], algorithm="HS256")


def fake_batch(req, client=None) -> GeneratedBatch:
    return GeneratedBatch(items=[
        GeneratedItem(
            question_type="multiple_choice", cognitive_level=req.level.value, indicator="Menentukan hasil",
            stimulus="", stem=f"Soal {req.level} #{n}: $2+2$ = ?", answer_key="A", explanation="2+2=4",
            scoring_rubric="", image_prompt="",
            options=[Option(label=label, text=text, is_correct=label == "A", rationale="-")
                     for label, text in zip("ABCD", ["4", "3", "5", "22"])],
        )
        for n in range(req.count)
    ])


JOB_PAYLOAD = {
    "idempotency_key": "atp-1-run-1",
    "subject": "Matematika", "grade": "Kelas 4 (Fase B)",
    "learning_objective": "Peserta didik dapat menjumlahkan bilangan cacah.",
    "material": "Penjumlahan bilangan cacah sampai 1.000.",
    "question_type": "multiple_choice", "total": 25,
    "tier_distribution": {"LOTS": 40, "MOTS": 40, "HOTS": 20},
}


@override_settings(CELERY_TASK_ALWAYS_EAGER=True, CORE_CALLBACK_URL="")
class GeneratorApiTests(TestCase):
    def setUp(self):
        from config.celery import app
        app.conf.task_always_eager = True
        self.client = APIClient()
        self.client.credentials(HTTP_AUTHORIZATION=f"Bearer {service_token()}")

    def test_rejects_missing_or_invalid_token(self):
        self.assertEqual(APIClient().post("/v1/generator/jobs", JOB_PAYLOAD, format="json").status_code, 401)
        bad = APIClient()
        bad.credentials(HTTP_AUTHORIZATION=f"Bearer {service_token(aud='other')}")
        self.assertEqual(bad.post("/v1/generator/jobs", JOB_PAYLOAD, format="json").status_code, 401)

    def test_requires_generate_scope(self):
        self.client.credentials(HTTP_AUTHORIZATION=f"Bearer {service_token(scopes=())}")
        self.assertEqual(self.client.post("/v1/generator/jobs", JOB_PAYLOAD, format="json").status_code, 403)

    @mock.patch("generator.tasks.generate_items", side_effect=fake_batch)
    def test_job_generates_exact_distribution_and_is_idempotent(self, _):
        with self.captureOnCommitCallbacks(execute=True):
            response = self.client.post("/v1/generator/jobs", JOB_PAYLOAD, format="json")
        self.assertEqual(response.status_code, 202, response.content)
        self.assertEqual(response.json()["distribution"], {"C1": 5, "C2": 5, "C3": 10, "C4": 2, "C5": 2, "C6": 1})

        job = GenerationJob.objects.get()
        self.assertEqual(job.status, GenerationJob.Status.COMPLETED)
        self.assertEqual(Question.objects.filter(job=job).count(), 25)
        self.assertEqual(Question.objects.filter(job=job, cognitive_level="C3").count(), 10)

        again = self.client.post("/v1/generator/jobs", JOB_PAYLOAD, format="json")
        self.assertEqual(again.status_code, 200)
        self.assertEqual(GenerationJob.objects.count(), 1)

    def test_other_school_cannot_read_job(self):
        job = GenerationJob.objects.create(
            school_id=SCHOOL, requested_by="1", idempotency_key="k", subject="IPA", grade="5",
            learning_objective="-", material="-", question_type="essay", total_requested=1, distribution={"C1": 1},
        )
        other = APIClient()
        other.credentials(HTTP_AUTHORIZATION=f"Bearer {service_token(school_id='01JOTHERSCHOOL0000000000000'[:26])}")
        self.assertEqual(other.get(f"/v1/generator/jobs/{job.pk}").status_code, 404)
        self.assertEqual(self.client.get(f"/v1/generator/jobs/{job.pk}").status_code, 200)

    def test_item_analysis_endpoint(self):
        response = self.client.post("/v1/analysis/item-analysis", {
            "keys": {"Q1": "A", "Q2": "B"},
            "answers": {"s1": {"Q1": "A", "Q2": "B"}, "s2": {"Q1": "A", "Q2": "C"}, "s3": {"Q1": "B", "Q2": "C"}},
            "kktp_threshold": 75,
        }, format="json")
        self.assertEqual(response.status_code, 200, response.content)
        self.assertEqual(response.json()["mastery"]["remedial"], ["s3", "s2"])
