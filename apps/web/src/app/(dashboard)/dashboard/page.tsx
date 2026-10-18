import { api } from "@/lib/api";
import type { Resource, School } from "@/types/api";

export default async function DashboardPage() {
  const { data: school } = await api<Resource<School>>("/control/school");

  return (
    <section className="space-y-2">
      <h1 className="text-2xl font-semibold text-slate-900">{school.name}</h1>
      <p className="text-slate-500">
        Tahun ajaran aktif: {school.active_academic_year?.name ?? "belum diatur"} · KKTP default {school.settings.kktp_default ?? "-"}
      </p>
      <p className="text-sm text-slate-400">Kartu KPI Dashboard Pimpinan akan tampil di sini setelah modul Program & KPI dibangun.</p>
    </section>
  );
}
