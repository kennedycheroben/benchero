# Task 10: Teams Management Verification

## 1. Architecture Decision
**Model A** was selected. Teams act as persistent, sport-specific squads (e.g., "Senior Men") independent of any individual season. This avoids recreating the core identity of the squad each year. Future season registration modules will link these persistent teams to specific seasons. Therefore, `season_id` is intentionally omitted from the `teams` table.

## 2. Files Changed
### Migrations
- `database/migrations/013_enhance_teams_table.php` (New)
### Core / Repositories
- `app/Repositories/TeamRepository.php` (New)
- `app/Services/TeamService.php` (New)
### Controllers & Routing
- `app/Controllers/Tenant/TeamController.php` (Modified)
- `config/routes.php` (Modified)
### Views
- `views/tenant/teams/index.php` (Modified)
- `views/tenant/teams/create.php` (New)
- `views/tenant/teams/edit.php` (New)
- `views/tenant/teams/show.php` (New)

## 3. Database Changes
Added to the existing `teams` table:
- `slug` (VARCHAR 100)
- `description` (TEXT)
- `team_type` (VARCHAR 100, flexible strings to support multiple sports)
- `is_active` (TINYINT 1)
- `display_order` (INT)
Dropped previous overly-broad unique slug index and added `uq_team_slug_per_org_sport` on `(organization_id, sport_id, slug)`.

## 4. Routes
- `GET  /o/{slug}/s/{sport_slug}/teams`
- `GET  /o/{slug}/s/{sport_slug}/teams/create`
- `POST /o/{slug}/s/{sport_slug}/teams`
- `GET  /o/{slug}/s/{sport_slug}/teams/{id}`
- `GET  /o/{slug}/s/{sport_slug}/teams/{id}/edit`
- `POST /o/{slug}/s/{sport_slug}/teams/{id}`
- `POST /o/{slug}/s/{sport_slug}/teams/{id}/delete`

## 5. Authorization Rules
- **Owner / Admin / Manager**: Full access to create, edit, and archive teams.
- **Other roles (Staff/Viewer)**: Read-only access enforced by `$this->requireManagerRole($request)` in `TeamController`.

## 6. Team Lifecycle
Teams are intended to persist for the lifetime of the club's participation in the sport.
- **Active**: `is_active = 1`
- **Archived**: `deleted_at` is set (Soft Delete), preserving historical records.

## 7. Season Relationship Decision
Teams do not belong to seasons. A future pivot (e.g., `season_teams` or `team_participations`) will handle tournament registrations if the product requires it.

## 8. Tests Executed
Script: `tests/run_task10_team_tests.php`

Tested the following invariants:
1. `Create Team`: Correct slug generation.
2. `Duplicate Slug Prevented`: Throws exception for duplicate slug in the same org/sport.
3. `Same Slug Different Sport`: Allows identical slugs (e.g. "Senior") across different sports in the same org.
4. `Same Slug Different Org`: Allows identical slugs across completely different orgs.
5. `IDOR Protection in Service`: Prevents updating a team that belongs to another organization by validating against the current `orgId` and `sportId`.
6. `Archive Team`: Soft deletes the team and sets `is_active = 0`.
7. `Archived Team Excluded`: Repository correctly hides archived teams from active listings.

## 9. Test Results
All 7/7 tests passed.

## 10. Security Findings
- **Cross-Tenant / IDOR Isolation**: The `TeamRepository` and `TeamService` actively require `organization_id` and `sport_id` for all operations, making it impossible to accidentally access another tenant's team via ID manipulation.
- **CSRF Protection**: Explicitly verified in the `TeamController` on all POST routes.
- **SQL Injection**: Prevented using prepared statements in `TeamRepository`.

## 11. Known Limitations
None. The module is built generically to support any future sports categories.
