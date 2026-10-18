# 06 — Spesifikasi dari Sistem Lama (EduRapor v6–v9, Google Apps Script)

Dokumen ini melengkapi spesifikasi EduERP berdasarkan kode dan dokumen yang **benar-benar berjalan** di sekolah. Prinsipnya: setiap aturan bisnis yang sudah dipakai guru wajib tetap ada di EduERP (kecuali yang tercatat sebagai bug/risiko di §5), sehingga migrasi tidak mengubah cara sekolah menilai siswa.

## 1. Sumber yang diperiksa

| Sumber | Isi | Catatan |
|---|---|---|
| Google Drive `SAP › sapnabire` | 15 file HTML, `Code.gs` (49 fungsi backend), template import Excel, contoh blangko rapor 2025, data timeline STNKM, dokumen panduan teknis Milestone 1–5, dokumen pengembangan produk, transkrip diskusi pengembangan (Laporan STNKM, Import Excel, Log Aktivitas, Pengelompokan Mapel Rapor), kamus heuristik NLP, halaman Bank Pengetahuan | `Code.gs`, `navbar`, `harian`, `dashboard`, `login` versi Drive = berkas yang diunggah ke sesi ini (ukuran sama persis). |
| Repo `yonesmontja/sap` | Versi kode di GitHub | Bercabang dari versi Drive. Lihat §5.1. |

File yang **sengaja tidak dibuka**: `moonshot_apikey.txt` (berisi kredensial; lihat §5.2). Data nilai dan nama siswa di file Excel hanya dibaca untuk memahami struktur kolom, **tidak** disalin ke repo.

HTML yang versinya di Drive lebih baru dari repo, tetapi tidak saya baca baris demi baris (perilakunya dibaca dari fungsi `Code.gs` yang dipanggilnya serta dokumen pendukung): `jadwal`, `jurnal`, `laporan_stnkm`, `presensi`, `rapor`, `master`, `master_siswa`, `import_excel`. Detail tampilan halaman-halaman ini perlu dicek ulang saat modul terkait dibangun.

## 2. Peta halaman lama → modul EduERP

| Halaman lama | Fungsi | Peran | Modul EduERP | Service | Fase |
|---|---|---|---|---|---|
| `login` | Login dengan pilihan peran (admin/kepsek/guru/siswa) | publik | Auth | Laravel | ✅ 1 (tanpa pilihan peran; peran dari akun) |
| `navbar` | Menu + penyaring menu per peran (RBAC sisi klien) | semua | Layout + RBAC server | Next.js + Laravel | ✅ 1 |
| `dashboard` | Dasbor mutu akademik: KPI, **Early Warning System**, radar STNKM, korelasi kehadiran–karakter–nilai, tren per mapel/jenis/suku/bulan/minggu | admin, kepsek, guru | Dashboard Akademik | Next.js + Laravel (read model) | 3 |
| `profil` | Profil siswa (login siswa) | siswa | Portal Siswa/Ortu | Next.js | 4 |
| `master` | CRUD master sekolah: guru, mapel, kelas, periode, jenis penilaian + **matriks STNKM**, kategori, materi + matriks STNKM, P5 (proyek, target), ruangan, referensi kependudukan, pengaturan sekolah | admin (guru: lihat) | Master Data & Kurikulum | Laravel | 1 (sebagian ✅) → 3 |
| `master_siswa` | Data induk siswa + orang tua/wali + wilayah + no. WA | admin | Kesiswaan | Laravel | 3 |
| `jadwal` | Jadwal sesi berulang (grup), edit/hapus berantai, ruangan, persiapan khusus, notifikasi WA | admin, guru | Jadwal & Sesi Kelas | Laravel | 3 |
| `harian` | Input nilai massal per kelas/mapel/jenis/materi/tanggal + statistik langsung + riwayat + ekspor CSV/PDF | guru, admin | Daftar Nilai | Laravel + Next.js | 4 |
| `import_excel` | Impor nilai/jurnal dari .xlsx (mode OTOMATIS & MANUAL STNKM) | guru, admin | Import Nilai | Next.js (parse) + Laravel | 4 |
| `presensi` | Presensi harian H/S/I/A per kelas + notifikasi WA ke orang tua | guru | Presensi | Laravel | 3 |
| `jurnal` | Jurnal anekdotal (linimasa), skor STNKM via AI atau heuristik lokal, lampiran bukti, mode dampak | guru | Jurnal Observasi | Laravel + Django (NLP) | 3 |
| `rapor` | Rapor akademik + catatan wali kelas (AI dari jurnal) + notifikasi WA | wali kelas, admin, kepsek | Rapor | Laravel + Django (AI) | 4 |
| `catatan_p5`, `rapor_p5` | Catatan proses P5, kalkulasi predikat P5 otomatis, rapor P5 | wali kelas | Kokurikuler (P5) | Laravel | 4 |
| `laporan_stnkm` | Laporan tren STNKM mingguan per kelas + grafik + ekspor | guru, kepsek | Laporan STNKM | Laravel + Next.js | 4 |
| `log_aktivitas` | Audit trail + KPI ringkas + ekspor XLSX/CSV/PDF | admin, kepsek | Audit | Laravel | ✅ 1 (pencatatan), UI 2 |
| `aset_kurikulum` (Bank Pengetahuan) | Aset topik kontekstual (Budaya Papua, Modernisasi, Ekologi Alam, Sosial Manusia) + baseline STNKM | admin | Bank Pengetahuan | Laravel | 3 |

