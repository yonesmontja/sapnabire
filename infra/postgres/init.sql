-- Satu cluster PostgreSQL, satu database per bounded context.
-- eduerp            : Laravel (master data, ERP, LMS, rapor, control center) — sumber kebenaran
-- eduerp_assessment : Django (job generator, bank soal AI, hasil analisis)
-- *_test            : database untuk test suite masing-masing service
CREATE DATABASE eduerp_assessment OWNER eduerp;
CREATE DATABASE eduerp_test OWNER eduerp;
ALTER USER eduerp CREATEDB; -- Django test runner membuat test_eduerp_assessment sendiri
