import { NextResponse, type NextRequest } from "next/server";

// Cek optimistis saja (ada cookie atau tidak). Validasi token sesungguhnya dilakukan Laravel di setiap request API.
const PUBLIC_ROUTES = ["/login"];

export function proxy(request: NextRequest) {
  const hasSession = request.cookies.has("eduerp_session");
  const isPublic = PUBLIC_ROUTES.includes(request.nextUrl.pathname);

  if (!hasSession && !isPublic) {
    return NextResponse.redirect(new URL("/login", request.nextUrl));
  }
  if (hasSession && isPublic) {
    return NextResponse.redirect(new URL("/dashboard", request.nextUrl));
  }
  return NextResponse.next();
}

export const config = {
  matcher: ["/((?!_next/static|_next/image|favicon.ico).*)"],
};
