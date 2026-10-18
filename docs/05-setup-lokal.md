# 05 — Menjalankan EduERP di Mesin Lokal

Tiga aplikasi + infrastruktur:

| Komponen | Folder | Port |
|---|---|---|
| PostgreSQL 16, Redis 7, Mailpit | `docker-compose.yml` | 5432, 6379, 8025 |
| Laravel (Core API) | `apps/core-api` | 8000 |
| Django (Smart Assessment) + Celery | `apps/assessment` | 8001 |
| Next.js (Web) | `apps/web` | 3000 |

## 0. Prasyarat

| Tool | Versi minimal | Cek |
|---|---|---|
| PHP | 8.3 + ekstensi `pdo_pgsql`, `mbstring`, `intl`, `bcmath` | `php -v && php -m \| grep pdo_pgsql` |
| Composer | 2.x | `composer -V` |
| Python | 3.12 | `python3 --version` |
| Node.js | 20.9 (disarankan 22 LTS) | `node -v` |
| Docker Desktop / Docker Engine | 24+ | `docker compose version` |

> **Windows:** paling mulus memakai **WSL2 (Ubuntu)** untuk ketiga aplikasi. Alternatif: Laragon (PHP + Composer) + Python & Node versi Windows; ganti `source .venv/bin/activate` dengan `.venv\Scripts\activate`.
>
> **Tanpa Docker:** pasang PostgreSQL 16 dan Redis secara native, lalu jalankan isi `infra/postgres/init.sql` dengan `psql -U postgres` (buat juga user `eduerp` password `eduerp`).

## 1. Infrastruktur

```bash
git clone https://github.com/yonesmontja/sapnabire.git eduerp && cd eduerp
docker compose up -d
docker compose ps          # postgres harus "healthy"
```
Database yang dibuat otomatis: `eduerp`, `eduerp_assessment`, `eduerp_test`.

## 2. Secret bersama Laravel ↔ Django

Buat satu secret acak (≥ 32 karakter) dan pakai di **kedua** `.env`:
```bash
openssl rand -base64 48
```

## 3. Laravel — Core API (terminal 1)

```bash
cd apps/core-api
composer install
cp .env.example .env
php artisan key:generate
# edit .env → SERVICE_JWT_SECRET=<secret dari langkah 2>
php artisan migrate --seed
```
Seeder membuat sekolah contoh, tahun ajaran 2026/2027 (2 semester), 6 bidang standar (KUR, KES, SAR, HUM, KEU, SDM) dan akun **admin**. Password admin dicetak **sekali** di terminal — catat lalu ganti.

```bash
php artisan serve --port=8000
```
Uji: `curl http://127.0.0.1:8000/up` → status 200.

Tes otomatis (memakai database `eduerp_test`):
```bash
php artisan test
```

## 4. Django — Smart Assessment (terminal 2 & 3)

```bash
cd apps/assessment
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
cp .env.example .env
# edit .env → SERVICE_JWT_SECRET=<secret yang sama>, ANTHROPIC_API_KEY=<key Anda>
python manage.py migrate
python manage.py runserver 127.0.0.1:8001
```
Terminal 3 — worker antrean generator soal:
```bash
cd apps/assessment && source .venv/bin/activate
celery -A config worker -l info --concurrency 4
```
> Tanpa Redis/Celery (sekadar mencoba): set `CELERY_TASK_ALWAYS_EAGER=true` di `.env` — job dikerjakan langsung di proses web (request akan lama).

Tes otomatis (Claude API di-mock, tidak memakai kredit):
```bash
python manage.py test
```

## 5. Next.js — Web (terminal 4)

```bash
cd apps/web
npm install
cp .env.example .env.local      # CORE_API_URL=http://127.0.0.1:8000/api/v1
npm run dev
```
Buka <http://localhost:3000> → otomatis diarahkan ke `/login` → masuk dengan `admin` → menu **SMART Control › Setup Sekolah & PIC**.

![Halaman Setup Sekolah & PIC](assets/setup-sekolah-pic.png)

Cek kualitas: `npm run lint && npm run typecheck && npm run build`.

## 6. Uji integrasi AI Soal Generator (opsional, memakai kredit API)

Dengan keempat proses berjalan, login lalu panggil gateway (contoh memakai token dari `/auth/login`):

```bash
TOKEN=$(curl -s -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"login":"admin","password":"<password admin>"}' | python3 -c 'import sys,json;print(json.load(sys.stdin)["token"])')

curl -s -X POST http://127.0.0.1:8000/api/v1/assessment/generator/jobs \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -H 'Idempotency-Key: coba-1' \
  -d '{"subject":"IPA","grade":"Kelas 5","learning_objective":"Menjelaskan siklus air",
       "material":"Siklus air: evaporasi, kondensasi, presipitasi, infiltrasi.",
       "question_type":"multiple_choice","total":10,"tier_distribution":{"LOTS":30,"MOTS":40,"HOTS":30}}'
```
Pantau `GET /api/v1/assessment/generator/jobs/<id>` sampai `status` = `completed`, lalu ambil soalnya di `…/questions`.

## 7. Masalah umum

| Gejala | Penyebab & solusi |
|---|---|
| `could not find driver` (Laravel) | Ekstensi `pdo_pgsql` belum aktif → `sudo apt install php8.3-pgsql` / aktifkan di `php.ini`. |
| `SQLSTATE[08006] connection refused` | PostgreSQL belum jalan → `docker compose up -d postgres`. |
| Django `401 Service token tidak valid` | `SERVICE_JWT_SECRET` di dua `.env` berbeda, atau jam sistem selisih > 30 detik. |
| Job generator diam di `queued` | Worker Celery belum dijalankan atau Redis mati. |
| Next.js selalu kembali ke `/login` | Laravel tidak bisa dihubungi dari server Next.js → cek `CORE_API_URL`. |
| Next.js build error "uncached data accessed outside of `<Suspense>`" | Halaman baru yang memanggil `api()` harus berada di bawah `loading.tsx` atau dibungkus `<Suspense>` (aturan Next.js 16). |
