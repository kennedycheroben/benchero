# Phase 3 Verification Report — Teamora

**Auditor:** Antigravity (independent, replacing previous agent)
**Date:** 2026-09-01
**Database dump timestamp:** 2026-08-31 20:02:16 (from `schema.sql`)
**Migration files modified:** 2026-08-31 19:55-19:57
**Scope:** Phases 0-3 (primary focus Phase 3)

---

## Executive Summary

```
Phase 3 — IMPLEMENTATION COMPLETE, VERIFICATION PARTIALLY FAILED
```

The previous agent created all 6 claimed migration files, and `bin/migrate.php` exists and
is syntactically correct. The `teamora_dev` database was created and populated (confirmed
via `mysqldump`-format `schema.sql` dated 2026-08-31 and the database directory existing
at `/opt/lampp/var/mysql/teamora_dev/`). All 6 migrations appear to have run successfully
(migrations table `AUTO_INCREMENT=7` confirms exactly 6 rows inserted).

**Phase 3 cannot be declared VERIFIED COMPLETE because:**

1. **No `docs/` directory exists** — none of the required architecture docs were created.
2. **No `PROJECT_STATUS.md`** — mandatory tracking file absent.
3. **No `task.md`** — mandatory task tracking file absent.
4. **No git repository** — no version history; changes cannot be verified between phases.
5. **MySQL is not currently running** — live queries could not be executed; `schema.sql`
   is the primary database evidence.
6. **Significant structural gaps** in the schema relative to the MVP entity list.
7. **2 CRITICAL financial integrity defects** in the existing schema.

Phase 2 (PHP foundation) is substantively present and functional.
Phase 3 is partially implemented — core tables exist but 6 critical MVP entities are absent
and 2 financial integrity defects are CRITICAL severity.

---

## Evidence

### Files Verified to Exist

| File | Size | Modified |
|---|---|---|
| `bin/migrate.php` | 2,447 bytes | 2026-08-31 19:57 |
| `database/migrations/001_create_organizations_table.php` | 596 bytes | 2026-08-31 19:55 |
| `database/migrations/002_create_users_table.php` | 642 bytes | 2026-08-31 19:55 |
| `database/migrations/003_create_organization_user_table.php` | 762 bytes | 2026-08-31 19:55 |
| `database/migrations/004_create_core_entities_tables.php` | 2,604 bytes | 2026-08-31 19:56 |
| `database/migrations/005_create_billing_tables.php` | 2,568 bytes | 2026-08-31 19:56 |
| `database/migrations/006_create_audit_logs_table.php` | 886 bytes | 2026-08-31 19:57 |
| `schema.sql` | 11,637 bytes | 2026-08-31 20:02 |

All 7 PHP files pass `/opt/lampp/bin/php -l` syntax check with **zero errors**.

### Database Evidence

`schema.sql` is a `mysqldump`-format output (`-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB`)
generated on `2026-08-31 20:02:16` from database `teamora_dev`. Timestamp sequence is
consistent: migration files created 19:55-19:57, database dumped at 20:02.

The `migrations` table in `schema.sql` shows `AUTO_INCREMENT=7`, confirming exactly 6
migration records — matching the 6 migration files claimed.

`/opt/lampp/var/mysql/teamora_dev/` exists in the MySQL data directory (confirmed by `ls`),
corroborating the database was created on disk.

MySQL/MariaDB is not currently running (stale PID file owned by another user prevents
startup without sudo). All database analysis relies on `schema.sql`.

---

## Architecture vs Implementation

> **CRITICAL NOTE:** No architecture documentation exists. The `docs/` directory did not
> exist and was created to hold this report. The "intended architecture" is inferred from
> the task description's stated requirements — not from actual design documents.

### Tables Present vs Absent

