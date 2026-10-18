"use server";

import { redirect } from "next/navigation";

import { ApiError, api } from "@/lib/api";
import { clearSession, setSession } from "@/lib/session";
import type { SessionUser } from "@/types/api";

export interface LoginState {
  error?: string;
}

export async function login(_: LoginState, formData: FormData): Promise<LoginState> {
  try {
    const result = await api<{ token: string; expires_at: string; user: SessionUser }>("/auth/login", {
      method: "POST",
      body: JSON.stringify({
        login: formData.get("login"),
        password: formData.get("password"),
        device_name: "web",
      }),
    });
    await setSession(result.token, result.expires_at);
  } catch (error) {
    if (error instanceof ApiError) {
      return { error: error.errors.login?.[0] ?? error.message };
    }
    return { error: "Server tidak dapat dihubungi. Coba lagi beberapa saat." };
  }
  redirect("/dashboard");
}

export async function logout() {
  try {
    await api("/auth/logout", { method: "POST" });
  } finally {
    await clearSession();
  }
  redirect("/login");
}
