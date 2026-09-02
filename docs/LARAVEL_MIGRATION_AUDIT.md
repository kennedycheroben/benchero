# Laravel-to-Teamora Migration Audit

This document inventories the original Laravel implementation of Gacio Sports and defines the migration strategy for transitioning to the Teamora multi-tenant SaaS architecture.

## 1. Route Inventory

### Public Routes
- `GET /` - Home
- `GET /about` - About Club
- `GET /teams/{team}` - Specific Team Page
- `GET /staff` - Staff overview
- `GET /fixtures` - Fixtures overview
- `GET /results` - Results overview
- `GET /standings` - League table
- `GET /news` - Newsroom
- `GET /gallery` - Gallery
- `GET /sponsors` - Sponsors & Partners
- `GET /shop` - Club Shop
- `GET /tickets` - Tickets
- `GET /fundraising` - Fundraising
- `GET /contact`, `/join`, `/privacy`, `/terms`, `/safeguarding` - Legal and informational pages

### Authentication Routes
- `GET /sign-in` - Auth entry
- `GET /auth/jdm/redirect` - OAuth Redirect to JDM
- `GET /auth/jdm/callback` - OAuth Callback from JDM
- `POST /logout` - Logout

### Administration Routes (Prefixed `/admin/`)
- `GET /` - Dashboard
- `GET|PUT /clubs/{club}/settings` - Club settings management
- `GET|POST /clubs/{club}/teams` - Team management
- `GET|POST /clubs/{club}/seasons` - Season management
- `GET|POST /assignments` - Role assignments

## 2. Controller Analysis

| Controller | Purpose | Teamora Action |
| --- | --- | --- |
| `SiteController` | Handles public-facing club pages, statically loading the single `Club::firstOrFail()`. | **REDESIGN** - Must become tenant-aware to render public profiles dynamically based on organization slug. |
| `JdmAuthenticationController` | JDM OAuth login flow. | **REMOVE** - Teamora implements native auth; JDM handoff happens independently via server-to-server tokens later (Phase A). |
| `DashboardController` | Simple authenticated landing page. | **REDESIGN** - Must support tenant context (`/o/{slug}/dashboard`). |
| `ClubSettingsController` | Manage single club details (name, affiliation, theming). | **PRESERVE / REDESIGN** - Becomes Organization Settings. |
| `TeamController` | Create/list teams for a club. | **PRESERVE** - Convert to multi-tenant structure mapping to `teams` table. |
| `SeasonController` | Create/list seasons for a club. | **PRESERVE** - Convert to multi-tenant structure mapping to `seasons` table. |
| `RoleAssignmentController`| Granular RBAC assigning users to roles and optionally teams. | **REDESIGN** - Teamora simplifies this into `organization_user.role` initially. |

## 3. Database & Models Mapping

| Laravel Entity/Table | Teamora Entity/Table | Status | Action |
| --- | --- | --- | --- |
| `users` | `users` | BUILT | Extended with email verification & password hash. |
| `clubs` | `organizations` | BUILT | Rebuilt for multi-tenancy. |
| `teams` | `teams` | BUILT | Exists; needs `sport_id` mapping. |
| `seasons` | `seasons` | MISSING | To be created. |
| `roles` / `permissions` | `organization_user.role` | REDESIGN | Simplified into tenant roles (owner, admin, member). |
| `role_assignments` | `organization_user` / `team_staff`| REDESIGN | Tenant membership handled. Team assignments pending. |
| `audit_events` | `audit_logs` | BUILT | Preserved format. |
| `external_identities` | (None) | REMOVE | Not storing JDM Oauth data directly anymore. |

## 4. Existing Sports Functionality

The Laravel application is essentially a structural shell for a single football club (Gacio Sports).
- **Existing entities:** Teams and Seasons.
- **Missing entities (Stubs in UI only):** Players, Fixtures, Results, Standings.
- **Sport logic:** The database does not enforce football-specific rules, but lacks a `sports` abstraction.
- **SaaS Requirement:** Teamora introduces the `sports` table (Football, Basketball, Volleyball, Rugby) which Teams will belong to, instead of hardcoding one sport per club.

## 5. Public Club Functionality

The Laravel app contains a robust public-facing web presence (Home, Teams, Fixtures, Gallery, Shop, etc.).
- **Action:** Teamora must rebuild this as **Public Organization Pages**. For MVP, we need to focus on surfacing the Organization Profile, its Teams, Fixtures, and Results publicly via a URL like `teamora.com/club/{slug}`.

## 6. Administration Hierarchy

- **Laravel:** Mixed platform/club administration because it only supported one club.
- **Teamora:**
  - **Platform Admin:** Superusers managing subscriptions, global sports, global organizations.
  - **Organization Admin:** Tenant owners managing their specific organization, teams, players, and billing.

## 7. SaaS Transformation Feature Status

### PRESERVE
- Team management (creation, listing).
- Season management.
- Audit logging.

### REDESIGN
- **Public Site:** Must become dynamic, tenant-aware public organization profiles.
- **Roles:** Moving from complex `permission_role` DB structures to `organization_user` enums.
- **Routing:** Moving from Laravel's global routes to `/o/{slug}/...` context boundaries.

### REMOVE
- JDM OAuth `ExternalIdentity` logic (replaced by local auth).
- Laravel Blade components, Service Providers, Artisan.

### NEW
- Multi-tenancy isolation.
- `sports` configuration.
- Billing (`plans`, `subscriptions`, `payments`).
- Platform Administration.

## 8. Security Migration

- **Authentication:** Migrated to native `AuthService` using `PASSWORD_DEFAULT`.
- **Authorization:** Laravel Policies must be rewritten as procedural logic inside controllers/services checking `organization_user`.
- **CSRF:** Reimplemented natively in `CsrfMiddleware`.
- **Rate Limiting:** Reimplemented natively via `RateLimiter`.

## 9. UI/UX Inventory

| Laravel Page | Teamora Page | Status | Priority |
| --- | --- | --- | --- |
| `public.auth-entry` | `auth.login` / `auth.register` | BUILT | High |
| `dashboard.index` | `tenant.dashboard` | MISSING | High |
| `admin.teams.index` | `tenant.teams.index` | MISSING | High |
| `admin.seasons.index` | `tenant.seasons.index` | MISSING | Medium |
| `public.home` | `public.organization.show` | MISSING | Low |

## 10. Recommended Implementation Order

1. **Authentication (Current):** Password resets & profile management.
2. **Tenant Boundary:** The `/onboarding` flow to create an organization and route to `/o/{slug}/dashboard`.
3. **Seasons & Teams:** Allowing org admins to create seasons and teams under a specific sport.
4. **Players & Roster:** Linking players to teams.
5. **Fixtures & Results:** Scheduling matches and recording scores.
6. **Public Pages:** Exposing the data to non-authenticated visitors.
7. **Billing:** M-Pesa integration.

## 11. Critical Dependency Analysis

- Cannot build Teams until **Tenant Context** is established.
- Cannot build Fixtures until **Teams** and **Seasons** exist.
- Cannot launch Billing until **Organizations** are actively using the MVP.

## 12. Laravel Removal Criteria

The `/opt/lampp/htdocs/gacio_sports` directory can be safely archived and removed when:
1. Teamora's Authentication (Phase 4) is complete.
2. Teamora's Sports MVP (Phase 5) is complete and reproduces Teams/Seasons.
3. Teamora's Public Organization profiles can accurately render the same data that the old `SiteController` rendered.
4. No part of Teamora requires Laravel's `vendor` folder, Eloquent, or Blade to function.
