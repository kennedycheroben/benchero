# Teams Architecture

## Overview
The Teams module implements **Model A**: Teams are persistent, sport-specific squads (e.g., "Senior Men") that exist independently of specific seasons. This prevents the duplication of core squad entities year over year. Future features will link these persistent teams to seasons via a participation model.

## 1. Schema & Ownership
The `teams` table relies on ULID primary keys. It is bound to:
- `organization_id`: Ensures complete tenant data isolation.
- `sport_id`: Ensures a team belongs to a specific sport context.

A unique constraint `(organization_id, sport_id, slug)` guarantees that within the same sport and tenant, duplicate team slugs cannot exist.
A team can share a slug across different sports in the same organization (e.g., a "Senior Men" Football team and a "Senior Men" Basketball team).

## 2. Team Types
Team types (e.g., "Senior", "U19", "Reserves") are stored as flexible strings (`VARCHAR`) rather than strict database ENUMs. This allows cross-sport flexibility (since Basketball categories may differ from Football categories) without requiring constant schema migrations.

## 3. Authorization
Team mutations (Create, Edit, Delete) are restricted to `owner`, `admin`, and `manager` roles in the `organization_user` table. 
`staff` and `viewer` roles have read-only access.

## 4. Deletion Strategy
Teams use a **soft deletion** strategy (`deleted_at`). This ensures that if a team is "deleted", it is merely hidden from the active management UI but its historical data (fixtures, results, player histories) remains resolvable.

## 5. Separation of Concerns
- **TeamRepository**: Handles all raw PDO operations. Requires `organization_id` and `sport_id` for almost all queries to enforce boundaries at the query level.
- **TeamService**: Enforces business rules (slug generation, slug conflict resolution, validation).
- **TeamController**: Validates HTTP requests, calls the service, and handles rendering views.

## 6. Future Expansion
The team overview (`show` route) establishes the layout foundation for upcoming modules:
- Players
- Staff
- Fixtures
- Results
- Statistics
These will be built in subsequent tasks but will be anchored visually to the Team Overview page.
