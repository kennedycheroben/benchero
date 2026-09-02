# Teamora Tenancy Model

## Conceptual Hierarchy

Teamora is a multi-tenant SaaS. The conceptual hierarchy is:
1. **User**: A human entity authenticated into the platform. A User can potentially belong to multiple Organizations.
2. **Organization (Tenant)**: The billing and administrative boundary (e.g., a sports club).
3. **Teams**: Sub-divisions within an Organization (e.g., Senior Men, U18).
4. **Players / Staff / Fixtures / Results**: Core entities that belong to an Organization (and usually a Team).

## Distinguishing Concepts

The architecture strictly separates three distinct concepts:
1. **Authentication:** "Who is this user?" (Managed via sessions)
2. **Tenant Context:** "Which organization is this request operating within?" (Managed via the URL path)
3. **Authorization:** "What is this user allowed to do within that organization?" (Managed via Roles and Permissions within the context)

**Rule:** We do NOT assume that an authenticated user maps 1:1 to an organization.

## Tenant Resolution Strategy

For the initial MVP, Teamora will use **Path-Based Tenant Resolution**.

### Selected Approach: Option B (Path)
**Pattern:** `teamora.com/o/{organization-slug}/...`
**Example:** `/o/acme-fc/dashboard`

### Justification
- **Shared Hosting & Deployment:** Truehost/cPanel shared hosting can make dynamic wildcard subdomains (Option A) difficult to configure and manage (especially for SSL certificates).
- **HTTPS:** Path-based isolation avoids complex wildcard SSL certificate requirements during the MVP phase.
- **Future Scalability:** The application logic is designed so that a `TenantMiddleware` extracts the tenant context. If we decide to migrate to subdomains in the future, we only need to update the `TenantMiddleware` and Routing logic, rather than rewriting the core application.
- **Session Selection (Option C):** Relying solely on the session for tenant selection makes deep linking, multiple tabs across different organizations, and REST API parity much harder. Path-based routing ensures the URL is always the source of truth for the context.

## Implementation Details

1. **Routing:** Routes belonging to a tenant will be prefixed with `/o/{tenant_slug}`.
2. **Tenant Middleware:** 
   - Intercepts requests to `/o/{tenant_slug}/*`.
   - Looks up the `Organization` by `slug`.
   - Validates that the currently authenticated `User` belongs to this `Organization` via the `organization_user` pivot table.
   - Sets the `Organization` in a globally accessible context (e.g., `App::setTenant($org)`).
3. **Scoping:** All database queries for tenant data MUST automatically or manually scope to `organization_id = {current_tenant_id}`.
