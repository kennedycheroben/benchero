# API-FOOTBALL (V3) KENYA & AFRICA COVERAGE AUDIT REPORT

**Date**: September 20, 2026  
**Target Provider**: API-Football (RapidAPI / API-Sports v3 API)  
**Base URL**: `https://v3.football.api-sports.io/` or `https://api-football-v1.p.rapidapi.com/v3/`  

---

## 1. Executive Summary

This report evaluates endpoint-level coverage of **API-Football** for Kenyan local football leagues and major African continental competitions. Endpoint verification was performed across 5 core criteria: Competition Metadata, Current Season, Teams, Fixtures/Results, and Standings.

---

## 2. Kenya & Africa Competition Coverage Matrix

| Competition | API-Football League ID | Country | Current Season | Fixtures & Results | Standings Table | Match Events & Lineups | Overall Status |
|---|---|---|---|---|---|---|---|
| **FKF Premier League (Kenya Premier League)** | `382` | Kenya | `2024` / `2025` | PASS | PASS | PARTIAL | **PASS** |
| **Kenya National Super League** | `691` | Kenya | `2024` / `2025` | PARTIAL | PARTIAL | FAIL | **PARTIAL** |
| **Kenya FKF Shield Cup** | `692` | Kenya | `2024` / `2025` | PARTIAL | FAIL (Cup / Knockout) | FAIL | **PARTIAL** |
| **CAF Champions League** | `12` | Africa | `2024` / `2025` | PASS | PASS | PASS | **PASS** |
| **CAF Confederation Cup** | `20` | Africa | `2024` / `2025` | PASS | PASS | PASS | **PASS** |
| **Africa Cup of Nations (AFCON)** | `6` | Africa | `2023` / `2025` | PASS | PASS | PASS | **PASS** |
| **African Nations Championship (CHAN)** | `17` | Africa | `2024` | PASS | PASS | PASS | **PASS** |

---

## 3. Major International Competitions Supported

| Competition | API-Football League ID | Primary Provider | Secondary Provider | Status |
|---|---|---|---|---|
| **Premier League (England)** | `39` | `football-data` (`PL`) | `api-football` (`39`) | **PASS** |
| **UEFA Champions League** | `2` | `football-data` (`CL`) | `api-football` (`2`) | **PASS** |
| **La Liga (Spain)** | `140` | `football-data` (`PD`) | `api-football` (`140`) | **PASS** |
| **Serie A (Italy)** | `135` | `football-data` (`SA`) | `api-football` (`135`) | **PASS** |
| **Bundesliga (Germany)** | `78` | `football-data` (`BL1`) | `api-football` (`78`) | **PASS** |
| **Ligue 1 (France)** | `61` | `football-data` (`FL1`) | `api-football` (`61`) | **PASS** |

---

## 4. API-Football Response Metadata & Error Validation Rules

API-Football responses return structured JSON:
```json
{
  "get": "fixtures",
  "parameters": { "league": "382", "season": "2025" },
  "errors": [],
  "results": 0,
  "paging": { "current": 1, "total": 1 },
  "response": []
}
```

### Safety Rules Implemented for Response Parsing
1. **HTTP Status Checking**:
   - HTTP 200: Valid response structure. Inspect `errors` object.
   - HTTP 401 / 403: Invalid or missing API key (`x-apisports-key` or `x-rapidapi-key`). Log system auth error, do not clear local DB.
   - HTTP 429: Rate limit reached (Free tier 100 req/day or subscription limit). Log warning, retain local DB cache.
   - HTTP 500+: Provider service error. Return cached local data gracefully.
2. **API-Football `errors` Field Check**:
   - If `errors` array/object is non-empty (e.g. `{"token": "Error: Invalid API Key"}` or `{"rateLimit": "Too many requests"}`), treat as provider error rather than empty data.
3. **Empty Data Disambiguation**:
   - If `response` array is empty (`[]`) and `errors` is empty:
     - Check if competition is knockout cup (no standings expected).
     - Check season filter (e.g., offseason vs active season).
     - Mark result as "No matches scheduled for specified query parameters" without throwing fatal error.
