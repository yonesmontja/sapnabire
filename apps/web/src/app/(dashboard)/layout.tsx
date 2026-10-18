import Link from "next/link";

import { logout } from "@/app/(auth)/login/actions";
import { NAV } from "@/components/nav";

export default function DashboardLayout({ children }: LayoutProps<"/">) {
  return (
    <div className="flex min-h-screen bg-slate-50">
      <aside className="hidden w-64 shrink-0 border-r border-slate-200 bg-white p-4 md:block">
        <p className="mb-6 text-lg font-semibold text-slate-900">EduERP</p>
        <nav className="space-y-5 text-sm">
          {NAV.map((section) => (
            <div key={section.group}>
              <p className="mb-1 text-xs font-medium uppercase tracking-wide text-slate-400">{section.group}</p>
              <ul className="space-y-1">
                {section.items.map((item) => (
                  <li key={item.label}>
                    {"href" in item ? (
                      <Link href={item.href} className="block rounded px-2 py-1 text-slate-700 hover:bg-slate-100">
                        {item.label}
                      </Link>
                    ) : (
                      <span className="block px-2 py-1 text-slate-400">{item.label}</span>
                    )}
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </nav>
        <form action={logout} className="mt-8">
          <button className="text-sm text-slate-500 hover:text-slate-900">Keluar</button>
        </form>
      </aside>
      <main className="flex-1 p-4 md:p-8">{children}</main>
    </div>
  );
}
