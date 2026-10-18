from django.urls import path

from . import views

urlpatterns = [
    path("jobs", views.GenerationJobCreateView.as_view()),
    path("jobs/<uuid:pk>", views.GenerationJobDetailView.as_view()),
    path("jobs/<uuid:pk>/questions", views.JobQuestionListView.as_view()),
]
