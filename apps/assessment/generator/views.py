from django.db import IntegrityError, transaction
from rest_framework import generics, status
from rest_framework.response import Response
from rest_framework.views import APIView

from core.authentication import HasScope, IsServiceRequest

from .models import GenerationJob, Question
from .serializers import GenerationJobCreateSerializer, GenerationJobSerializer, QuestionSerializer
from .tasks import dispatch_job


class GenerationJobCreateView(APIView):
    permission_classes = [IsServiceRequest, HasScope]
    required_scope = "assessment:generate"

    def post(self, request):
        serializer = GenerationJobCreateSerializer(data=request.data)
        serializer.is_valid(raise_exception=True)
        data = dict(serializer.validated_data)
        weights = data.pop("weights")
        total = data.pop("total")
        data.pop("level_distribution", None)
        data.pop("tier_distribution", None)

        existing = GenerationJob.objects.filter(school_id=request.user.school_id, idempotency_key=data["idempotency_key"]).first()
        if existing:
            return Response(GenerationJobSerializer(existing).data, status=status.HTTP_200_OK)

        try:
            with transaction.atomic():
                job = GenerationJob.objects.create(
                    school_id=request.user.school_id,
                    requested_by=request.user.user_id,
                    total_requested=total,
                    **data,
                )
        except IntegrityError:
            job = GenerationJob.objects.get(school_id=request.user.school_id, idempotency_key=data["idempotency_key"])
            return Response(GenerationJobSerializer(job).data, status=status.HTTP_200_OK)

        transaction.on_commit(lambda: dispatch_job(job, weights))
        return Response(GenerationJobSerializer(job).data, status=status.HTTP_202_ACCEPTED)


class GenerationJobDetailView(generics.RetrieveAPIView):
    serializer_class = GenerationJobSerializer

    def get_queryset(self):
        return GenerationJob.objects.filter(school_id=self.request.user.school_id)


class JobQuestionListView(generics.ListAPIView):
    serializer_class = QuestionSerializer

    def get_queryset(self):
        return Question.objects.filter(
            school_id=self.request.user.school_id, job_id=self.kwargs["pk"]
        ).order_by("cognitive_level", "created_at")
