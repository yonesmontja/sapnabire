from django.conf import settings
from rest_framework import serializers

from .models import GenerationJob, Question
from .planner import allocate, expand_tiers
from .taxonomy import CognitiveLevel, Tier


class GenerationJobCreateSerializer(serializers.Serializer):
    idempotency_key = serializers.CharField(max_length=100)
    learning_objective_id = serializers.CharField(max_length=26, required=False, allow_blank=True)
    subject = serializers.CharField(max_length=100)
    grade = serializers.CharField(max_length=50)
    learning_objective = serializers.CharField()
    material = serializers.CharField(max_length=60000)
    question_type = serializers.ChoiceField(
        ["multiple_choice", "complex_multiple_choice", "true_false", "short_answer", "essay"]
    )
    options_count = serializers.IntegerField(min_value=3, max_value=5, default=4)
    total = serializers.IntegerField(min_value=1)
    # Salah satu wajib: komposisi per level C atau per tier LOTS/MOTS/HOTS (persentase / bobot).
    level_distribution = serializers.DictField(child=serializers.FloatField(min_value=0), required=False)
    tier_distribution = serializers.DictField(child=serializers.FloatField(min_value=0), required=False)

    def validate_total(self, value):
        limit = settings.AI["MAX_QUESTIONS_PER_JOB"]
        if value > limit:
            raise serializers.ValidationError(f"Maksimal {limit} soal per job.")
        return value

    def validate(self, attrs):
        levels, tiers = attrs.get("level_distribution"), attrs.get("tier_distribution")
        if bool(levels) == bool(tiers):
            raise serializers.ValidationError("Isi tepat satu: level_distribution (C1–C6) atau tier_distribution (LOTS/MOTS/HOTS).")
        if levels and not set(levels) <= {c.value for c in CognitiveLevel}:
            raise serializers.ValidationError({"level_distribution": "Kunci harus C1–C6."})
        if tiers and not set(tiers) <= {t.value for t in Tier}:
            raise serializers.ValidationError({"tier_distribution": "Kunci harus LOTS, MOTS, atau HOTS."})

        weights = levels or expand_tiers(tiers)
        try:
            attrs["distribution"] = allocate(attrs["total"], weights)
        except ValueError as exc:
            raise serializers.ValidationError(str(exc)) from exc
        attrs["weights"] = weights
        return attrs


class QuestionSerializer(serializers.ModelSerializer):
    class Meta:
        model = Question
        exclude = ["school_id"]


class GenerationJobSerializer(serializers.ModelSerializer):
    progress = serializers.SerializerMethodField()

    class Meta:
        model = GenerationJob
        exclude = ["school_id", "material"]

    def get_progress(self, job):
        return round(100 * (job.chunks_done + job.chunks_failed) / job.chunks_total) if job.chunks_total else 0
