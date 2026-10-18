import { api } from "@/lib/api";
import type { Department, DepartmentPic, Resource, School } from "@/types/api";

const ROLE_LABEL: Record<DepartmentPic["role"], string> = {
  lead: "PIC Utama",
  deputy: "Wakil PIC",
  member: "Anggota",
  advisor: "Pembina",
};

function DepartmentCard({ department }: { department: Department }) {
  return (
    <li className="rounded-lg bg-white p-4 ring-1 ring-slate-200">
      <div className="flex items-center gap-2">
        <span className="h-3 w-3 rounded-full" style={{ backgroundColor: department.color ?? "#94a3b8" }} />
        <span className="font-medium text-slate-900">{department.name}</span>
        <span className="text-xs text-slate-400">{department.code}</span>
      </div>
      <ul className="mt-2 space-y-1 text-sm text-slate-600">
        {department.pics?.length ? (
          department.pics.map((pic) => (
            <li key={pic.id}>
              <span className="mr-2 inline-block w-6 rounded bg-slate-100 text-center text-xs font-semibold">{pic.raci}</span>
              {pic.employee?.display_name} — {ROLE_LABEL[pic.role]}
            </li>
          ))
        ) : (
          <li className="text-amber-600">Belum ada PIC</li>
        )}
      </ul>
      {!!department.children?.length && (
        <ul className="mt-3 space-y-2 border-l border-slate-200 pl-3">
          {department.children.map((child) => (
            <DepartmentCard key={child.id} department={child} />
          ))}
        </ul>
      )}
    </li>
  );
}

export default async function SetupPage() {
  const [{ data: school }, { data: departments }] = await Promise.all([
    api<Resource<School>>("/control/school"),
    api<Resource<Department[]>>("/control/departments"),
  ]);

  return (
    <section className="space-y-6">
      <header>
        <h1 className="text-2xl font-semibold text-slate-900">Setup Sekolah & Master PIC</h1>
        <p className="text-sm text-slate-500">
          {school.name} · NPSN {school.npsn} · {school.city}, {school.province} · Kepala sekolah:{" "}
          {school.principal?.display_name ?? "belum ditetapkan"}
        </p>
      </header>
      <ul className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        {departments.map((department) => (
          <DepartmentCard key={department.id} department={department} />
        ))}
      </ul>
    </section>
  );
}