| Entity | Migration | schema.sql | Status |
|---|---|---|---|
| `organizations` | 001 | YES | Implemented |
| `users` | 002 | YES | Implemented — missing auth cols |
| `organization_user` | 003 | YES | Implemented |
| `sports` | 004 | YES | Implemented |
| `teams` | 004 | YES | Implemented — missing slug |
| `players` | 004 | YES | Implemented — missing identifier |
| `staff` | 004 | YES | Implemented |
| `plans` | 005 | YES | Implemented — missing interval |
| `subscriptions` | 005 | YES | Implemented — CASCADE defect |
| `payments` | 005 | YES | Implemented — CASCADE + no unique receipt |
| `audit_logs` | 006 | YES | Implemented |
| `migrations` | Runner | YES | Tracking table |
| `seasons` | MISSING | MISSING | **MISSING — required for MVP** |
| `competitions` | MISSING | MISSING | **MISSING — required for MVP** |
| `fixtures` | MISSING | MISSING | **MISSING — required for MVP** |
| `results` | MISSING | MISSING | **MISSING — required for MVP** |
| `team_player` | MISSING | MISSING | **MISSING — players cannot be rostered** |
| `team_staff` | MISSING | MISSING | **MISSING — staff cannot be assigned** |
| `policy_versions` | MISSING | MISSING | Deferred — Phase 8 |
| `policy_acceptances` | MISSING | MISSING | Deferred — Phase 8 |

### Migration Files vs schema.sql Discrepancies

**Migration 004 multi-statement exec():**
Migration 004 executes 4 `CREATE TABLE` statements in a single `$pdo->exec()` call.
With `PDO::ATTR_EMULATE_PREPARES => false`, this is unreliable across drivers. All 4 tables
appear in `schema.sql` because MariaDB 10.4 accepted it — but this is fragile and HIGH risk.

**ON DELETE RESTRICT normalization:**
Migrations 004 and 005 declare `ON DELETE RESTRICT` on some FKs. `schema.sql` shows these
without the explicit keyword (MariaDB normalizes RESTRICT as default). Functionally identical.

No other discrepancies between migration intent and `schema.sql` for tables that exist.

---

## Tenant Isolation Review

### Platform/Global Tables

| Table | Rationale |
|---|---|
| `users` | Platform-level accounts; org access via pivot |
| `sports` | Shared lookup; not org-specific |
| `plans` | Subscription tiers; platform-managed |
| `migrations` | Infrastructure |

### Organization/Tenant-Owned Tables

| Table | `organization_id` FK | Cascade | Status |
|---|---|---|---|
| `organization_user` | YES (`fk_ou_org`) | CASCADE | Correct |
| `teams` | YES (`fk_team_org`) | CASCADE | Correct |
| `players` | YES (`fk_player_org`) | CASCADE | Correct |
| `staff` | YES (`fk_staff_org`) | CASCADE | Correct |
| `subscriptions` | YES (`fk_sub_org`) | CASCADE | DEFECT |
| `payments` | YES (`fk_payment_org`) | CASCADE | CRITICAL DEFECT |
| `audit_logs` | YES (nullable) | SET NULL | Acceptable |

Organization-scoping via `organization_id` FK is present on all tenant-owned tables.
This is necessary but not sufficient — the application must enforce scoped queries on
every data access. The schema cannot substitute for application-level policy enforcement.

---

## Cross-Tenant Relationship Test

### Team to Player Cross-Tenant Risk

No `team_player` or roster pivot table exists. Teams and players exist independently under
their respective `organization_id`s but have no relationship in the database yet.

The cross-tenant scenario (Team from Org A assigned Player from Org B) cannot be violated
*or* prevented because the relationship table does not exist.

**When the roster pivot is added it MUST include:**
- `organization_id CHAR(26) NOT NULL` on the pivot itself
- `UNIQUE KEY (organization_id, team_id, player_id)`
- Application-layer policy verifying `team.organization_id = player.organization_id`
  before inserting (DB-level cross-table CHECK is not supported in MariaDB)

### Other Relationships

| Pair | Table | DB Protection |
|---|---|---|
| Team to Player | MISSING | Deferred |
| Team to Staff | MISSING | Deferred |
| Season to Org | MISSING | Deferred |
| Competition to Org | MISSING | Deferred |
| Fixture to Org | MISSING | Deferred |
| Subscription to Org | Exists | UNIQUE `organization_id` |
| Payment to Org | Exists | FK only |

---

## Database Integrity

### Primary Keys

