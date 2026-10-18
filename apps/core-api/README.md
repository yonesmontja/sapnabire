# EduERP Core API (Laravel 13)

Core ERP, autentikasi, multi-tenant, dan **API Gateway** EduERP. Panduan instalasi: [`docs/05-setup-lokal.md`](../../docs/05-setup-lokal.md).

## Struktur

```
app/
├── Enums/                    UserRole, EmploymentType, PicRole (+ RACI default)
├── Http/
│   ├── Controllers/Api/V1/   Auth, School, AcademicYear, Employee, Department, DepartmentPic
│   │   └── Assessment/       GeneratorController → Django
│   ├── Controllers/Api/Internal/  Webhook dari Django (HMAC)
│   ├── Middleware/           role:, school, service.signature
│   ├── Requests/V1/          Validasi + aturan tenant (exists ... where school_id)
│   └── Resources/V1/         Bentuk JSON respons
├── Models/                   School, AcademicYear, Semester, Employee, Department, DepartmentPic, ActivityLog
│   └── Concerns/BelongsToSchool.php   scope forSchool()
└── Services/Assessment/AssessmentClient.php   Service JWT + HTTP ke Django
database/
├── migrations/2026_10_18_1000xx_*   Fase 1: Setup Sekolah & Master PIC
└── seeders/SchoolSetupSeeder.php    Sekolah contoh, TA 2026/2027, 6 bidang, akun admin
tests/Feature/                Control/SchoolSetupTest, Assessment/AssessmentGatewayTest
```

## Perintah

```bash
php artisan migrate --seed     # skema + data awal
php artisan serve --port=8000
php artisan test               # butuh database eduerp_test
./vendor/bin/pint              # format kode
php artisan route:list --path=api
```
