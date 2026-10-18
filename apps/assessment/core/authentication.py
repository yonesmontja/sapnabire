"""Autentikasi service-to-service.

Laravel menandatangani JWT HS256 berumur ≤ 5 menit untuk setiap request ke Django:
    iss=eduerp-core, aud=eduerp-assessment, sub=<user id>, school_id=<ULID>, scopes=[...]
Django tidak pernah menerima token user (Sanctum) secara langsung.
"""

from dataclasses import dataclass, field

import jwt
from django.conf import settings
from rest_framework import authentication, exceptions, permissions


@dataclass(frozen=True)
class ServicePrincipal:
    user_id: str
    school_id: str
    role: str
    scopes: frozenset[str] = field(default_factory=frozenset)

    is_authenticated = True

    def has_scope(self, scope: str) -> bool:
        return scope in self.scopes


class ServiceJWTAuthentication(authentication.BaseAuthentication):
    keyword = "Bearer"

    def authenticate(self, request):
        header = authentication.get_authorization_header(request).decode()
        if not header.startswith(f"{self.keyword} "):
            return None

        conf = settings.SERVICE_JWT
        try:
            claims = jwt.decode(
                header.removeprefix(f"{self.keyword} "),
                conf["SECRET"],
                algorithms=["HS256"],
                audience=conf["AUDIENCE"],
                issuer=conf["ISSUER"],
                leeway=conf["LEEWAY_SECONDS"],
                options={"require": ["exp", "iat", "sub", "school_id"]},
            )
        except jwt.PyJWTError as exc:
            raise exceptions.AuthenticationFailed(f"Service token tidak valid: {exc}") from exc

        principal = ServicePrincipal(
            user_id=str(claims["sub"]),
            school_id=claims["school_id"],
            role=claims.get("role", ""),
            scopes=frozenset(claims.get("scopes", [])),
        )
        return principal, claims

    def authenticate_header(self, request):
        return self.keyword


class IsServiceRequest(permissions.BasePermission):
    def has_permission(self, request, view):
        return isinstance(request.user, ServicePrincipal)


class HasScope(permissions.BasePermission):
    """Pakai dengan `required_scope = "assessment:generate"` di view."""

    def has_permission(self, request, view):
        scope = getattr(view, "required_scope", None)
        return scope is None or request.user.has_scope(scope)
