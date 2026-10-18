import "server-only";

import { redirect } from "next/navigation";

import { clearSession, getToken } from "./session";

const BASE_URL = process.env.CORE_API_URL ?? "http://127.0.0.1:8000/api/v1";

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public errors: Record<string, string[]> = {},
  ) {
    super(message);
  }
}

/**
 * Fetch ke Laravel dari Server Component / Server Action (pola BFF).
 * Browser tidak pernah memegang token maupun alamat internal API.
 */
export async function api<T>(path: string, init: RequestInit = {}): Promise<T> {
  const token = await getToken();
  const response = await fetch(`${BASE_URL}${path}`, {
    ...init,
    cache: "no-store",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...init.headers,
    },
  });

  if (response.status === 401) {
    await clearSession();
    redirect("/login");
  }

  const body = response.status === 204 ? null : await response.json();
  if (!response.ok) {
    throw new ApiError(response.status, body?.message ?? "Terjadi kesalahan.", body?.errors);
  }
  return body as T;
}
