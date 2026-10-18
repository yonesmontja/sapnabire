# 00 — Strategi Migrasi EduERP (kerangka Strategic Management)

## 1. RIM — Realitas → Imajinasi → Makna

**Realitas** (dari repo `yonesmontja/sap`, EduRapor v5.6 berbasis Google Apps Script):
- ±14.000 baris: `Code.gs` 2.247 baris + 15 halaman HTML; 30+ sheet sebagai "tabel" (`MasterSiswa`, `TransNilai`, `TransJurnal`, `RekapRaporAkhir`, P5, STNKM, jadwal, log aktivitas).
- Relasi dijaga manual oleh kode (tidak ada foreign key), query = baca seluruh sheet lalu filter di memori.
- Password di sheet `Users` tersimpan **plaintext**; API key AI (Gemini, Qwen, DeepSeek, Claude, …) tersimpan di sheet `PengaturanSekolah`.
- Batas Apps Script (waktu eksekusi 6 menit, kuota harian, 1 spreadsheet) membatasi jumlah siswa, analisis, dan pembuatan soal massal.
- Control Center masih berupa 15 sheet terpisah yang disambung rumus.
- Fitur yang sudah matang dan dipakai guru setiap hari: penilaian **STNKM double-layer**, jurnal anekdotal dengan AI + heuristik offline, laporan tren STNKM mingguan, P5 otomatis, Early Warning System, notifikasi WhatsApp, import Excel, audit trail (detail: `06-spesifikasi-edurapor.md`).
- Ditemukan bug pembaruan nilai yang menimpa baris siswa lain, serta dua cabang kode (Drive vs GitHub) dengan fitur dan perbaikan keamanan yang berbeda.

**Imajinasi**: satu platform sekolah di mana guru cukup menyusun ATP sekali, lalu Prota, Promes, silabus, KKTP, modul ajar, jurnal, soal, analisis butir, dan rapor mengalir otomatis; pimpinan melihat kesehatan seluruh program sekolah dalam satu layar; data aman dan bisa dipakai banyak sekolah dalam satu yayasan.

**Makna**: waktu guru kembali ke mengajar (bukan merangkai tabel Word), keputusan pimpinan berbasis data dan bukti (evidence), dan setiap siswa yang belum tuntas teridentifikasi lebih awal untuk remedial.

## 2. F3 — Form Follows Function

| Fungsi | Bentuk (teknologi) |
|---|---|
| Transaksi, aturan bisnis, keamanan, multi-sekolah | Laravel + PostgreSQL (relasional, constraint di database) |
| Pengalaman guru/pimpinan yang cepat & bisa offline | Next.js (Server Components, PWA, IndexedDB) |
| Statistik butir soal & AI | Django + Python (ekosistem data science, Celery) |
| Proses lama (generate ratusan soal, ekspor) | Antrean asinkron, bukan request langsung |

## 3. Gap Analysis

| Aspek | Saat ini | Target | Gap |
|---|---|---|---|
| Integritas data | Relasi manual di sheet | FK + constraint + transaksi | Skema relasional (✅ dimulai) |
| Keamanan | Password plaintext, API key di sheet | Hash bcrypt, token kedaluwarsa, secret di server | Auth baru (✅), migrasi akun dengan reset password |
| Skala | 1 sekolah, batas Apps Script | Multi-sekolah, ribuan siswa | Tenant `school_id` (✅), antrean |
| Administrasi guru | Input berulang per dokumen | ATP sebagai sumber tunggal | Modul Kurikulum & Planning |
| Asesmen | Tidak ada bank soal & analisis butir | Generator AI + analisis CTT + remedial | Django service (✅ inti) |
| Control Center | 15 sheet | Alur relasional + dashboard | Modul Program/KPI/Evidence/Issue |

## 4. Problem Statement

Sekolah menjalankan administrasi akademik dan program kerja di atas spreadsheet yang tidak aman, tidak berelasi, dan tidak bisa diskalakan; akibatnya guru menghabiskan waktu pada pekerjaan administratif berulang, pimpinan tidak punya gambaran real-time atas program dan ketuntasan belajar, dan data sensitif (password, API key) berisiko bocor.

## 5. WWW — What We Want

1. Guru menyusun ATP **sekali**; Prota/Promes/silabus/KKTP terbentuk otomatis.
2. Guru membuat **100 soal berkomposisi C1–C6 dalam < 10 menit**, lengkap kunci, pembahasan, kisi-kisi, kartu soal siap cetak.
3. Setelah ujian, analisis butir + daftar remedial tersedia **dalam hitungan detik**.
4. Kepala sekolah membuka **satu dashboard** untuk program aktif, keterlambatan, evidence, issue kritis, capaian KPI.
5. Tidak ada password/API key yang tersimpan polos; setiap perubahan tercatat (audit).

## 6. Opsi Solusi & Analisis Biaya

| Opsi | Kelebihan | Kekurangan | Biaya relatif |
|---|---|---|---|
| A. Perbaiki Apps Script | Murah, familiar | Batas platform tetap, keamanan lemah | Rendah, tapi utang teknis naik |
| B. Beli SaaS sekolah | Cepat jalan | Tidak sesuai alur SAP (STNKM, P5, Control Center), data di vendor | Langganan per siswa/tahun |
| C. Monolit Laravel saja | Satu bahasa, sederhana | AI & statistik di PHP kurang matang | Sedang |
| **D. Laravel + Next.js + Django (dipilih)** | Tiap fungsi dengan alat terbaiknya; AI & data science terisolasi dan bisa diskalakan sendiri | Tiga stack untuk dipelihara | Sedang–tinggi di awal, rendah per sekolah tambahan |

