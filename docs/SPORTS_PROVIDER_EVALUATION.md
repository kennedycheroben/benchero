# BENCHERO SPORTS PLATFORM — PROVIDER EVALUATION & ARCHITECTURE REPORT

## 1. Executive Summary

This document evaluates legitimate sports data and news provider APIs for the public **Benchero Sports Platform**. The objective is to transition from mock data to real production data streams across multiple sports (Football, Basketball, Rugby, Cricket, Tennis, Motorsport, and local Kenyan competitions) while maintaining complete provider independence, protecting API secrets, and strictly complying with third-party commercial licensing and copyright law.

---

## 2. Sports Data Provider Comparison

| Provider | Leagues & Sports Covered | Live Scores | Fixtures & Results | Standings | Kenyan / Regional Coverage | Free Dev Tier | Commercial Production Pricing | Commercial Usage Rights | Evaluation & Verdict |
|---|---|---|---|---|---|---|---|---|---|
| **Football-Data.org (v4)** | European Football (Premier League, Champions League, La Liga, Serie A, Bundesliga, World Cup) | Yes | Yes | Yes | None / Not supported | 10 req/min | €19–€199 / month | Permitted on paid plans | **Selected Initial Provider for European Football** |
| **Sportmonks (v3)** | Global Football (2,000+ leagues), Basketball, Cricket | Yes (Websockets + REST) | Yes | Yes | Yes (FKF Premier League, CAF) | 1,000 req/day | €29–€249 / month | Permitted on paid plans | **Selected Multi-Sport & Regional Provider Target** |
| **API-Football (RapidAPI)** | 1,000+ football competitions worldwide | Yes | Yes | Yes | Partial (FKF KPL) | 100 req/day | $19–$150 / month | Permitted on paid plans | **Alternative Backup Provider** |
| **TheSportsDB** | Multi-sport (Football, Basketball, Rugby, F1) | Limited | Yes | Yes | Community driven | 2 req/sec | $5–$25 / month | Permitted | **Supplementary Metadata** |

### Multi-Provider Architecture
Benchero Sports uses a **Provider Adapter System** where `SportsService` delegates data loading to one or more provider adapters without leaking provider details to Controllers or Views:
```text
                          SportsService
                                │
                     SportsProviderInterface
                                │
         ┌──────────────────────┼──────────────────────┐
         │                      │                      │
FootballDataSportsProvider  SportmonksProvider  RegionalSportsProvider
(European Football)          (Global & Basketball)  (Kenyan / Local Sports)
```
- `FootballDataSportsProvider`: Connects to Football-Data.org API v4 (`X-Auth-Token` header) for Premier League, Champions League, La Liga, and Serie A.
- `RegionalSportsProvider`: Standalone adapter for local Kenyan sports APIs or custom regional feeds.
- `MockSportsProvider`: Preserved for local offline testing and automated unit tests (`APP_ENV=development`).

---

## 3. News Provider Evaluation & Copyright Compliance

| Source | Coverage | Headlines & Summaries | Source Attribution | Image Policy | Commercial Terms & Licensing | Compliance Model |
|---|---|---|---|---|---|---|
| **RSS / Atom Aggregator** (BBC Sport, Sky Sports, Goal, Standard Sports Kenya) | Global & Local Kenyan Sports | Yes | Mandatory canonical source link | Safe fallback placeholders (No unauthorized rehosting of third-party images) | Public RSS aggregation complying with publisher feed terms | **Primary Production News Model** |
| **NewsAPI.org** | Global Sports Media | Yes | Mandatory publisher link | Provider thumbnails | $449/mo (Commercial license required) | **Optional API Adapter** |

### Copyright & Licensing Compliance Statement
> **Copyright & Licensing Policy**: The implementation is designed to minimize copyright risk by storing headlines, permitted short summaries, publication metadata, attribution, and canonical source URLs rather than reproducing complete articles. Production use must comply with each feed publisher's terms, API/license terms, copyright requirements, and applicable law.

- **Image Rehosting Rule**: RSS image URLs are not rehosted or mirrored to disk without explicit commercial permission. Rendered cards use safe default fallback badges and local category icons.
- **HTML Sanitization Rule**: All external news titles and summaries are stripped of raw HTML tags and sanitized using `htmlspecialchars()` / `$this->e()` output escaping to prevent XSS or script injection.

---

## 4. Production Safeguards & Error Fail-Safe

1. **Environment Strictness**:
   If `APP_ENV=production` and `SPORTS_PROVIDER=real` is configured:
   - If required API keys (`FOOTBALL_DATA_API_KEY`) are missing or invalid, Benchero logs a system configuration error in `sports_sync_logs` and returns a safe application response ("Live scores temporarily delayed") using cached data.
   - The application **NEVER** falls back to mock data silently in production.
2. **Cron Locking (`flock()`)**:
   `bin/sports_sync.php` acquires an exclusive lock on `storage/locks/sports_sync.lock` using `flock()` to prevent concurrent cron process collisions.
3. **Database Idempotency**:
   Unique index constraint `UNIQUE KEY (provider, external_id)` on `sports_matches`, `sports_competitions`, `sports_teams`, and `sports_news` prevents duplicate insertions.
