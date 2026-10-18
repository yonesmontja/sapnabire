"""EduERP Smart Assessment service (Django).

Service ini TIDAK punya login sendiri: semua request datang dari Laravel (API Gateway)
membawa Service JWT berumur pendek. Lihat docs/04-integrasi-ai-soal.md.
"""

from pathlib import Path

import environ

BASE_DIR = Path(__file__).resolve().parent.parent
env = environ.Env()
environ.Env.read_env(BASE_DIR / ".env")

SECRET_KEY = env("DJANGO_SECRET_KEY", default="insecure-dev-key")
DEBUG = env.bool("DJANGO_DEBUG", default=False)
ALLOWED_HOSTS = env.list("DJANGO_ALLOWED_HOSTS", default=["localhost", "127.0.0.1"])

INSTALLED_APPS = [
    "django.contrib.contenttypes",
    "django.contrib.auth",
    "rest_framework",
    "core",
    "generator",
    "analysis",
]

MIDDLEWARE = [
    "django.middleware.security.SecurityMiddleware",
    "django.middleware.common.CommonMiddleware",
]

ROOT_URLCONF = "config.urls"
WSGI_APPLICATION = "config.wsgi.application"

DATABASES = {
    "default": env.db("DATABASE_URL", default="postgres://eduerp:eduerp@127.0.0.1:5432/eduerp_assessment"),
}
DEFAULT_AUTO_FIELD = "django.db.models.BigAutoField"

LANGUAGE_CODE = "id"
TIME_ZONE = "Asia/Jayapura"
USE_TZ = True

REST_FRAMEWORK = {
    "DEFAULT_AUTHENTICATION_CLASSES": ["core.authentication.ServiceJWTAuthentication"],
    "DEFAULT_PERMISSION_CLASSES": ["core.authentication.IsServiceRequest"],
    "DEFAULT_RENDERER_CLASSES": ["rest_framework.renderers.JSONRenderer"],
    "UNAUTHENTICATED_USER": None,
}

SERVICE_JWT = {
    "SECRET": env("SERVICE_JWT_SECRET", default="change-me-shared-secret-min-32-chars"),
    "ISSUER": env("SERVICE_JWT_ISSUER", default="eduerp-core"),
    "AUDIENCE": env("SERVICE_JWT_AUDIENCE", default="eduerp-assessment"),
    "LEEWAY_SECONDS": 30,
}
CORE_CALLBACK_URL = env("CORE_CALLBACK_URL", default="")

CELERY_BROKER_URL = env("CELERY_BROKER_URL", default="redis://127.0.0.1:6379/1")
CELERY_TASK_ACKS_LATE = True
CELERY_TASK_ALWAYS_EAGER = env.bool("CELERY_TASK_ALWAYS_EAGER", default=False)

AI = {
    "MODEL": env("AI_MODEL", default="claude-opus-5-5"),
    # Soal per panggilan model. Kecil = lebih tahan gagal & paralel; besar = lebih sedikit panggilan.
    "QUESTIONS_PER_CHUNK": env.int("AI_QUESTIONS_PER_CHUNK", default=10),
    "MAX_QUESTIONS_PER_JOB": env.int("AI_MAX_QUESTIONS_PER_JOB", default=500),
}

LOGGING = {
    "version": 1,
    "handlers": {"console": {"class": "logging.StreamHandler"}},
    "root": {"handlers": ["console"], "level": "INFO"},
}