## 3. Aturan bisnis yang wajib dipertahankan

### 3.1 Penilaian STNKM "Double-Layer"
Lima dimensi: **S** Skill/praktik, **T** Teori/konsep, **N** Nalar/HOTS, **K** Karakter, **M** Minat.

- Setiap **jenis penilaian** (`MasterMBK`) dan setiap **materi** (`MasterMateri`) punya bobot S/T/N/K/M.
- Saat nilai mentah disimpan (mode `OTOMATIS`): `poin_X = nilai × bobot_jenis_X × bobot_materi_X`, dibulatkan 2 desimal. Bobot kosong/tidak valid diperlakukan **1.0**.
- Mode `MANUAL` (dari impor Excel guru yang sudah punya nilai per aspek): poin S/T/N/K/M dan nilai rata-rata diambil apa adanya. Kolom `metode_stnkm` menandai asal baris.
- **Sinkronisasi ulang** (setelah bobot diubah) hanya menghitung ulang baris `OTOMATIS` pada periode aktif; baris `MANUAL` tidak boleh tersentuh.
- **Nilai kosong ≠ 0.** Sel kosong berarti "hadir tetapi tidak dinilai": baris dilewati, tidak disimpan, dan tidak menjadi pembagi rata-rata.

**EduERP:** `assessment_types` dan `learning_objectives`/materi memiliki tabel bobot `dimension_weights` (polimorfik). `objective_scores` menyimpan `raw_score` + `points_s..points_m` + `scoring_method` (`auto`/`manual`). Perhitungan ada di Laravel (deterministik, transaksional) dan dilengkapi unit test dengan contoh dari dokumen panduan (85 × 1.0 × 1.0 = 85; 85 × 0.7 × 0.8 = 47.6).

### 3.2 Jurnal Anekdotal (observasi perilaku)
- Isian: tanggal, siswa, guru pelapor, konteks/materi, narasi kejadian, lampiran bukti (foto/dokumen), skor S/T/N/K/M **−5.0 s.d. +5.0** (positif = perilaku baik, negatif = pelanggaran, 0 = tidak ada indikasi).
- Sumber skor: AI (dulu Gemini/Qwen/DeepSeek/Claude/OpenKey via API) **atau** mesin heuristik lokal (kamus ±130 frasa → bobot STNKM, mis. "jujur" K+5, "menyontek" N−1 K−5) sebagai cadangan saat offline. Guru dapat mengoreksi skor sebelum menyimpan.
- `mode_dampak`: **BONUS/Intervensi** (poin ditambahkan ke vektor STNKM rapor, dibagi rata ke jumlah mapel) atau **PISAH** (hanya portofolio naratif). Impor jurnal dari Excel default `PISAH`.
- Tampilan linimasa (terbaru di atas), filter bertingkat Kelas → Siswa & Materi.

**EduERP:** tabel `observations` + `observation_attachments` (object storage privat, URL bertanda tangan, kompresi gambar di klien maks ±1 MB). Skor AI dari Django (`POST /v1/observations/score`, structured output, satu model terkelola di server, bukan lima API key di sekolah). Kamus heuristik menjadi tabel `nlp_lexicon` yang bisa dikelola admin dari UI, dan juga dipakai di PWA saat offline. Koreksi guru disimpan berdampingan dengan skor AI (`ai_scores` vs `final_scores`) sebagai data evaluasi kualitas AI.

