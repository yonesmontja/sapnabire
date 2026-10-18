# EduERP Smart Assessment (Django 5.2)

Service internal (tidak diekspos ke internet) untuk **AI Soal Generator** dan **Analisis Butir Soal**. Hanya menerima request dari Laravel dengan Service JWT. Rancangan: [`docs/04-integrasi-ai-soal.md`](../../docs/04-integrasi-ai-soal.md).

```
config/        settings (env), urls, celery
core/          ServiceJWTAuthentication, HasScope, callback HMAC ke Laravel
generator/
├── taxonomy.py    C1–C6 ↔ LOTS/MOTS/HOTS
├── planner.py     distribusi pasti (largest remainder) + pemecahan per 10 soal
├── schemas.py     kontrak structured output (Pydantic)
├── llm.py         Claude: streaming + structured output + fallback penolakan
├── tasks.py       Celery: generate_chunk, status job, callback
└── models.py      GenerationJob, Question (bank soal: draft → published → retired)
analysis/
├── item_analysis.py   p, D (27%), point-biserial terkoreksi, pengecoh, KR-20, SEM, rekomendasi
└── mastery.py         4 interval KKTP, daftar remedial & pengayaan
tests/         planner, analisis butir, API + JWT + tenant isolation (Claude di-mock)
```

```bash
python manage.py migrate
python manage.py runserver 127.0.0.1:8001
celery -A config worker -l info
python manage.py test
```
