from django.urls import path

from . import views

urlpatterns = [
    path("item-analysis", views.ItemAnalysisView.as_view()),
]
