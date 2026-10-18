# 03 — API Gateway (Laravel) v1

Base URL: `/api/v1`. Semua respons JSON. Autentikasi `Authorization: Bearer <token Sanctum>` kecuali disebut lain.

## Konvensi

| Aspek | Standar |
|---|---|
| Versi | Prefix URL (`/api/v1`). Breaking change → `/api/v2`, v1 dipertahankan ≥ 1 semester. |
| Tenant | Diambil dari `users.school_id` — **tidak pernah** dari parameter request. Akses ID milik sekolah lain → `404`. |
| Otorisasi | Middleware `role:<peran,...>`; `super_admin` selalu lolos. Kebijakan detail (guru hanya kelas yang diampu) via Laravel Policy. |
| Paginasi | `?page=&per_page=` (maks 100), format `data` + `links` + `meta` bawaan Laravel Resource. |
| Validasi | `422` dengan `{ message, errors: { field: [..] } }`. |
| Idempotensi | Header `Idempotency-Key` pada endpoint pembuat job. |
| Rate limit | Login 5/menit/IP; generator AI 10/menit/user; default 60/menit. |
| Audit | Setiap perubahan tulis → `activity_logs` (modul, event, subject, IP). |
| Bahasa | Pesan error Bahasa Indonesia (`APP_LOCALE=id`). |

## Status implementasi

✅ = sudah ada & teruji di Fase 1 · ⏳ = dirancang, dibangun di fase berikutnya.

### Auth
| Method | Endpoint | Peran | Status |
|---|---|---|---|
| POST | `/auth/login` — `{login, password, device_name}` (login = username **atau** email) | publik | ✅ |
| GET | `/auth/me` | semua | ✅ |
| POST | `/auth/logout` | semua | ✅ |
| POST | `/auth/password` — ganti password | semua | ⏳ |
| POST | `/auth/forgot-password`, `/auth/reset-password` | publik | ⏳ |

### SMART Control — Setup & Struktur
| Method | Endpoint | Peran tulis | Status |
|---|---|---|---|
| GET / PATCH | `/control/school` | school_admin, principal | ✅ |
| GET / POST | `/control/academic-years` (POST membuat 2 semester sekaligus) | school_admin, principal | ✅ |
| POST | `/control/academic-years/{id}/activate` | school_admin, principal | ✅ |
| CRUD | `/control/employees` (`?q=&is_teacher=&is_active=`) | school_admin, principal | ✅ |
| CRUD | `/control/departments` (default pohon + PIC aktif; `?flat=1` untuk dropdown) | school_admin, principal | ✅ |
| GET / POST | `/control/departments/{id}/pics` (`?academic_year_id=&include_ended=1`) | school_admin, principal | ✅ |
| POST | `/control/departments/{id}/pics/{pic}/end` — akhiri penugasan (histori tetap) | school_admin, principal | ✅ |
| GET | `/control/raci-matrix?academic_year_id=` — matriks bidang × pegawai | semua | ⏳ |

### SMART Control — Program, Pelaksanaan, Kendala, Laporan
| Method | Endpoint | Status |
|---|---|---|
| CRUD | `/control/programs`, `/control/programs/{id}/kpis`, `/control/kpis/{id}/targets` | ⏳ |
| CRUD | `/control/programs/{id}/milestones`, `/control/milestones/{id}/tasks` | ⏳ |
| POST | `/control/tasks/{id}/realizations`, `/control/realizations/{id}/evidences` (multipart) | ⏳ |
| PATCH | `/control/evidences/{id}/verify` | ⏳ |
| CRUD | `/control/issues`, `POST /control/issues/{id}/escalate` | ⏳ |
| CRUD | `/control/meetings`, `/control/meetings/{id}/decisions`, `/control/decisions/{id}/follow-ups` | ⏳ |
| GET | `/control/dashboard` — program aktif, % selesai, terlambat, evidence kurang, issue kritis, rata-rata KPI | ⏳ |
| POST | `/control/documents` — `{type: agenda|notulen|raci|rag_flowchart|slides_15|executive_summary, ...}` → job ekspor | ⏳ |