Mitigasi kekurangan opsi D: kontrak antar-service sempit (hanya 2 jalur: JWT keluar, webhook masuk), tes otomatis di tiap service, dan satu monorepo.

## 7. Rekomendasi

**Konsep**: monorepo `sapnabire` dengan tiga aplikasi (`apps/core-api`, `apps/web`, `apps/assessment`) di atas satu cluster PostgreSQL; Laravel sebagai gateway tunggal.

**Hasil yang ditargetkan (Fase 1–4)**: lihat WWW di atas, plus 100% data lama termigrasi dan terverifikasi sebelum Apps Script dinonaktifkan.

**Sumber daya**: 1 full-stack lead (Laravel/Next.js), 1 Python/AI engineer (paruh waktu), 1 operator sekolah sebagai product owner, 2–3 guru penguji (champion).

**SWOT**

| | Positif | Negatif |
|---|---|---|
| **Internal** | S: Domain sudah dipahami dari sistem lama; alur STNKM & P5 sudah teruji | W: Tim kecil, tiga stack |
| **Eksternal** | O: Kurikulum Merdeka menuntut administrasi berbasis TP — fitur otomatisasi bernilai tinggi; dapat dipakai sekolah lain di yayasan | T: Konektivitas internet di Papua tidak stabil (→ PWA offline); biaya API AI; perubahan regulasi kurikulum |

**Perkiraan anggaran operasional** (perlu diukur ulang setelah uji coba):
- Server: 1 VPS 4 vCPU / 8 GB (Laravel + Next.js + Django + Postgres + Redis) cukup untuk 1–3 sekolah.
- AI: ±10 soal per panggilan model; biaya token per 100 soal dicatat dari `usage` di log worker selama pilot, lalu dijadikan dasar kuota per guru.
- Backup: snapshot harian Postgres + object storage.

**Roadmap**

| Fase | Isi | Status |
|---|---|---|
| **1. Fondasi** | Arsitektur, auth, tenant, Setup Sekolah, Tahun Ajaran, Pegawai, Master Bidang & PIC, gateway → Django, generator soal (backend), analisis butir (backend) | ✅ repo ini |
| 2. Control Center | Program, KPI & target, milestone (RAG otomatis), delegasi RACI, realisasi, evidence, issue/eskalasi, rapat-keputusan-tindak lanjut, Dashboard Pimpinan, generator dokumen | ⏳ |
| 3. Kurikulum & LMS | Siswa + orang tua (kontak WA, opt-in), kelas, mapel, CP/TP/ATP, kalender akademik & minggu efektif, jadwal berulang, Prota/Promes/Silabus otomatis, KKTP, Modul Ajar + LKPD, presensi H/S/I/A, **jurnal anekdotal STNKM** (AI + kamus heuristik), notifikasi WhatsApp, PWA offline | ⏳ |
| 4. Asesmen & Rapor | Nilai per TP dengan **STNKM double-layer** (auto/manual), import Excel, laporan tren STNKM mingguan, dashboard akademik + **Early Warning System**, P5/kokurikuler, rapor format 2025 + catatan wali kelas AI, UI generator + telaah soal, ekspor kisi-kisi/kartu soal/naskah, Tryout TKA | ⏳ |
| 5. ERP | Inventaris, keuangan & PR (alur persetujuan), EB → EK | ⏳ |
| 6. Migrasi & cut-over | ETL sheet → Postgres (normalisasi tanggal & kode presensi, pemetaan `id_guru`, deteksi `id_nilai` ganda), pindah lampiran Drive → storage privat, verifikasi paralel 1 bulan, reset password massal, cabut API key/token lama, matikan Apps Script | ⏳ |

**TDU — Tes dan Ukur**

| Ukuran | Cara ukur | Target |
|---|---|---|
| Kebenaran kode | `php artisan test`, `python manage.py test`, `npm run build` di CI setiap push | 100% hijau |
| Waktu guru menyusun administrasi semester | Survei sebelum/sesudah pada guru champion | turun ≥ 50% |
| Waktu generate 100 soal | `finished_at − created_at` di `generation_jobs` | < 10 menit |
| Kualitas soal AI | % soal `draft → published` tanpa revisi besar; % butir `terima` setelah analisis | ≥ 70% |
| Adopsi Control Center | % program dengan evidence lengkap tiap bulan | ≥ 80% |

## 8. Success Plan — 4W1H (Fase 2 berikutnya)

| | |
|---|---|
| **What** | Membangun modul Program–KPI–Milestone–Evidence + Dashboard Pimpinan di atas fondasi Fase 1. |
| **Why** | Paling cepat memberi nilai ke pimpinan dan langsung memakai Master Bidang & PIC yang sudah jadi. |
| **Who** | Lead developer (build), operator sekolah (input program), kepala sekolah (validasi dashboard). |
| **When** | Dimulai setelah Fase 1 di-review dan `docker compose` + ketiga app berjalan di mesin tim. |
| **How** | Migrasi + API + UI per sub-modul, tes fitur per endpoint, demo mingguan ke kepala sekolah, umpan balik masuk sebagai issue GitHub. |
