# AGENTS.md — Aturan kerja untuk agen AI di repo EduERP

Berkas ini dibaca otomatis oleh agen coding (Claude Code lewat `CLAUDE.md`, Codex, Cursor, dan sejenisnya). Spesifikasi lengkap, invarian bisnis, dan kartu tugas ada di [`docs/07-spesifikasi-teknis-agen.md`](docs/07-spesifikasi-teknis-agen.md). **Baca dokumen itu sebelum mengerjakan tugas apa pun.**

## Peta repo

| Folder | Isi | Bahasa/stack |
|---|---|---|
| `apps/core-api` | Core ERP, auth, tenant, API Gateway `/api/v1` | Laravel 13, PHP 8.3, Sanctum, PostgreSQL |
| `apps/assessment` | AI soal, analisis butir, skor NLP jurnal (internal, tidak diekspos) | Django 5.2, DRF, Celery, Anthropic SDK |
| `apps/web` | UI + BFF | Next.js 16, React 19, Tailwind 4 (baca `apps/web/AGENTS.md`, aturan Next 16 berbeda) |
| `docs/` | Strategi, arsitektur, ERD, API, spesifikasi | Markdown + mermaid |

## Perintah verifikasi (wajib hijau sebelum commit)

```bash
# Laravel
cd apps/core-api && ./vendor/bin/pint --test && php artisan test
# Django
cd apps/assessment && python manage.py makemigrations --check --dry-run && python manage.py test
# Next.js
cd apps/web && npm run lint && npm run typecheck && npm run build
```
Infrastruktur lokal: `docker compose up -d` (PostgreSQL 16, Redis 7). Database tes: `eduerp_test` (Laravel), `test_eduerp_assessment` (Django).

## Aturan yang tidak boleh dilanggar

1. **Tenant:** setiap tabel operasional punya `school_id`. Query lewat scope `forSchool()`. ID milik sekolah lain dibalas `404`. Aturan `exists` di validasi selalu dibatasi `where('school_id', …)`.
2. **ID domain = ULID** (`HasUlids`, `foreignUlid`). Hanya `users` yang memakai bigint.
3. **Histori tidak dihapus.** Penugasan diakhiri dengan `ended_on`, master memakai `SoftDeletes`, `activity_logs` append-only.
4. **Audit di server.** Setiap aksi tulis memanggil `ActivityLog::record(...)`. Jangan mengandalkan log yang dikirim dari browser.
5. **Rahasia.** API key dan token tidak pernah disimpan polos di DB, dikirim dalam respons, ditulis ke log, atau di-commit. Key AI hanya ada di env Django.
6. **Jalur komunikasi:** Browser → Next.js → Laravel → Django. Next.js tidak memanggil Django. Token user hanya disimpan di cookie httpOnly.
7. **Data pribadi anak:** jangan pernah memakai nama atau NISN siswa asli di kode, fixture, tes, atau dokumen. Selalu pakai factory/faker.
8. **Invarian bisnis** `INV-*` di `docs/07` adalah kontrak. Mengubahnya butuh persetujuan manusia; jangan "memperbaiki" angka ambang atau rumus sendiri.
9. Hasil AI (soal, skor jurnal, catatan rapor) selalu berstatus **draf** sampai dikonfirmasi guru.

## Konvensi kode

- Ikuti pola modul Setup Sekolah & PIC yang sudah ada: Migration → Model (+ `BelongsToSchool`) → FormRequest (`app/Http/Requests/V1`) → Resource (`app/Http/Resources/V1`) → Controller (`app/Http/Controllers/Api/V1`) → route di `routes/api.php` → Feature test (`tests/Feature/<Modul>`).
- Constraint bisnis yang bisa dijaga database (unik parsial, `CHECK`) ditulis di migrasi, bukan hanya di PHP.
- Pesan error untuk pengguna dalam Bahasa Indonesia. Identifier kode dalam Bahasa Inggris.
- Next.js: data diambil di Server Component lewat `src/lib/api.ts`. Halaman yang memanggil `api()` harus berada di bawah `loading.tsx` atau `<Suspense>`.
- Satu tugas = satu branch/PR kecil. Jangan mengubah file di luar cakupan kartu tugas.

## Definition of Done

Semua perintah verifikasi hijau, ada tes untuk setiap kriteria penerimaan di kartu tugas, `docs/03-api-gateway.md` (status ✅) dan `docs/02-erd.md` diperbarui bila skema atau endpoint berubah, lalu laporan akhir mengikuti format di `docs/07` §8.