### 3.3 Laporan Tren STNKM mingguan
- Week 1 = **Senin** dari tanggal awal tahun ajaran (default 13 Juli, bisa diganti user); mode alternatif **ISO 8601**.
- Rentang minggu ditampilkan kronologis penuh (termasuk minggu tanpa data), dapat difilter.
- Per minggu: rata-rata S, T, N, K, M (gabungan poin nilai + jurnal) serta rata-rata nilai Bahasa Indonesia dan Matematika.
- Sumbu-x: minggu, bulan, atau keduanya bertumpuk; label bulan tampil **sekali dan di tengah** minggu-minggu bulan tersebut.
- Tabel per siswa (akumulasi), ekspor grafik + data ke XLSX/CSV/PDF.

**EduERP:** fungsi PostgreSQL/`date_trunc('week')` + `academic_calendar_weeks` (sudah ada di ERD) menjadi sumber nomor minggu tunggal untuk Promes, laporan STNKM, dan jurnal mengajar. Mapel pembanding (B. Indonesia, Matematika) dikonfigurasi di `schools.settings`, bukan dicari dari nama mapel.

### 3.4 P5 / Kokurikuler
- Proyek P5 per kelas & periode, target per proyek (dimensi, elemen, sub-elemen, capaian akhir fase) dengan **relasi STNKM** (mis. `poin_K,poin_M`).
- Skor target = rata-rata poin dimensi terkait dari seluruh nilai siswa pada periode; predikat **SB ≥ 90, BSH ≥ 75, MB ≥ 60, BB < 60**.
- Catatan proses P5 per siswa; draf kasar guru bisa dirapikan AI.

### 3.5 Rapor (format Kurikulum Merdeka 2025)
- Identitas: nama, NISN, kelas, fase, semester, tahun ajaran, sekolah, alamat.
- Tabel mapel: **Nilai Akhir** + **Capaian Kompetensi** dua kalimat (capaian tertinggi; yang masih perlu bimbingan).
- Kokurikuler (narasi), Ekstrakurikuler (nama + keterangan), Ketidakhadiran (Sakit, Izin, Tanpa Keterangan), Catatan Wali Kelas, Tanggapan Orang Tua, tanda tangan Orang Tua, Wali Kelas, Kepala Sekolah (+ NIP).
- **Mapel agama** ditampilkan sesuai agama siswa; **IPA/IPS** tidak ditampilkan di kelas 1–3 (aturan sekolah, jadi dibuat konfigurasi).
- **Guru paralel:** satu mapel diajar beberapa guru untuk kelompok siswa berbeda dalam satu kelas → tampil satu baris, nilai diakumulasi. Di sistem lama diatasi dengan mengelompokkan nama mapel; di EduERP diselesaikan oleh struktur `subjects` (satu) ↔ `teaching_assignments` (banyak guru), sehingga tidak perlu trik nama.
- KKM/KKTP per mapel, fallback ke `kktp_default` sekolah.
- Catatan wali kelas: AI menyusun maksimal 3 kalimat formal-apresiatif dari kompilasi jurnal siswa; wali kelas menyunting lalu memfinalkan.

### 3.6 Early Warning System (dashboard)
Tingkat risiko per siswa (diambil yang tertinggi):

| Kategori | Bahaya (Tinggi) | Perhatian (Sedang) | Waspada |
|---|---|---|---|
| Akademik | ≥ 2 nilai di bawah KKM | — | 1 nilai di bawah KKM |
| Presensi | ≥ 3 Alpa | ≥ 1 Alpa **atau** Sakit+Izin ≥ 5 | — |
| Karakter | ≥ 2 jurnal intervensi/negatif (mode intervensi, atau K/S < 0) | 1 jurnal negatif | — |

Plus tren yang direncanakan di dokumen panduan: K atau M turun di bawah ambang **dua minggu berturut-turut** → notifikasi ke wali kelas.

**EduERP:** aturan disimpan sebagai konfigurasi per sekolah (`ews_rules`), dihitung job terjadwal harian ke tabel `student_risk_flags`, ditampilkan di dashboard dan bisa memicu notifikasi.

