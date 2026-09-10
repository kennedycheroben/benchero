# BENCHERO — Performance, Security, & SEO Audit Report

**Application Name:** Benchero  
**Production Domain:** `https://benchero.co.ke`  
**Environment:** Traditional PHP / MySQL (MariaDB) / Apache (cPanel/LAMPP compatible)  
**Audit Date:** September 2026  

---

## 1. System Architecture Overview

Benchero is a multi-tenant sports organization management platform implemented in custom OOP PHP (PHP 8.x compatible) without heavy framework overhead (such as Laravel).

- **Routing:** Router-based routing configured in `config/routes.php` dispatching to Controllers.
- **Middleware:** Pipeline pattern (`Benchero\Core\Middleware\Pipeline`) executing global and route-level middlewares:
  - `SecurityHeadersMiddleware`
  - `SessionMiddleware`
  - `CsrfMiddleware`
  - `TenantMiddleware`
  - `AuthMiddleware`
- **Database Layer:** PDO wrapper in `Benchero\Core\Database\Database` using ULID primary keys for tenant entities and standard foreign key constraints.
- **Tenancy Architecture:** Multi-tenant single-database isolation using `organization_id` column on all tenant-owned entities.
- **Subscription Architecture:** Centralized subscription status checking in `SubscriptionService.php` enforcing trial periods, monthly/yearly billing intervals, and public club website visibility toggling.

---

## 2. Full System Audit Findings

### 2.1 HTTPS & Security Headers
- **Current State:** `.env` specifies `APP_URL=https://benchero.co.ke` and `APP_HSTS_ENABLED=true`.
- **Finding:** `public/index.php` contains explicit `ini_set("display_errors", 1);` which forces error display even when `APP_DEBUG=false` in `.env`.
- **HSTS / HTTPS Finding:** HTTP to HTTPS redirection was not enforced at the application middleware/Apache level, allowing potential mixed-content or HTTP fallback.
- **Recommendation:** Add automatic HTTP -> HTTPS redirection in production within `SecurityHeadersMiddleware.php` and `.htaccess`, and emit `Strict-Transport-Security: max-age=31536000; includeSubDomains` dynamically when HTTPS is active.

### 2.2 Caching Strategy & Isolated Keys
- **Current State:** No application-level data caching or browser HTTP cache-control strategy was active.
- **Finding:** Public club website requests query database repeatedly for standing tables, rosters, fixtures, and news articles on every page render.
- **Risk:** High database CPU load during traffic spikes. Additionally, generic cache keys risk cross-tenant data leaks if not strictly prefixed with `organization_id`.
- **Recommendation:** Implement a filesystem-backed `CacheService` (`storage/cache/`) with tenant-isolated cache keys (e.g. `public_club:{organization_id}:home`) and explicit Cache-Control headers (`public, max-age=31536000, immutable` for versioned assets; `no-store` for authenticated routes).

### 2.3 Session & Cookie Security
- **Current State:** PHP sessions managed via standard file driver. Session cookies configured via `SessionMiddleware`.
- **Finding:** Cookie attributes need strict default enforcement: `Secure=true` in production, `HttpOnly=true`, and `SameSite=Lax`. Session IDs are regenerated on login, but need to be regenerated on role/privilege changes as well.
- **Recommendation:** Harden session cookie params across the application.

### 2.4 Rate Limiting & API Protection
- **Current State:** `RateLimiter` class uses a database table (`rate_limits`) with atomic UPSERT. Currently applied only to login and registration in `AuthController.php`.
- **Finding:** Endpoint-specific rate limits were missing for resend email verification, password reset, file uploads, contact forms, and M-Pesa STK push.
- **M-Pesa Callback Finding:** Payment callbacks from Safaricom must NOT be blocked by standard IP rate limiters, but must be protected via passkey/token validation, transaction reference check, idempotency, and logging.

### 2.5 Image Processing & Logo Uploads
- **Current State:** Club owners were entering logo image URLs as text input rather than uploading files!
- **Finding:** Relying on external image URLs allows broken image links, unsafe HTTP content, and lacks server-side image validation.
- **Recommendation:** Replace URL text inputs with multipart file upload (`<input type="file" name="logo">`). Implement strict server-side validation: max 2MB (2,097,152 bytes), MIME type checking via `finfo_file()` (`image/png`, `image/jpeg`, `image/webp`), `getimagesize()` verification, secure random ULID filenames, safe directory storage, and automatic cache invalidation.

### 2.6 Email Deliverability & Domain Authentication
- **Current State:** `SmtpMailer` sends emails using SMTP host `lon105.truehost.cloud` on port 465 SSL with account `contact@benchero.co.ke`.
- **Finding:** Some registration emails landed in spam due to missing domain authentication documentation (SPF, DKIM, DMARC) and unformatted email body templates.
- **Recommendation:** Update email templates with clean HTML & plain-text layouts. Document precise SPF, DKIM, and DMARC DNS settings for `benchero.co.ke` in `docs/BENCHERO_EMAIL_DELIVERABILITY.md`.

### 2.7 SEO, Sitemap, Robots.txt & Structured Data
- **Current State:** Public club pages and main SaaS pages used static title tags. No dynamic sitemap or robots.txt route was provided.
- **Finding:** Missing meta tags, OpenGraph social sharing tags, canonical URLs, and Schema.org JSON-LD structured data.
- **Recommendation:** Implement dynamic SEO title/meta/OG generation for Benchero and client club websites, add `/sitemap.xml` and `/robots.txt` handlers, and add Schema.org (`Organization`, `WebSite`, `SportsOrganization`) JSON-LD scripts.

### 2.8 About Page & User Experience
- **Current State:** `/about` page contained placeholder text or developer-centric terminology.
- **Recommendation:** Overhaul `/about` into an engaging, non-technical page structured in 10 clear sections targeted at sports club owners, managers, coaches, players, and supporters.

---

## 3. Recommended Implementation Strategy

1. **Phase 2 & 3:** Enforce HTTPS and HSTS across `SecurityHeadersMiddleware.php` and `.htaccess`.
2. **Phase 4 & 5:** Build `CacheService.php` with tenant isolation; configure static asset caching and Apache `mod_deflate` compression.
3. **Phase 6:** Add database indexes via migration `022_add_performance_indexes.php`.
4. **Phase 7:** Apply per-endpoint rate limits and secure M-Pesa callback handling.
5. **Phase 8 & 9:** Secure session cookies (`Secure`, `HttpOnly`, `SameSite=Lax`), create `/cookies` page and cookie consent banner.
6. **Phase 10 - 14:** Add dynamic SEO meta, `/sitemap.xml`, `/robots.txt`, and Schema.org JSON-LD.
7. **Phase 15:** Redesign `/about` page with 10 non-technical sections.
8. **Phase 16 & 17:** Polish client white-labeling, mobile navbar responsiveness, and keyboard accessibility.
9. **Phase 18 - 21:** Convert logo URL fields to secure file uploads with server-side validation.
10. **Phase 22 - 25:** Refine email templates and create deliverability guide.
11. **Phase 26 - 36:** Final QA, security checks, test suite verification, and comprehensive documentation.
