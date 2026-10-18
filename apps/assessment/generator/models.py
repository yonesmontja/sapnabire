"""Penyimpanan job generator & bank soal hasil AI.

ID Laravel (sekolah, TP/ATP, mapel, user) disimpan sebagai referensi string ULID —
Django tidak memiliki tabel master; sumber kebenaran tetap di core (Laravel).
"""

import uuid

from django.db import models


class GenerationJob(models.Model):
    class Status(models.TextChoices):
        QUEUED = "queued"
        RUNNING = "running"
        COMPLETED = "completed"
        PARTIAL = "partial"  # sebagian potongan gagal, soal yang berhasil tetap tersimpan
        FAILED = "failed"

    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    school_id = models.CharField(max_length=26, db_index=True)
    requested_by = models.CharField(max_length=26)
    idempotency_key = models.CharField(max_length=100)
    learning_objective_id = models.CharField(max_length=26, blank=True, help_text="ID TP/ATP di core")
    subject = models.CharField(max_length=100)
    grade = models.CharField(max_length=50)
    learning_objective = models.TextField()
    material = models.TextField()
    question_type = models.CharField(max_length=30)
    options_count = models.PositiveSmallIntegerField(default=4)
    total_requested = models.PositiveIntegerField()
    distribution = models.JSONField(help_text='Jumlah final per level, mis. {"C1": 20, "C4": 10}')
    status = models.CharField(max_length=20, choices=Status.choices, default=Status.QUEUED)
    chunks_total = models.PositiveIntegerField(default=0)
    chunks_done = models.PositiveIntegerField(default=0)
    chunks_failed = models.PositiveIntegerField(default=0)
    error = models.TextField(blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    finished_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        constraints = [
            models.UniqueConstraint(fields=["school_id", "idempotency_key"], name="generation_job_idempotent"),
        ]
        indexes = [models.Index(fields=["school_id", "-created_at"])]


class Question(models.Model):
    class Source(models.TextChoices):
        AI = "ai"
        MANUAL = "manual"

    class Status(models.TextChoices):
        DRAFT = "draft"        # hasil AI, wajib ditelaah guru
        REVIEWED = "reviewed"
        PUBLISHED = "published"
        RETIRED = "retired"    # dinonaktifkan setelah analisis butir

    id = models.UUIDField(primary_key=True, default=uuid.uuid4, editable=False)
    school_id = models.CharField(max_length=26, db_index=True)
    job = models.ForeignKey(GenerationJob, null=True, blank=True, on_delete=models.SET_NULL, related_name="questions")
    learning_objective_id = models.CharField(max_length=26, blank=True, db_index=True)
    source = models.CharField(max_length=10, choices=Source.choices, default=Source.AI)
    status = models.CharField(max_length=20, choices=Status.choices, default=Status.DRAFT)
    question_type = models.CharField(max_length=30)
    cognitive_level = models.CharField(max_length=2, db_index=True)
    indicator = models.TextField()
    stimulus = models.TextField(blank=True)
    stem = models.TextField()
    options = models.JSONField(default=list)
    answer_key = models.TextField()
    explanation = models.TextField()
    scoring_rubric = models.TextField(blank=True)
    image_prompt = models.TextField(blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
