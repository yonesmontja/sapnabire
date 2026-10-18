from django.http import JsonResponse
from django.urls import include, path

urlpatterns = [
    path("health", lambda request: JsonResponse({"status": "ok"})),
    path("v1/generator/", include("generator.urls")),
    path("v1/analysis/", include("analysis.urls")),
]
