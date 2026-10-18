"use client";

import { useActionState } from "react";

import { login, type LoginState } from "./actions";

export default function LoginPage() {
  const [state, formAction, pending] = useActionState<LoginState, FormData>(login, {});

  return (
    <main className="flex min-h-screen items-center justify-center bg-slate-50 px-4">
      <form action={formAction} className="w-full max-w-sm space-y-4 rounded-xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <div>
          <h1 className="text-xl font-semibold text-slate-900">EduERP</h1>
          <p className="text-sm text-slate-500">Sistem Manajemen Terpadu Sekolah</p>
        </div>
        <label className="block text-sm">
          <span className="text-slate-700">Username atau email</span>
          <input name="login" required autoComplete="username" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" />
        </label>
        <label className="block text-sm">
          <span className="text-slate-700">Password</span>
          <input name="password" type="password" required autoComplete="current-password" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2" />
        </label>
        {state.error && <p role="alert" className="text-sm text-red-600">{state.error}</p>}
        <button disabled={pending} className="w-full rounded-md bg-blue-600 px-3 py-2 font-medium text-white disabled:opacity-60">
          {pending ? "Memproses…" : "Masuk"}
        </button>
      </form>
    </main>
  );
}
