# Database Design

## Overview
Teamora uses a relational database (MariaDB/MySQL) with a single-database, multi-tenant schema.

## Identifiers
- **Primary Keys:** We use ULIDs (Universally Unique Lexicographically Sortable Identifiers) formatted as `CHAR(26)`.
- **Why ULID?** Collision resistance across distributed systems, natural sorting by timestamp, and obfuscation of ID sequences. 

## Tenancy
- **Tenant ID:** `organization_id` acts as the tenant discriminator on all tenant-scoped tables (teams, players, staff, subscriptions, payments).

## Core Tables
- `users`: Authenticated individuals.
- `organizations`: Billing and tenant boundaries.
- `organization_user`: Pivot mapping users to organizations with roles.
- `sports`: Platform-level taxonomy (e.g., Football).
- `teams`: Scoped to `organization_id`, represents a playing squad.
- `players` & `staff`: People entities scoped to `organization_id`.

## Integrity Rules
- **Owned Entities** (teams, players, staff) use `ON DELETE CASCADE` to their parent organization, as they have no existence outside it.
- **Financial Entities** (subscriptions, payments) use `ON DELETE RESTRICT` to protect audit trails and billing history from accidental cascades.

## Migrations
Migrations are written in raw PHP/PDO, guaranteeing determinism via `sort()` and idempotency via `information_schema` checks.
