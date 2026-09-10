# Security Model

## Perimeter Security
- **HTTPS/TLS**: Required in production for all endpoints.
- **HSTS**: Enforced via headers.

## Application Security
- **Authentication**: See `AUTHENTICATION_ARCHITECTURE.md`.
- **CSRF Protection**: All state-changing requests (`POST`, `PUT`, `DELETE`) require a valid CSRF token, validated by `CsrfMiddleware`.
- **SQL Injection**: Prevented globally by strictly using PDO Prepared Statements.
- **XSS**: Handled via secure output escaping in the templating engine (Plates).

## Authorization (Phase 4 Foundation)
- **Platform Roles**: e.g., `platform_admin`. Full access across tenants.
- **Organization Roles**: e.g., `owner`, `admin`, `manager`, `staff`, `viewer`. Scoped strictly to the tenant (`organization_user` pivot).
- Organization membership roles must never be confused with platform administrator privileges.

## Rate Limiting
- To mitigate brute force, scraping, and DoS attacks, authentication and public-write endpoints will enforce rate limits (e.g., max 5 login attempts per minute per IP).
