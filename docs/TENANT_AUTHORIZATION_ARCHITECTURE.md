# Tenant Authorization Architecture

## 1. Overview
Teamora is a multi-tenant SaaS application where data is strictly isolated between different organizations (tenants). To ensure security and prevent data leaks, tenant authorization is handled globally and sequentially during the request lifecycle.

## 2. Request Lifecycle
Every incoming HTTP request goes through a strict verification pipeline:

1. **Security Middleware**: Adds headers (HSTS, Content-Security-Policy).
2. **Session Middleware**: Starts secure sessions and reads `$_SESSION`.
3. **CSRF Middleware**: Validates state-changing POST/PUT requests.
4. **Authentication (`AuthMiddleware`)**: Rejects unauthenticated requests if the route requires a logged-in user.
5. **Tenant Resolution (`TenantMiddleware`)**:
    - If the route starts with `/o/{slug}`, extracts the `slug`.
    - Resolves the Organization from the database.
6. **Membership Verification (`TenantMiddleware`)**:
    - Queries `organization_user` to ensure the authenticated user belongs to the resolved organization.
    - If inactive or not a member, aborts with 403/404.
7. **Context Injection**: The `Organization` entity is injected into the `Request` attributes.
8. **Controller/Service**: Processes the logic natively within the tenant context.

## 3. The Boundary (`/o/{slug}`)
- No user can access a tenant route without explicitly passing the `slug` in the URL.
- We DO NOT use `?organization_id=X` as it is easily manipulatable.
- A user attempting to access `/o/org-a` will be blocked if they only have membership in `org-b`.

## 4. Organization Onboarding
The onboarding process bridges the gap between a verified user and an organization owner.
1. User hits `/onboarding`.
2. Creates an organization (Name, Slug, Country, Timezone).
3. The system creates the `Organization`, assigns the user the `owner` role in `organization_user`.
4. The system initializes a **Trial Subscription** linked to a default plan.

## 5. Roles & Authorization Matrix
Currently supported roles in `organization_user`:
- **owner**: Highest level access, billing and tenant configuration.
- **admin**: Manages teams, seasons, users.
- **member**: View access and participation rights.

> **Note**: We explicitly rejected Laravel's granular `permissions` / `role_assignments` system in favor of this simplified SaaS hierarchy.

## 6. Security Principles
- **Never trust client-supplied IDs:** The tenant context is strictly derived from the validated route parameter and matched against the authenticated session.
- **Server-Side Enforcement:** Do not rely on UI hiding. Controllers must inherit the Tenant context.
- **Isolate by default:** Queries should be scoped (e.g. `WHERE organization_id = :org_id`) automatically at the repository level where possible.