| Table | PK Type | Correct |
|---|---|---|
| `organizations` | `CHAR(26)` | YES |
| `users` | `CHAR(26)` | YES |
| `organization_user` | Composite `(org_id, user_id)` CHAR(26) | YES |
| `sports` | `INT AUTO_INCREMENT` | YES (platform lookup) |
| `teams` | `CHAR(26)` | YES |
| `players` | `CHAR(26)` | YES |
| `staff` | `CHAR(26)` | YES |
| `plans` | `INT AUTO_INCREMENT` | MEDIUM risk (enumerable) |
| `subscriptions` | `CHAR(26)` | YES |
| `payments` | `CHAR(26)` | YES |
| `audit_logs` | `INT AUTO_INCREMENT` | Acceptable |
| `migrations` | `INT AUTO_INCREMENT` | Acceptable |

All FK columns referencing `CHAR(26)` PKs are themselves `CHAR(26)` — type consistency
is correct throughout.

### Foreign Keys

| Constraint | Parent | Child | ON DELETE |
|---|---|---|---|
| `fk_ou_org` | `organizations(id)` | `organization_user(organization_id)` | CASCADE |
| `fk_ou_user` | `users(id)` | `organization_user(user_id)` | CASCADE |
| `fk_team_org` | `organizations(id)` | `teams(organization_id)` | CASCADE |
| `fk_team_sport` | `sports(id)` | `teams(sport_id)` | RESTRICT |
| `fk_player_org` | `organizations(id)` | `players(organization_id)` | CASCADE |
| `fk_staff_org` | `organizations(id)` | `staff(organization_id)` | CASCADE |
| `fk_sub_org` | `organizations(id)` | `subscriptions(organization_id)` | **CASCADE** |
| `fk_sub_plan` | `plans(id)` | `subscriptions(plan_id)` | RESTRICT |
| `fk_payment_org` | `organizations(id)` | `payments(organization_id)` | **CASCADE** |
| `fk_payment_sub` | `subscriptions(id)` | `payments(subscription_id)` | SET NULL |
| `fk_audit_org` | `organizations(id)` | `audit_logs(organization_id)` | SET NULL |
| `fk_audit_user` | `users(id)` | `audit_logs(user_id)` | SET NULL |

**CRITICAL:** `payments` and `subscriptions` cascade-delete when an organization is deleted.
Financial records destroyed this way violate accounting integrity and regulatory requirements.

### Indexes

| Table | Present | Missing |
|---|---|---|
| `organizations` | PK, UNIQUE(slug) | Index on deleted_at |
| `users` | PK, UNIQUE(email) | — |
| `organization_user` | Composite PK, KEY(user_id) | — |
| `teams` | PK, KEY(org_id), KEY(sport_id) | No slug, no per-org name unique |
| `players` | PK, KEY(org_id) | No unique identifier |
| `staff` | PK, KEY(org_id) | — |
| `plans` | PK, UNIQUE(slug) | — |
| `subscriptions` | PK, UNIQUE(org_id), KEY(plan_id) | — |
| `payments` | PK, KEY(org_id), KEY(sub_id) | **UNIQUE on mpesa_receipt_number** |
| `audit_logs` | PK, KEY(org_id), KEY(user_id) | — |

### Unique Constraints

| Scope | Column(s) | Status |
|---|---|---|
| Global | `organizations.slug` | Enforced |
| Global | `users.email` | Enforced |
| Global | `plans.slug` | Enforced |
| Global | `sports.name`, `sports.slug` | Enforced |
| Per-org | `subscriptions.organization_id` | Enforced |
| Global | `payments.mpesa_receipt_number` | **MISSING — CRITICAL** |
| Per-org | Team slug within org | **MISSING** |
| Per-org | Player unique identifier | **MISSING** |

### Soft Delete

Soft delete (`deleted_at TIMESTAMP NULL`) present on: `organizations`, `users`, `teams`,
`players`, `staff`, `plans`.

Correctly absent on: `subscriptions` (uses status enum), `payments` (immutable financial
records), `audit_logs` (must never be deletable), `organization_user` (cascade handles it).

---

## ID / ULID Review

**Claimed:** CHAR(26) ULIDs for all entity PKs.
**Verified:** All relevant PK columns are `CHAR(26)`. All FK references are `CHAR(26)`.
Type and length consistency is correct throughout the schema.

