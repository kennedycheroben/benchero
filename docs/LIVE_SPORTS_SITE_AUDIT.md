# BENCHERO LIVE SPORTS SITE AUDIT REPORT

**Domain**: [https://benchero.co.ke](https://benchero.co.ke)  
**Date**: September 20, 2026  
**Auditor**: Benchero Engineering Team  

---

## 1. Executive Summary

An audit of the public sports platform on the live Benchero website (`https://benchero.co.ke`) was conducted to diagnose the unavailability of upcoming football fixtures, analyze stale live score indicators, and evaluate existing football results.

### Key Finding
The public website's database persistence layer is functioning correctly for finished matches (20 matches stored and displayed on `/sports/results`), but **upcoming match fixtures on `/sports/fixtures` were returning 0 items** despite valid fixture data existing in the `sports_matches` database table.

---

## 2. Page-by-Page Audit Matrix

| Page Tested | Expected Behavior | Actual Behavior | Suspected Cause | Evidence | Status |
|---|---|---|---|---|---|
| **`/sports`** (Homepage) | Render live scoreboard, recent results, upcoming fixtures, news, competitions | Displays live ticker, 6 news items, 6 recent results, but **Upcoming Fixtures list is empty**. Shows badge *"Scores may be temporarily delayed"*. | Status filter mismatch in `SportsService::getFixtures()` + stale sync timestamp (>120s since last live sync). | HTML source contains `<ul class="list-group list-group-flush"></ul>` for fixtures. DB has matches with `status = 'NS'`. Sync log last run was >25h ago. | **FAIL (Fixtures)** |
| **`/sports/results`** | Render recent finished match results | Renders 20 finished matches with scores, teams, timestamps, full-time badges. | Working as expected. DB queries `status IN ('FINISHED', 'FT', 'AET', 'PEN')`. | HTML renders Burnley 1-1 Derby, Everton 1-0 Ipswich, Brighton 3-0 Arsenal, etc. | **PASS** |
| **`/sports/fixtures`** | Render upcoming match fixtures | Displays card: **"No Fixtures Scheduled — No upcoming match fixtures match your current filter parameters."** | `SportsService::getFixtures()` queries `status IN ('SCHEDULED', 'TIMED', 'POSTPONED')`, but `FootballDataSportsProvider` normalized fixtures to `status = 'NS'`. | DB query for `'SCHEDULED'` yields 0 rows; DB query for `'NS'` yields 5 upcoming fixtures. | **FAIL** |
| **`/sports/live`** | Render active live matches | Displays live scoreboard header and live matches or delayed notice. | Query missing `'HT'` status; cron process hasn't run in >25 hours. | `sports_sync_logs` last `sync-live` entry timestamp is `2026-09-19 22:28:10`. | **PARTIAL** |
| **`/sports/competitions`** | Display list of competitions | Displays 13 competition cards (Bundesliga, Premier League, La Liga, Serie A, etc.). | Working as expected. | HTML renders cards with links to `/sports/c/{slug}`. | **PASS** |
| **`/sports/clubs`** | Display club directory | Displays registered Benchero clubs directory. | Working as expected. | HTML renders club cards correctly. | **PASS** |
| **`/sports/c/premier-league`** | Competition detail hub with standings, results, fixtures | Renders competition header, standings, results, but **fixtures section empty**. | Same status code mismatch (`'NS'` vs `'SCHEDULED'`). | Fixture array returned empty by `getFixtures()`. | **FAIL (Fixtures)** |

---

## 3. Detailed Root Cause Analysis

### Root Cause 1: Fixture Status Code Mismatch
- `FootballDataSportsProvider::normalizeMatch()` maps raw API status `SCHEDULED` or `TIMED` to `'NS'` (Not Started).
- `SportsSyncService::syncFixtures()` persists matches to `sports_matches` table with `status = 'NS'`.
- `SportsService::getFixtures()` executes:
  ```sql
  SELECT m.* ... FROM sports_matches m WHERE m.status IN ('SCHEDULED', 'TIMED', 'POSTPONED')
  ```
- Because `'NS'` was omitted from the SQL `IN` clause, `getFixtures()` returned an empty array `[]`.

### Root Cause 2: Halftime Status Code Mismatch
- `normalizeMatch()` maps raw `PAUSED` status to `'HT'` (Half Time).
- `SportsService::getLiveScores()` executed:
  ```sql
  SELECT m.* ... FROM sports_matches m WHERE m.status IN ('LIVE', 'IN_PLAY', 'PAUSED')
  ```
- Matches currently at halftime (`status = 'HT'`) were excluded from live score queries.

### Root Cause 3: Inactive Production Scheduler
- Inspection of `sports_sync_logs` revealed that the background sync CLI script (`bin/sports_sync.php`) has not executed since `2026-09-19 22:28:12`.
- `SportsService::getLiveScores()` marks data as stale (`is_stale = true`) if the last successful sync occurred more than 120 seconds ago.

---

## 4. Remediation Action Plan

1. **Standardize Status Code Queries**:
   Update `SportsService.php` to include `'NS'` in `getFixtures()` and `'HT'` in `getLiveScores()`.
2. **Standardize Sync Cleanup**:
   Update `SportsSyncService.php` to include `'HT'` when tracking and cleaning up active live matches.
3. **Regression Testing**:
   Execute `tests/run_sports_sync_regression_tests.php` and verify `/sports/fixtures` returns populated fixtures from database.
