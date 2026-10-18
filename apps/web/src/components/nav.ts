// Peta modul EduERP. Item tanpa `href` = belum dibangun (fase berikutnya, lihat docs/06-roadmap.md).
export const NAV = [
  { group: "Ringkasan", items: [{ label: "Dashboard Pimpinan", href: "/dashboard" }] },
  {
    group: "SMART Control",
    items: [
      { label: "Setup Sekolah & PIC", href: "/control/setup" },
      { label: "Program, KPI & Milestone" },
      { label: "Delegasi & Evidence" },
      { label: "Issue, Rapat & Keputusan" },
    ],
  },
  { group: "LMS & Administrasi Guru", items: [{ label: "ATP → Prota/Promes" }, { label: "Modul Ajar & LKPD" }, { label: "Presensi & Jurnal" }] },
  { group: "Smart Assessment", items: [{ label: "AI Soal Generator" }, { label: "Analisis Butir Soal" }, { label: "Simulator TKA" }] },
  { group: "Penilaian & Rapor", items: [{ label: "Daftar Nilai per TP" }, { label: "Rapor" }] },
  { group: "Logistik, Keuangan & HR", items: [{ label: "Inventaris" }, { label: "Keuangan & PR" }, { label: "Evaluasi Kinerja EB/EK" }] },
] as const;
