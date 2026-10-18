from rest_framework import serializers
from rest_framework.response import Response
from rest_framework.views import APIView

from .item_analysis import analyze
from .mastery import mastery_report


class ItemAnalysisInput(serializers.Serializer):
    keys = serializers.DictField(child=serializers.CharField(max_length=10), allow_empty=False)
    answers = serializers.DictField(child=serializers.DictField(child=serializers.CharField(allow_blank=True)))
    kktp_threshold = serializers.FloatField(min_value=0, max_value=100, default=75)


class ItemAnalysisView(APIView):
    """Stateless: Laravel mengirim kunci + lembar jawaban, Django mengembalikan analisis lengkap."""

    def post(self, request):
        payload = ItemAnalysisInput(data=request.data)
        payload.is_valid(raise_exception=True)
        data = payload.validated_data
        try:
            result = analyze(data["answers"], data["keys"])
        except ValueError as exc:
            raise serializers.ValidationError(str(exc)) from exc

        item_count = len(data["keys"])
        scores = {
            student: 100 * sum((answers.get(i) or "").strip().upper() == k.strip().upper() for i, k in data["keys"].items()) / item_count
            for student, answers in data["answers"].items()
        }
        return Response({
            **result.to_dict(),
            "mastery": mastery_report(scores, data["kktp_threshold"]),
        })
