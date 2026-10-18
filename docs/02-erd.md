# 02 — ERD Konseptual

ERD dipecah per domain agar terbaca. Semua tabel (kecuali yang ditandai) memiliki `school_id` → `schools.id` untuk isolasi tenant; demi ringkas, relasi `school_id` tidak digambar ulang di setiap diagram.

Legenda status: ✅ sudah ada migrasinya di Fase 1 · ⏳ fase berikutnya.

## A. Inti: Sekolah, Orang, Kelas (✅ sebagian)

```mermaid
erDiagram
    SCHOOLS ||--o{ ACADEMIC_YEARS : "punya"
    ACADEMIC_YEARS ||--|{ SEMESTERS : "terdiri dari"
    SCHOOLS ||--o{ EMPLOYEES : "mempekerjakan"
    SCHOOLS ||--o{ USERS : "akun"
    USERS |o--o| EMPLOYEES : "login sebagai"
    SCHOOLS |o--o| EMPLOYEES : "kepala sekolah"
    SCHOOLS ||--o{ DEPARTMENTS : "bidang"
    DEPARTMENTS |o--o{ DEPARTMENTS : "sub-bidang"
    DEPARTMENTS ||--o{ DEPARTMENT_PICS : "PIC"
    EMPLOYEES ||--o{ DEPARTMENT_PICS : "ditugaskan"
    ACADEMIC_YEARS ||--o{ DEPARTMENT_PICS : "periode"

    SCHOOLS ||--o{ STUDENTS : "⏳"
    STUDENTS ||--o{ ENROLLMENTS : "⏳ terdaftar"
    CLASSROOMS ||--o{ ENROLLMENTS : "⏳ rombel"
    ACADEMIC_YEARS ||--o{ CLASSROOMS : "⏳"
    EMPLOYEES ||--o{ CLASSROOMS : "⏳ wali kelas"
    STUDENTS }o--o{ GUARDIANS : "⏳ orang tua/wali"

    SCHOOLS {
        ulid id PK
        string npsn UK
        string name
        string level "SD/SMP/SMA/SMK"
        ulid principal_employee_id FK
        jsonb settings "kktp_default, report_city, ..."
    }
    ACADEMIC_YEARS {
        ulid id PK
        string name "2026/2027"
        date starts_on
        date ends_on
        bool is_active "unik aktif per sekolah"
    }
    SEMESTERS {
        ulid id PK
        tinyint term "1 Ganjil, 2 Genap"
        date starts_on
        date ends_on
    }
    EMPLOYEES {
        ulid id PK
        bigint user_id FK "nullable"
        string employee_number "NIP/NIY"
        string full_name
        string employment_type "pns/pppk/gty/gtt/pty/honorary"
        bool is_teacher
    }
    DEPARTMENTS {
        ulid id PK
        ulid parent_id FK
        string code UK "per sekolah"
        string name
    }
    DEPARTMENT_PICS {
        ulid id PK
        string role "lead/deputy/member/advisor"
        char raci "R/A/C/I"
        date started_on
        date ended_on "null = aktif"
    }
    ENROLLMENTS {
        ulid id PK
        ulid student_id FK
        ulid classroom_id FK
        smallint roll_number "no. absen"
    }
```

## B. Kurikulum & LMS: CP → TP → ATP → Prota/Promes → Modul Ajar (⏳)

Tulang punggungnya adalah **TP (learning_objectives)**: semua artefak guru (Prota, Promes, Silabus, KKTP, Modul Ajar, jurnal, nilai, soal) menunjuk ke TP, sehingga perubahan ATP otomatis mengalir ke dokumen turunannya.