### 3.7 Dashboard akademik
KPI (siswa, guru, mapel, entri nilai, jurnal, % kehadiran), distribusi kategori nilai (≥ 90 Sangat Baik, 80–89 Baik, 70–79 Cukup, < 70 Perlu Bimbingan), radar 5 dimensi STNKM, korelasi kehadiran–karakter–nilai per siswa, rata-rata per mapel/jenis penilaian/suku/bulan/minggu, setiap grafik bisa diperbesar dan diekspor.

### 3.8 Presensi, Jadwal, Notifikasi WhatsApp
- Presensi H/S/I/A per siswa per tanggal per kelas.
- Jadwal: sesi bisa berulang (`id_group`); edit/hapus **berantai** (sesi ini saja, sesi ini dan sesudahnya, atau semua) dengan pergeseran waktu (delta) yang konsisten; ruangan, materi, persiapan khusus, status sesi.
- **Notifikasi WA** (gateway Fonnte, kirim batch dengan jeda 2 detik, nomor `08…` → `628…`, dukung ID grup WA) untuk: presensi (ke orang tua), jurnal, jadwal (ke guru/grup guru), rapor terbit, laporan STNKM. Bila API nonaktif, sistem menyediakan tombol WA manual. Setiap pengiriman tercatat di audit.
- Pengaturan: status gateway, token, no. WA kepala sekolah, ID grup WA guru; `no_wa` pada siswa, orang tua, dan wali.

**EduERP:** Laravel Notifications dengan channel `whatsapp` (driver Fonnte, mudah diganti ke WhatsApp Business API), lewat antrean, dengan tabel `notification_logs` dan **persetujuan orang tua (opt-in)** sebelum pesan dikirim ke nomor orang tua. Token gateway disimpan sebagai secret terenkripsi, bukan di tabel pengaturan biasa.

### 3.9 Import Excel
- Parsing di browser (SheetJS), batas **10 MB**, pratinjau tabel, baris bermasalah disorot merah dan memblokir unggah; sel nilai **kosong** diberi lencana kuning "dilewati" (tidak memblokir).
- Metadata dipilih sekali di layar (periode, kelas, mapel, jenis, materi, tanggal), bukan diketik per baris.
- Guru mapel hanya melihat materi dari mapel yang diampunya.
- Template: sheet `Format_Data` (`id_siswa, nama_siswa, S, T, N, K, M, Rata-rata` untuk mode MANUAL; `id_siswa, nilai` untuk OTOMATIS) + sheet `Referensi_Siswa` (ID, NISN, nama, kelas). Mode jurnal: `id_siswa, tanggal, narasi_kejadian, [val_S..val_M]`.

**EduERP:** alur sama, tetapi Laravel memvalidasi ulang di server (tenant, hak ampu, rentang nilai) dan impor bersifat transaksional (semua atau tidak sama sekali) dengan laporan hasil per baris.

### 3.10 Audit trail
Dicatat: login, logout, perubahan master & siswa, input/ubah/hapus nilai, presensi, jurnal (simpan, hapus, analisis AI, analisis heuristik), cetak rapor, modul P5, pengiriman WA. Tampilan khusus admin/kepsek dengan KPI (total aktivitas, akses sistem, data master, akademik), pencarian, urut, paginasi, ekspor XLSX/CSV/PDF.

**EduERP:** `activity_logs` (✅ Fase 1) dicatat oleh server, bukan dikirim dari browser seperti di sistem lama (yang bisa dimanipulasi atau terlewat).

### 3.11 Master & referensi
Agama, suku, pekerjaan, wilayah (provinsi–kabupaten/kota–kecamatan–desa–kode pos), sekolah asal (NPSN), orang tua (ayah/ibu + pekerjaan + alamat + WA), wali, siswa (NISN, panggilan, JK, TTL, suku, agama, sekolah asal, status tempat tinggal, alamat). Bank Pengetahuan Kurikulum: kategori aset, topik, panduan guru, baseline STNKM 0–100 %.

### 3.12 Arsip tahunan
Sistem lama menyalin spreadsheet lalu mengosongkan tabel transaksi. **EduERP tidak memerlukan ini:** semua data terikat `academic_year_id`/`semester_id`, sehingga cukup filter periode, ditambah backup PostgreSQL harian. Partisi tabel besar per tahun ajaran dipertimbangkan bila volume menuntut.

## 4. Peran pengguna (lama → baru)

