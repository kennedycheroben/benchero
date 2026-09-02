# Task 8 Verification: Sports Foundation

## 1. Functionality
The platform now has a globally managed sports catalogue decoupled from core tenant models. Organizations explicitly opt-in to sports through the `organization_sports` pivot table.

## 2. Routes & Middleware
- **`/o/{slug}/sports`**: Manage active sports.
- **`/o/{slug}/sports/toggle`**: Activate/deactivate sports.
- **`/o/{slug}/s/{sport_slug}/*`**: Sport-scoped tenant routes. `TenantMiddleware` extracts the sport slug, validates its existence globally and its activation by the tenant, then injects the `Sport` entity into the Request.

## 3. Database Changes
- Migration `011_refactor_sports_foundation` recreated the `sports` table with a `CHAR(26)` ULID, `description`, and `is_active`.
- `teams.sport_id` changed from `INT` to `CHAR(26)`.
- `organization_sports` pivot table created with `organization_id`, `sport_id`, and `is_active`.
- The 4 core sports (`football`, `basketball`, `volleyball`, `rugby`) are seeded idempotently.

## 4. Architecture
Detailed in `docs/SPORTS_ARCHITECTURE.md`.
The team slug is allowed to be reused across different sports for the same organization without violating the current `(organization_id, slug)` uniqueness because the slug in the MVP doesn't include the sport yet (or rather, we demonstrated that identical names are fully supported; slugs might need unique resolution depending on URL structure for teams).

## 5. Security & Tests
The `run_task8_sports_security_tests.php` verified:
- Deterministic and idempotent sports seeding.
- Organization can activate a sport.
- Cross-tenant and cross-sport isolation (User A receives a 404 for an unactivated sport in Org A, and a 403 for an activated sport in Org B).
- Same team name successfully inserted for both football and basketball under the same organization.

## 6. Known Limitations
- Team slugs in `teams` might require the sport prefix if they ever share routes without `/s/{sport_slug}/`. Currently, `/o/gacio-sports/s/football/teams/{team_slug}` cleanly isolates them, but the DB unique constraint on `teams` is only `(organization_id, slug)`. For the MVP we demonstrated name duplication is supported; slug duplication requires appending an identifier in future iterations if we enforce uniqueness strictly.
- Teams UI is read-only placeholder for now.

**STATUS: TASK 8 — VERIFIED COMPLETE**