```mermaid
erDiagram
    SUBJECTS ||--o{ LEARNING_OUTCOMES : "CP per fase"
    LEARNING_OUTCOMES ||--o{ LEARNING_OBJECTIVES : "diturunkan jadi TP"
    LEARNING_OBJECTIVES ||--o{ ATP_ITEMS : "diurutkan"
    LEARNING_PATHS ||--|{ ATP_ITEMS : "ATP berisi"
    TEACHING_ASSIGNMENTS }o--|| LEARNING_PATHS : "memakai ATP"
    TEACHING_ASSIGNMENTS }o--|| EMPLOYEES : "guru"
    TEACHING_ASSIGNMENTS }o--|| CLASSROOMS : "kelas"
    TEACHING_ASSIGNMENTS }o--|| SUBJECTS : "mapel"
    ATP_ITEMS ||--o{ PLAN_ALLOCATIONS : "Prota/Promes"
    ACADEMIC_CALENDAR_WEEKS ||--o{ PLAN_ALLOCATIONS : "minggu efektif"
    LEARNING_OBJECTIVES ||--|{ KKTP_CRITERIA : "4 interval"
    ATP_ITEMS ||--o{ LESSON_PLANS : "Modul Ajar"
    LESSON_PLANS ||--o{ WORKSHEETS : "LKPD"
    TEACHING_ASSIGNMENTS ||--o{ CLASS_SESSIONS : "pertemuan"
    CLASS_SESSIONS }o--o| ATP_ITEMS : "materi hari itu"
    CLASS_SESSIONS ||--o{ ATTENDANCES : "presensi"
    CLASS_SESSIONS ||--o| TEACHING_JOURNALS : "jurnal mengajar"

    LEARNING_OBJECTIVES {
        ulid id PK
        string code "mis. MAT.4.B.03"
        text description
        string phase "A-F"
    }
    ATP_ITEMS {
        ulid id PK
        ulid learning_path_id FK
        ulid learning_objective_id FK
        smallint sequence
        smallint allocated_jp "jam pelajaran"
        tinyint semester
    }
    ACADEMIC_CALENDAR_WEEKS {
        ulid id PK
        ulid semester_id FK
        date week_start
        bool is_effective "libur/ujian = false"
    }
    PLAN_ALLOCATIONS {
        ulid id PK
        ulid atp_item_id FK
        ulid week_id FK
        smallint jp
    }
    KKTP_CRITERIA {
        ulid id PK
        tinyint level "1-4"
        string label "Belum/Mulai/Berkembang/Sangat"
        smallint min_score
        smallint max_score
        text descriptor
    }
```

**Prota/Promes otomatis**: generator membagi `atp_items.allocated_jp` secara berurutan ke `academic_calendar_weeks` yang `is_effective = true` sesuai JP mingguan mapel (dari jadwal). Hasilnya disimpan di `plan_allocations` — Prota = agregasi per semester, Promes = matriks TP × minggu.

## C. Penilaian, Rapor & Smart Assessment

```mermaid
erDiagram
    TEACHING_ASSIGNMENTS ||--o{ ASSESSMENTS : "⏳ ulangan/tugas/proyek"
    ASSESSMENTS }o--o{ LEARNING_OBJECTIVES : "⏳ mengukur TP"
    ASSESSMENTS ||--o{ ASSESSMENT_ITEMS : "⏳ butir"
    ASSESSMENT_ITEMS }o--o| DJ_QUESTIONS : "question_id (Django)"
    ASSESSMENTS ||--o{ STUDENT_RESPONSES : "⏳ lembar jawaban"
    STUDENTS ||--o{ OBJECTIVE_SCORES : "⏳ nilai per TP"
    LEARNING_OBJECTIVES ||--o{ OBJECTIVE_SCORES : ""
    STUDENTS ||--o{ REPORT_CARDS : "⏳ rapor per semester"
    REPORT_CARDS ||--|{ REPORT_CARD_SUBJECTS : "nilai akhir + deskripsi"

    DJ_GENERATION_JOBS ||--o{ DJ_QUESTIONS : "✅ (Django)"
    DJ_QUESTIONS {
        uuid id PK
        string school_id "ref ULID"
        string learning_objective_id "ref ULID TP"
        string cognitive_level "C1-C6"
        string status "draft/reviewed/published/retired"
        jsonb options
        text answer_key
        text explanation
    }
    OBJECTIVE_SCORES {
        ulid id PK
        decimal score
        tinyint kktp_level "1-4"
        string source "assessment/manual/offline"
        timestamp client_recorded_at "untuk sinkron offline"
    }
    REPORT_CARD_SUBJECTS {
        ulid id PK
        decimal final_score
        text description "auto dari TP tertinggi & terendah"
    }
```

`DJ_*` = tabel milik Django di database `eduerp_assessment`. Relasi lintas database hanya berupa ID (tanpa foreign key fisik).

## D. SMART Control Center (15 sheet → relasional)

| Sheet lama | Tabel |
|---|---|
| Home, Navigasi | — (menjadi UI/menu) |
| Setup Sekolah | `schools`, `academic_years`, `semesters` ✅ |
| Master Bidang & PIC | `departments`, `department_pics` ✅ |
| Master Program | `programs` |
| KPI & Target | `kpis`, `kpi_targets` (per periode) |
| Milestone, Timeline | `milestones` (tanggal rencana/aktual, bobot) |
| Delegasi Tugas | `tasks` (RACI per tugas → `task_assignees`) |
| Log Realisasi | `realizations` |
| Evidence Register | `evidences` (file di object storage, polymorphic ke task/milestone/kpi) |
| Issues, Eskalasi | `issues`, `escalations` |
| Rapat, Keputusan, Tindak Lanjut | `meetings`, `meeting_attendees`, `decisions`, `follow_ups` |
| Review & Laporan | materialized view + generator dokumen |