**CRITICAL GAP:** `composer.json` includes `ramsey/uuid` (generates UUID4, 32 hex chars
without hyphens — NOT 26-char ULIDs). No ULID library is in `composer.json`. `app/Models/`
is completely empty. There is no ID generation code anywhere in the application.

The schema is structurally correct for ULIDs but **the application cannot generate them**.
Every entity creation in Phase 4+ will fail without a ULID generator.

**Collation:** All `CHAR(26)` columns use `utf8mb4_unicode_ci` (case-insensitive). ULIDs
are uppercase — functionally acceptable but `utf8mb4_bin` would be more correct. LOW risk.

---

## Billing Integrity

| Requirement | Status |
|---|---|
| Free trials | Supported (`status='trialing'`, `trial_ends_at`) |
| Monthly subscriptions | Partial — no `billing_interval` column |
| Annual subscriptions | Partial — no `billing_interval` column |
| Payment states | Supported (ENUM pending/completed/failed/refunded) |
| M-Pesa receipt references | Exists (`mpesa_receipt_number VARCHAR(100)`) |
| Duplicate callback prevention | **MISSING — no UNIQUE constraint** |
| Subscription/payment relationship | Supported (`payments.subscription_id FK`) |
| Plan archiving | Supported (`plans.deleted_at`) |
| Financial record protection | **CRITICAL DEFECT — payments CASCADE-deletes** |

No `billing_interval` column on `subscriptions` or `plans`. Monthly vs annual billing
is indistinguishable. Renewal date calculation and pricing tiers cannot be implemented
correctly. This blocks the billing MVP.

---

## Sports Model Review

The `Organization -> Sport -> Team` hierarchy:

```
organizations.id
  -> teams.organization_id  (FK, tenant scope)
  -> teams.sport_id         (FK, sport reference)
     -> sports.id           (platform lookup table)
```

The `sports` table has no football-specific columns. The design correctly supports multiple
sports (football, basketball, volleyball, rugby, athletics) without sport-specific DB
structures. This part is architecturally sound.

**However:** All sports operational tables are absent — `seasons`, `competitions`, `fixtures`,
`results`, `team_player`, `team_staff`. Players cannot be assigned to teams. The sports
management MVP cannot be built on the current schema without additional migrations.

---

## MVP Entity Review

| Entity | Status | Blocking Issues |
|---|---|---|
| organizations | Implemented | None |
| users | Implemented | Missing auth columns |
| organization_user | Implemented | None |
| sports | Implemented | None |
| teams | Implemented | No slug, no per-org uniqueness |
| players | Implemented | No identifier, no team assignment |
| staff | Implemented | No team assignment |
| plans | Implemented | No billing_interval |
| subscriptions | Implemented | No billing_interval, CASCADE defect |
| payments | Implemented | No unique receipt, CASCADE defect |
| audit_logs | Implemented | None |
| **seasons** | **MISSING** | Required for MVP |
| **competitions** | **MISSING** | Required for MVP |
| **fixtures** | **MISSING** | Required for MVP |
| **results** | **MISSING** | Required for MVP |
| **team_player** | **MISSING** | Players unrosterable |
| **team_staff** | **MISSING** | Staff unassignable |
| policy_versions | Deferred | Phase 8 |
| policy_acceptances | Deferred | Phase 8 |

---

## Migration Runner Review

**Positive:**
- Discovery: `glob('*.php')` — correct
- Ordering: Numeric filename prefix (001_, 002_...) — correct on Linux
- Tracking: `migrations` table — correct
- Duplicate prevention: `in_array()` check — correct
- Error handling: `try/catch` with `exit(1)` — correct
- Injection safety: Prepared statement for tracking insert — correct
- Idempotency: `CREATE TABLE IF NOT EXISTS` + tracking table — safe to re-run

**Issues:**

**HIGH — Multi-statement exec() in migration 004:**
4 `CREATE TABLE` statements in one `$pdo->exec()` call. Unreliable across PDO drivers.
Must be split into separate calls.

**MEDIUM — No glob() sort:**
`glob()` does not guarantee alphabetical order in the PHP spec. Add `sort($files)` after
`glob()`.

