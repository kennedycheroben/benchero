# Players & Roster Architecture

## Overview
Teamora implements **Model A** for player and roster management to ensure that player identities remain stable across seasons and multiple team participations, while supporting full tenant and sport isolation.

## 1. Player Identity Model (`players` table)
- **Scope**: A player belongs directly to an `organization_id` and a `sport_id`. This means a single physical person playing both Football and Basketball for the same organization will have two separate player records, allowing sport-specific stats/profiles later without cross-contamination.
- **Identity Fields**: `first_name`, `last_name`, `display_name`, `bio`, `date_of_birth` (optional).
- **Data Minimization**: We explicitly do not store nationality, phone numbers, or addresses. `date_of_birth` is nullable and not strictly required by the UI unless specific league rules demand it.

## 2. Roster Assignment Model (`roster_assignments` table)
- **Scope**: A roster assignment is the junction entity connecting a `player_id`, `team_id`, and `season_id`.
- **Tenant Integrity**: Includes `organization_id` and `sport_id` directly on the assignment record. This makes cross-tenant IDOR attacks practically impossible at the database level because queries enforce the tenant context automatically without complex joins.
- **Jersey Numbers & Positions**: These live on the `roster_assignments` table, NOT the `players` table. A player can change jersey numbers and positions across different seasons and teams.
- **Positions**: Instead of a strict enum, `position` is a generic `VARCHAR` to support multiple sports natively. 

## 3. Historical Data & Archival Strategy
- **Soft Deletes**: Both `players` and `roster_assignments` support soft deletion (`deleted_at`). 
- When a player leaves a team, the `roster_assignment` is soft-deleted (archived). When a player leaves the organization, the `player` record is soft-deleted. 
- **Fixtures & Results Integrity**: Because we soft-delete rather than hard-delete, any future fixtures or match events that reference a player or a roster assignment will remain perfectly intact.

## 4. Authorization & Security
- Only `owner`, `admin`, and `manager` roles can mutate players or roster assignments.
- `staff` and `viewer` roles have read-only access.
- **IDOR Protection**: Every single repository query forces `organization_id` and `sport_id` matching, rejecting mismatched client-provided IDs.

## 5. Constraint Enforcement
- `uq_roster_assignment (player_id, team_id, season_id)`: Prevents a player from being assigned to the same team in the same season more than once.