```mermaid
erDiagram
    DEPARTMENTS ||--o{ PROGRAMS : "bidang pemilik"
    EMPLOYEES ||--o{ PROGRAMS : "PIC program"
    ACADEMIC_YEARS ||--o{ PROGRAMS : "periode"
    PROGRAMS ||--o{ KPIS : "diukur oleh"
    KPIS ||--o{ KPI_TARGETS : "target per bulan/semester"
    PROGRAMS ||--o{ MILESTONES : "tahapan"
    MILESTONES ||--o{ TASKS : "didelegasikan"
    TASKS ||--o{ TASK_ASSIGNEES : "RACI"
    EMPLOYEES ||--o{ TASK_ASSIGNEES : ""
    TASKS ||--o{ REALIZATIONS : "log realisasi"
    KPI_TARGETS ||--o{ REALIZATIONS : "capaian"
    REALIZATIONS ||--o{ EVIDENCES : "bukti"
    PROGRAMS ||--o{ ISSUES : "kendala"
    ISSUES ||--o{ ESCALATIONS : "naik level"
    MEETINGS ||--o{ DECISIONS : "menghasilkan"
    ISSUES }o--o{ MEETINGS : "dibahas di"
    DECISIONS ||--o{ FOLLOW_UPS : "tindak lanjut"
    FOLLOW_UPS }o--o| TASKS : "menjadi tugas"

    PROGRAMS {
        ulid id PK
        string code
        string name
        string status "draft/aktif/selesai/batal"
        date planned_start
        date planned_end
        decimal budget "link ke modul keuangan"
    }
    MILESTONES {
        ulid id PK
        decimal weight "bobot % program"
        date due_on
        date done_on
        string rag "auto: Red/Amber/Green"
    }
    EVIDENCES {
        ulid id PK
        string evidenceable_type
        ulid evidenceable_id
        string file_path
        string verification_status "pending/valid/ditolak"
    }
    ISSUES {
        ulid id PK
        string severity "rendah/sedang/tinggi/kritis"
        string status
        date due_on
    }
```

**Indikator Dashboard Pimpinan** dihitung dari relasi di atas:
- *% Selesai program* = Σ bobot milestone selesai.
- *Keterlambatan* = milestone `done_on IS NULL AND due_on < today`.
- *Evidence belum lengkap* = realisasi tanpa evidence `valid`.
- *Issue kritis* = `severity = kritis AND status ≠ closed`.
- *Rata-rata capaian KPI* = rata-rata (realisasi ÷ target) per KPI aktif.

## E. Logistik, Keuangan & HR (⏳)

```mermaid
erDiagram
    ROOMS ||--o{ ASSETS : "lokasi"
    ASSET_CATEGORIES ||--o{ ASSETS : ""
    ASSETS ||--o{ ASSET_MOVEMENTS : "mutasi/perbaikan"
    DEPARTMENTS ||--o{ PURCHASE_REQUESTS : "pengaju"
    PROGRAMS |o--o{ PURCHASE_REQUESTS : "untuk program"
    PURCHASE_REQUESTS ||--|{ PURCHASE_REQUEST_ITEMS : ""
    PURCHASE_REQUESTS ||--o{ APPROVALS : "alur persetujuan"
    BUDGET_ACCOUNTS ||--o{ JOURNAL_ENTRIES : "buku besar"
    PURCHASE_REQUESTS |o--o{ JOURNAL_ENTRIES : "realisasi"
    EMPLOYEES ||--o{ MONTHLY_EVALUATIONS : "EB"
    MONTHLY_EVALUATIONS }o--|| PERFORMANCE_REVIEWS : "6 EB → 1 EK"
    KPIS |o--o{ MONTHLY_EVALUATIONS : "indikator kinerja"
```

## F. Pemetaan dari sistem lama (Google Sheets → PostgreSQL)