**MEDIUM — No transaction wrapping:**
DDL in MariaDB auto-commits. A partially-failed migration leaves the schema in an
inconsistent state. `IF NOT EXISTS` mitigates re-run failures but not all failure modes.

**LOW — No down() rollback:**
Migrations are one-way only. Acceptable for Phase 3 but limits recovery options.

---

## Phase 2 Sanity Check

| Component | Status |
|---|---|
| `app/bootstrap.php` | PASS |
| Composer autoload | PASS |
| `app/Core/Routing/Router.php` (FastRoute) | PASS |
| `app/Core/Http/Request.php` | PASS |
| `app/Core/Http/Response.php` | PASS |
| `app/Core/Middleware/Pipeline.php` | PASS |
| `app/Core/Database/Database.php` (PDO singleton) | PASS |
| `views/` (League/Plates) | PASS |
| `public/.htaccess` | PASS |
| `public/index.php` | PASS |
| `app/Middleware/CsrfMiddleware.php` | PASS |
| `app/Middleware/SessionMiddleware.php` | PASS |
| `app/Middleware/SecurityHeadersMiddleware.php` | PASS |
| `app/Middleware/AuthMiddleware.php` | PASS (stub) |
| `app/Middleware/TenantMiddleware.php` | STUB — pass-through |
| `config/routes.php` (2 routes) | PASS |

Phase 2 is functionally complete. `TenantMiddleware` is an acceptable documented stub.
`app/Models/` is empty — no model classes exist yet.

---

## Security Red Flags

### CRITICAL

**C1: `payments` ON DELETE CASCADE on organizations**
Deleting an organization destroys all payment records. Violates accounting integrity and
regulatory requirements. Must be changed to RESTRICT before any data is written.

**C2: No UNIQUE constraint on `payments.mpesa_receipt_number`**
M-Pesa retries duplicate callbacks. Without uniqueness, the same receipt can be stored
multiple times, creating fraudulent duplicate payment records.

**C3: `APP_KEY` is empty in `.env`**
Required for signed tokens and encryption in Phase 4. Must be populated before any
authentication code is written.

### HIGH

**H1: No ULID library in composer.json**
`ramsey/uuid` generates UUIDs (not ULIDs). `app/Models/` is empty — no ID generation
code exists. Every entity creation will fail without a ULID generator.

**H2: Multi-statement exec() in migration 004**
Fragile — will silently fail on certain PDO configurations or future DB versions.

**H3: Missing `users` authentication columns**
`email_verified_at`, `remember_token`, `last_login_at` absent. Phase 4 authentication
cannot be implemented cleanly without adding these via migration first.

**H4: No `billing_interval` on subscriptions**
Monthly vs annual billing indistinguishable. Renewal calculations will be incorrect.

**H5: Missing 6 MVP entity tables**
`seasons`, `competitions`, `fixtures`, `results`, `team_player`, `team_staff` are absent.
These are not deferred — they block the sports MVP.

**H6: `SESSION_SECURE_COOKIE=false` default in `.env.example`**
Insecure default propagated to all new environments.

### MEDIUM

**M1: `subscriptions` ON DELETE CASCADE on organizations**
Subscription history should be preserved for billing reconciliation.

**M2: Sequential INT PKs on `plans`**
Guessable plan IDs in URLs create enumeration risk.

**M3: No `teams.slug`**
Cannot build clean tenant-scoped team URLs. No per-org name uniqueness enforced.

**M4: No git repository**
Changes between phases cannot be verified or rolled back.

**M5: No architecture documentation**
Design decisions are not traceable. Audit compared against inferred requirements only.

**M6: `glob()` sort not guaranteed**

### LOW

**L1: `CHAR(26)` collation is `utf8mb4_unicode_ci`**
Case-insensitive comparison for ULID columns. Low risk in practice.

**L2: No `expose_php = Off`**
PHP version fingerprinting enabled by default.

---

## Recommended Fixes

### Mandatory before Phase 4 starts