| Lama | Baru (`UserRole`) | Catatan |
|---|---|---|
| admin | `school_admin` | |
| kepsek | `principal` | Login lama memakai NIP sebagai password → diganti akun biasa. |
| guru | `teacher` | Status **wali kelas** diturunkan dari `classrooms.homeroom_employee_id`, bukan flag di sesi. |
| siswa | `student` | Login lama: NISN sebagai username **dan** password → wajib akun + password sendiri (atau kode akses sekali pakai dari wali kelas). |
| — | `guardian` | Baru: orang tua menerima notifikasi dan melihat rapor anaknya. |

## 5. Temuan risiko & bug yang harus ditangani

### 5.1 Dua cabang kode berbeda
- **Drive** (dipakai sekolah): menambahkan notifikasi WA, kolom `no_wa`, dropdown Master/Jadwal, dan revisi `updateJadwalBerantai`.
- **Repo `sap`** (PR #1–#3): menambahkan penyaringan data sensitif di server (`maskApiKey_`, `sanitizeRows_`, data keluarga hanya dimuat bila diminta) dan penjaga halaman per peran.
- Versi Drive **tidak** memiliki perbaikan keamanan repo, dan repo tidak memiliki fitur WA. Selama EduERP belum menggantikan sistem lama, kedua cabang perlu digabung agar sekolah tidak berjalan tanpa perbaikan keamanan.

### 5.2 Keamanan & privasi (prioritas tinggi)
1. Password **plaintext** di sheet `Users`; login siswa dan kepala sekolah memakai nomor identitas (NISN/NIP) sebagai password.
2. API key AI dan **token WA** tersimpan di sheet `PengaturanSekolah`/Script Properties; ada file **`moonshot_apikey.txt`** di folder Drive. Disarankan: **cabut (rotate) kunci tersebut** dan hapus file teks kredensial dari Drive.
3. Lampiran jurnal (foto anak) dibagikan **"Anyone with the link"**. Di EduERP: penyimpanan privat + URL bertanda tangan yang kedaluwarsa.
4. Template/berkas Excel berisi nama dan ID siswa nyata; jangan dibagikan di luar sekolah dan jangan dimasukkan ke repo.

### 5.3 Bug integritas data
1. **`saveNilaiBatch`, baris pembaruan nilai:** `rowIdx: rowIndex + 2`, padahal `rowIndex` dihitung dari array yang sudah memuat baris header (baris sheet = `rowIndex + 1`). Akibatnya, **mengedit nilai yang sudah ada menulis ke baris di bawahnya**: nilai siswa berikutnya tertimpa, dan baris asli tidak berubah. Ini ada di versi Drive maupun repo. Saat migrasi: deteksi `id_nilai` ganda, lalu bandingkan dengan log aktivitas untuk memulihkan nilai yang tertimpa.
2. EWS menghitung Alpa dari `status === 'A'` **atau** `status_kehadiran === 'ALPA'`, sehingga data yang tersimpan sebagai `status_kehadiran = 'A'` tidak terhitung. Di EduERP: kode presensi dinormalisasi (`H/S/I/A`) dengan enum.
3. Tanggal tersimpan dalam beberapa format (`dd/MM/yyyy`, ISO, objek Date) → ETL migrasi wajib menormalisasi ke `date`.
4. Pencocokan pengampu mapel memakai `id_guru` yang bisa berisi ID **atau** nama guru → ETL memetakan ke `employees.id`, dan baris yang tidak cocok dilaporkan untuk diperiksa manual.

## 6. Dampak pada rancangan EduERP

Tabel baru di ERD (`02-erd.md`, bagian G): `dimension_weights`, `observations`, `observation_attachments`, `nlp_lexicon`, `projects`/`project_targets`/`project_target_dimensions`/`project_scores`, `extracurriculars`/`student_extracurriculars`, `report_card_*` (kokurikuler, ketidakhadiran, catatan, tanggapan orang tua), `schedules`/`schedule_series`, `rooms`, `knowledge_assets`, `ews_rules`/`student_risk_flags`, `guardian_contacts` (+ opt-in WA), `notification_logs`, serta referensi kependudukan.

Endpoint baru ada di `03-api-gateway.md` (bagian "Dari sistem lama"). Urutan pengerjaan di roadmap `00-strategi.md` disesuaikan: **Presensi, Jurnal (STNKM), Nilai dengan STNKM, dan Rapor** adalah fitur yang setiap hari dipakai guru, sehingga didahulukan sebelum ERP non-akademik.