| Sheet lama (`sap/Code.gs`) | Tabel baru | Catatan migrasi |
|---|---|---|
| `Users` | `users` | Password lama **plaintext** → wajib reset; akun dibuat ulang dengan hash bcrypt. |
| `PengaturanSekolah` | `schools` (+`settings`) | `api_key_gemini` **tidak dimigrasi**; pindah ke secret Django. |
| `MasterGuru` | `employees` (+`users`) | `nip_nik` → `employee_number`. |
| `MasterPeriode` | `academic_years` + `semesters` | |
| `TransLogAktivitas` | `activity_logs` | |
| `MasterSiswa`, `MasterOrtu`, `MasterWali`, `MasterWilayah`, ... | `students`, `guardians`, referensi wilayah Kemendagri | |
| `MasterKelas`, `MasterMapel` | `classrooms`, `subjects`, `teaching_assignments` | |
| `MasterMateri` (+ matriks STNKM), `MasterMBK` (+ matriks STNKM) | `learning_objectives`, `assessment_types` + `dimension_weights` | Matriks S-T-N-K-M dipertahankan sebagai bobot dimensi (lihat bagian G). |
| `TransNilai`, `RekapRaporAkhir` | `objective_scores` (+ `points_s..m`, `scoring_method`), `report_cards` | Periksa `id_nilai` ganda akibat bug pembaruan baris (06 §5.3). |
| `TransPresensi` | `attendances` | Normalisasi kode H/S/I/A. |
| `TransJurnal` (jurnal anekdotal) | `observations`, `observation_attachments` | Lampiran dipindah dari Drive publik ke penyimpanan privat. |
| `TransJadwal` | `schedule_series`, `class_sessions` | `id_group` → seri jadwal. |
| `MasterBankPengetahuan` | `knowledge_assets` | |
| `HEURISTIK_DICT` (hardcoded di `jurnal.html`) | `nlp_lexicon` | Dikelola admin dari UI. |
| `PengaturanSekolah` (WA: `status_wa_api`, `wa_api_token`, `no_wa_kepsek`, `id_wa_group_guru`) | `schools.settings` + secret terenkripsi | Token tidak disimpan polos. |
| `MasterProyek`, `MasterTargetP5`, `TransNilaiP5`, `TransCatatanP5` | `projects`, `project_targets`, `project_scores` | |
| `TransCatatanWali` | `report_cards.homeroom_note` | |

## G. Tambahan dari sistem lama: STNKM, Jurnal, P5, Rapor, Notifikasi (⏳)

Rinciannya ada di [`06-spesifikasi-edurapor.md`](06-spesifikasi-edurapor.md).

```mermaid
erDiagram
    ASSESSMENT_TYPES ||--o| DIMENSION_WEIGHTS : "bobot STNKM jenis"
    LEARNING_OBJECTIVES ||--o| DIMENSION_WEIGHTS : "bobot STNKM materi/TP"
    ASSESSMENT_TYPES ||--o{ OBJECTIVE_SCORES : "jenis"
    STUDENTS ||--o{ OBSERVATIONS : "jurnal anekdotal"
    EMPLOYEES ||--o{ OBSERVATIONS : "pelapor"
    OBSERVATIONS ||--o{ OBSERVATION_ATTACHMENTS : "bukti"
    NLP_LEXICON }o--|| SCHOOLS : "kamus heuristik"
    PROJECTS ||--|{ PROJECT_TARGETS : "target P5"
    PROJECT_TARGETS ||--|{ PROJECT_TARGET_DIMENSIONS : "relasi STNKM"
    PROJECT_TARGETS ||--o{ PROJECT_SCORES : "predikat SB/BSH/MB/BB"
    STUDENTS ||--o{ PROJECT_SCORES : ""
    REPORT_CARDS ||--o{ REPORT_CARD_EXTRACURRICULARS : ""
    REPORT_CARDS ||--o| REPORT_CARD_ATTENDANCE_SUMMARY : "S/I/A"
    STUDENTS ||--o{ STUDENT_RISK_FLAGS : "EWS"
    EWS_RULES ||--o{ STUDENT_RISK_FLAGS : "aturan"
    STUDENTS ||--o{ GUARDIAN_CONTACTS : "orang tua/wali"
    GUARDIAN_CONTACTS ||--o{ NOTIFICATION_LOGS : "WA"
    SCHEDULE_SERIES ||--|{ CLASS_SESSIONS : "sesi berulang"
    ROOMS ||--o{ CLASS_SESSIONS : ""

    DIMENSION_WEIGHTS {
        ulid id PK
        string weightable_type
        ulid weightable_id
        decimal s "default 1.0"
        decimal t
        decimal n
        decimal k
        decimal m
    }
    OBJECTIVE_SCORES {
        ulid id PK
        decimal raw_score "null = tidak dinilai, bukan 0"
        decimal points_s
        decimal points_t
        decimal points_n
        decimal points_k
        decimal points_m
        string scoring_method "auto/manual"
    }
    OBSERVATIONS {
        ulid id PK
        date observed_on
        text narrative
        jsonb ai_scores "skor usulan AI/heuristik"
        jsonb final_scores "skor setelah koreksi guru, -5..+5"
        string score_source "ai/heuristic/manual"
        string impact_mode "bonus/separate"
    }
    NLP_LEXICON {
        ulid id PK
        string phrase
        decimal s
        decimal t
        decimal n
        decimal k
        decimal m
    }
    GUARDIAN_CONTACTS {
        ulid id PK
        string relation "ayah/ibu/wali"
        string whatsapp
        bool wa_opt_in
    }
    STUDENT_RISK_FLAGS {
        ulid id PK
        string category "akademik/presensi/karakter"
        tinyint level "1 waspada, 2 perhatian, 3 bahaya"
        text reason
        date detected_on
    }
```