**Fix 1 — Financial integrity migrations (migration 007):**
```sql
ALTER TABLE payments DROP FOREIGN KEY fk_payment_org;
ALTER TABLE payments ADD CONSTRAINT fk_payment_org
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT;

ALTER TABLE subscriptions DROP FOREIGN KEY fk_sub_org;
ALTER TABLE subscriptions ADD CONSTRAINT fk_sub_org
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT;

ALTER TABLE payments
    ADD UNIQUE KEY uq_mpesa_receipt (mpesa_receipt_number);

ALTER TABLE users
    ADD COLUMN email_verified_at TIMESTAMP NULL DEFAULT NULL AFTER email,
    ADD COLUMN remember_token CHAR(100) NULL DEFAULT NULL AFTER password_hash,
    ADD COLUMN last_login_at TIMESTAMP NULL DEFAULT NULL AFTER remember_token;

ALTER TABLE teams ADD COLUMN slug VARCHAR(100) NOT NULL DEFAULT '' AFTER name;
ALTER TABLE teams ADD UNIQUE KEY uq_team_slug_per_org (organization_id, slug);
```

**Fix 2 — APP_KEY:**
```bash
php -r "echo base64_encode(random_bytes(32)) . PHP_EOL;"
```
Add result to `.env` as `APP_KEY=base64:...`

**Fix 3 — Add ULID library:**
```bash
./composer.phar require robinvdvleuten/ulid
```

**Fix 4 — Fix migration 004:**
Split single `exec()` with 4 `CREATE TABLE` statements into 4 separate `exec()` calls.

**Fix 5 — Add sort() in bin/migrate.php:**
```php
$files = glob($migrationsDir . '/*.php');
sort($files);  // add this line
```

### Before Phase 5

- Add migrations for `seasons`, `competitions`, `fixtures`, `results`, `team_player`,
  `team_staff`
- Add `billing_interval ENUM('monthly', 'annual')` to `subscriptions` and `plans`
- Implement `TenantMiddleware`

### Before Production

- Set `SESSION_SECURE_COOKIE=true` in production
- Add `expose_php = Off` to php.ini
- Create all `docs/` architecture documents
- Initialize git repository

---

## Risks Ranked

| # | Risk | Severity |
|---|---|---|
| 1 | `payments` cascade-deletes with organization | CRITICAL |
| 2 | No UNIQUE on `mpesa_receipt_number` | CRITICAL |
| 3 | `APP_KEY` is empty | CRITICAL |
| 4 | No ULID library — ID generation impossible | HIGH |
| 5 | Multi-statement exec() in migration 004 | HIGH |
| 6 | Missing `email_verified_at` and auth columns | HIGH |
| 7 | No `billing_interval` column | HIGH |
| 8 | 6 MVP entity tables missing | HIGH |
| 9 | `SESSION_SECURE_COOKIE=false` default | HIGH |
| 10 | `subscriptions` CASCADE delete | MEDIUM |
| 11 | No `teams.slug` | MEDIUM |
| 12 | `glob()` sort not guaranteed | MEDIUM |
| 13 | Sequential INT PKs on `plans` | MEDIUM |
| 14 | No git repository | MEDIUM |
| 15 | No architecture documentation | MEDIUM |
| 16 | CHAR(26) case-insensitive collation | LOW |
| 17 | No `expose_php = Off` | LOW |

---

## Phase Verdicts

### Phase 2

```
Phase 2 — VERIFIED COMPLETE (with documented stubs)
```

PHP foundation is functional. All required components exist and pass syntax checks.

### Phase 3

```
Phase 3 — IMPLEMENTATION COMPLETE, VERIFICATION PARTIALLY FAILED
```

Core infrastructure tables exist in the database. Migration runner is functional and
idempotent. Phase 3 fails verification due to: 2 CRITICAL financial integrity defects,
6 required MVP entity tables absent, missing authentication-critical `users` columns,
no ULID generation capability, no architecture documentation, no project tracking files,
no git repository.

**The database is conditionally safe to build Phase 4 on**, provided that Fixes 1-5
above are implemented before Phase 4 code is written.

---

*Report generated by independent audit 2026-09-01. No application code was modified
during this audit. MySQL was not running; analysis based on schema.sql (mysqldump
dated 2026-08-31 20:02:16) and filesystem inspection.*
