# Fixtures Architecture

## 1. Domain Definition
The Fixtures Foundation represents the generic scheduling of sporting events between two participating teams within a season. It serves as the authoritative source of truth for **when** and **where** an event happens, but it does NOT store sport-specific scoring (e.g., goals, points) or outcomes (winner/loser). Those belong to a future `Result` module that will link back to a completed fixture.

## 2. Participant Model Decision
For the MVP, we explicitly define `home_team_id` and `away_team_id` on the `fixtures` table.
*Reasoning*: Teamora's primary target sports (Football, Basketball, Volleyball, Rugby) are exclusively two-team events. A generic `fixture_participants` many-to-many relationship would introduce premature complexity and significant JOIN overhead for 99% of all queries. If the platform expands to multi-participant sports (e.g., athletics, swimming) in the future, we can migrate to a `fixture_participants` table while retaining `home/away` columns as a fast-path for traditional team sports.

## 3. Season Relationship
Every fixture rigidly belongs to a specific `season_id`. The season context naturally limits which teams should be involved, and aligns with the organizational boundaries.

## 4. Competition & Friendly Context
The fixture model accommodates standard leagues, cups, tournaments, and friendlies using `competition_type` and `competition_name` strings. This enables friendly matches to be fully-fledged fixtures without requiring a separate feature or complicated parent-child hierarchy.

## 5. Status Lifecycle
Fixtures transition through a strict lifecycle:
- `scheduled` -> The default state. Modifiable.
- `postponed` -> The event is delayed. Core details locked, but time/date can be rescheduled.
- `completed` -> The event finished. Locked state (prepares for Result attachment).
- `cancelled` -> The event is aborted permanently.

## 6. Timezone Strategy
- **Storage**: The `scheduled_at` timestamp is always stored in UTC in the database.
- **Display**: It is converted to the organization's local timezone (via the `organizations.timezone` setting) on output. This prevents daylight saving time anomalies.

## 7. Tenant Isolation
A fixture requires `organization_id` and `sport_id`. 
The `FixtureService` enforces that the `home_team`, `away_team`, and `season` provided all belong to the same `organization_id` and `sport_id` as the fixture being created. No fixture can cross tenant or sport boundaries.

## 8. Authorization
- **Owner, Admin, Manager**: Full creation, modification, and status transition control.
- **Staff, Viewer**: Read-only access to view schedules.

## 9. Future Readiness
The fixture table contains no sport-specific terminology (no halves, quarters, goals, sets, or innings). Future scoring plugins (like `FootballResult` or `BasketballResult`) will attach to `fixture_id` via a 1:1 or 1:N relationship, preserving the generic scheduling core.

## Historical Data Safety (Task 12.1)
* **Soft Deletion**: Fixtures are soft-deleted using the `deleted_at` timestamp rather than hard-deleted.
* **Query Exclusion**: Normal queries (`findBySeason`, `findById`) explicitly exclude rows where `deleted_at IS NOT NULL`.
* **Historical Identity**: Fixture records are retained physically in the database to ensure future `Results` entities can reference historical fixtures without dangling foreign keys.
* **Deletion Pattern**: Hard deletion (`DELETE FROM fixtures`) is strictly forbidden for normal application flows.

## Public Architecture (Task 12.1)
* **Authenticated Boundaries**: Tenant administration routes (`/o/{slug}/...`) remain completely protected by `TenantMiddleware`. No anonymous exceptions exist.
* **Public Separation**: Public routes (e.g. `/{org_slug}/{sport_slug}/fixtures`) operate on completely separate handlers (`Public\FixtureController`).
* **Tenant Isolation**: Public queries explicitly scope by `organization_id` and `sport_id` to prevent cross-tenant data leakage.
* **Data Limits**: The public responses expose only information necessary for a public club schedule. Private identifiers, audit logs, and internal notes are omitted from public interfaces.
