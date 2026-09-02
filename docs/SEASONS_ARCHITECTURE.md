# Seasons Architecture

## Overview
Seasons in Teamora are strictly scoped by **Organization** and **Sport**. Unlike legacy implementations that may have attempted to manage global seasons or loosely coupled dates, this architecture guarantees tenant isolation.

## 1. Schema & Ownership
The `seasons` table relies on ULID primary keys. It is bound to:
- `organization_id`: Ensuring complete tenant data isolation.
- `sport_id`: Ensuring a season (e.g., 2025/2026) belongs uniquely to a sport context (e.g., Football).

A unique constraint `(organization_id, sport_id, slug)` guarantees that within the same sport and tenant, duplicate season slugs cannot be created.

## 2. Lifecycle
Instead of a complex, explicitly updated `status` ENUM (e.g., `upcoming`, `active`, `completed`), Teamora derives the season lifecycle from its properties:
- **Upcoming**: `starts_on` is in the future.
- **Completed**: `ends_on` is in the past.
- **Active**: Current date falls between `starts_on` and `ends_on`.
- **Archived**: Represented by a non-null `deleted_at` (soft delete pattern).

## 3. Current Season Invariant
Teamora ensures that there is only **one** "current" season per organization and sport at any given time.
Because MariaDB 10.4 lacks support for partial unique indexes (`WHERE is_current = 1`), this invariant is guaranteed through the service layer.

The `SeasonService` wraps the "Set Current" operation in a transaction:
1. `UPDATE seasons SET is_current = 0 WHERE organization_id = ? AND sport_id = ?`
2. `UPDATE seasons SET is_current = 1 WHERE id = ?`

## 4. Authorization
Season mutations (Create, Edit, Delete, Set Current) are restricted to `owner` and `admin` roles in the `organization_user` table. 

## 5. Separation of Concerns
- **SeasonRepository**: Handles all raw PDO operations. Requires `organization_id` and `sport_id` for almost all queries to enforce boundaries at the query level.
- **SeasonService**: Enforces business rules (slug generation, date validation, transaction wrapping, authorization checks).
- **SeasonController**: Validates HTTP requests, calls the service, and returns HTML Responses.
