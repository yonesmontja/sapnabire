import "server-only";

import { cookies } from "next/headers";

// Token Sanctum disimpan di cookie httpOnly: tidak bisa dibaca JavaScript browser (aman dari XSS).
export const SESSION_COOKIE = "eduerp_session";

export async function getToken(): Promise<string | undefined> {
  return (await cookies()).get(SESSION_COOKIE)?.value;
}

export async function setSession(token: string, expiresAt: string) {
  (await cookies()).set(SESSION_COOKIE, token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    path: "/",
    expires: new Date(expiresAt),
  });
}

export async function clearSession() {
  (await cookies()).delete(SESSION_COOKIE);
}