### LMS & Administrasi Guru
| Method | Endpoint | Status |
|---|---|---|
| CRUD | `/curriculum/subjects`, `/curriculum/outcomes` (CP), `/curriculum/objectives` (TP) | ⏳ |
| CRUD | `/curriculum/learning-paths` (ATP) + `PUT /curriculum/learning-paths/{id}/items` (urutan & JP) | ⏳ |
| POST | `/planning/semester-sync` — sinkron ATP → Prota + data kelas | ⏳ |
| GET | `/planning/annual-plans/{assignment}` (Prota), `/planning/semester-plans/{assignment}` (Promes), `/planning/syllabus/{assignment}` | ⏳ |
| CRUD | `/planning/kktp/{objective}` (4 interval), `/planning/lesson-plans` (Modul Ajar), `/planning/worksheets` (LKPD) | ⏳ |
| GET/POST | `/classroom/sessions`, `/classroom/sessions/{id}/attendance`, `/classroom/sessions/{id}/journal` | ⏳ |
| POST | `/sync/offline` — batch nilai & presensi dari IndexedDB (upsert by ULID) | ⏳ |

### Smart Assessment (gateway ke Django)
| Method | Endpoint | Diteruskan ke Django | Status |
|---|---|---|---|
| POST | `/assessment/generator/jobs` | `POST /v1/generator/jobs` | ✅ |
| GET | `/assessment/generator/jobs/{uuid}` | `GET /v1/generator/jobs/{uuid}` | ✅ |
| GET | `/assessment/generator/jobs/{uuid}/questions` | `GET /v1/generator/jobs/{uuid}/questions` | ✅ |
| PATCH | `/assessment/questions/{uuid}` — telaah guru (draft → reviewed) | `PATCH /v1/questions/{uuid}` | ⏳ |
| POST | `/assessment/{assessment}/analysis` — Laravel kirim kunci + jawaban | `POST /v1/analysis/item-analysis` (sudah ada di Django ✅) | ⏳ |
| POST | `/assessment/exports` — kisi-kisi, kartu soal, naskah (PDF/DOCX/XLSX) | `POST /v1/exports` | ⏳ |
| POST | `/assessment/tryouts` — paket TKA | `POST /v1/tryouts` | ⏳ |
| POST | `/internal/assessment/callbacks` — webhook dari Django, **HMAC**, bukan token user | — | ✅ |

### Penilaian & Rapor
| Method | Endpoint | Status |
|---|---|---|
| GET/PUT | `/grades/{assignment}/objectives` — daftar nilai per TP (bulk upsert) | ⏳ |
| GET | `/grades/{assignment}/final` — nilai akhir otomatis | ⏳ |
| POST | `/report-cards/generate` — deskripsi rapor otomatis (job) | ⏳ |
| GET | `/report-cards/{student}/{semester}` (+ `?format=pdf`) | ⏳ |

### Logistik, Keuangan & HR
| Method | Endpoint | Status |
|---|---|---|
| CRUD | `/inventory/rooms`, `/inventory/assets`, `/inventory/assets/{id}/movements` | ⏳ |
| CRUD | `/finance/purchase-requests` + `POST .../{id}/submit|approve|reject` | ⏳ |
| GET | `/finance/budget-realization?academic_year_id=` | ⏳ |
| CRUD | `/hr/monthly-evaluations` (EB), `POST /hr/performance-reviews/compile` (6 EB → EK) | ⏳ |

## Contoh

```http
POST /api/v1/auth/login
{"login": "admin", "password": "••••••", "device_name": "web"}

200 {"token": "1|abc…", "expires_at": "2026-10-18T16:00:00Z",
     "user": {"id": 1, "role": "school_admin", "school_id": "01K…"}}
```

```http
POST /api/v1/control/departments/01K…/pics
{"employee_id": "01K…", "academic_year_id": "01K…", "role": "lead", "started_on": "2026-07-13"}

201 {"data": {"id": "01K…", "role": "lead", "raci": "A", "ended_on": null, "employee": {…}}}
422 {"message": "Bidang ini sudah memiliki PIC utama aktif. …", "errors": {"role": ["…"]}}
```
