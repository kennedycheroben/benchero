# Benchero Custom Domains Architecture & Security Model

## 1. Executive Summary & Architectural Scope

Benchero provides a secure, multi-tenant digital identity platform for sports organizations. The custom domain system allows organizations on the **Benchero Pro** subscription tier to connect branded hostnames (e.g. `cheetahsfc.co.ke`, `www.rhinosrfc.com`, `club.athletics.org`) directly to their public club website without duplicating code or views.

### Architectural Boundary: Application vs. Infrastructure

A critical requirement of the Benchero system is maintaining a strict separation between **Application-Level Logic** and **Hosting / Infrastructure Requirements**:

| Component | Responsibility / Status | Technical Mechanism |
| :--- | :--- | :--- |
| **Domain Registration & Normalization** | **APPLICATION IMPLEMENTED** | `DomainValidator`: RFC 1035/1123, lowercase, trailing dot removal, strict URL/path/IP rejection |
| **Ownership Verification** | **APPLICATION IMPLEMENTED** | `DomainVerificationService`: 48-hex CSPRNG token via DNS TXT `_benchero-verification.<domain>` |
| **Global Uniqueness** | **APPLICATION IMPLEMENTED** | Unique index on `normalized_domain` in MySQL `custom_domains` table |
| **Tenant Resolution** | **APPLICATION IMPLEMENTED** | `TenantResolver`: Authoritative `HTTP Host` lookup to verified active organization record |
| **Cross-Tenant Isolation** | **APPLICATION IMPLEMENTED** | `TenantMiddleware`: Absolute enforcement, rejects foreign club slugs with HTTP 404 |
| **Authentication Separation** | **APPLICATION IMPLEMENTED** | `TenantMiddleware`: 302 redirects `/login`, `/register`, `/admin`, `/o/*` to canonical `APP_URL` |
| **Public Site Serving** | **APPLICATION IMPLEMENTED** | Transparent request rewriting to existing `PublicClubController` and theme engine |
| **URL & Canonical SEO Generation** | **APPLICATION IMPLEMENTED** | `club_url()` helper and `SeoService` generate clean root-relative or custom domain links |
| **Pro Entitlement Enforcement** | **APPLICATION IMPLEMENTED** | `EntitlementService` and `SubscriptionService` block non-Pro and display 402 lock on expired subs |
| **Domain Replacement & Removal** | **APPLICATION IMPLEMENTED** | Zero-downtime staged transition, primary domain preservation, auditable removal |
| **Honest SSL/TLS Verification** | **APPLICATION IMPLEMENTED** | `DomainService::checkSslStatus()`: Live TCP socket handshake & X.509 cert parse; **No fake SSL** |
| **Rate Limiting & Audit Trail** | **APPLICATION IMPLEMENTED** | `RateLimiter` on verification/updates; centralized audit logs in `audit_logs` table |
| **DNS Traffic Ingress** | **INFRASTRUCTURE REQUIRED** | Customer points CNAME / A records to Benchero ingress |
| **Dynamic SNI SSL Issuance** | **INFRASTRUCTURE REQUIRED** | External ingress layer (Cloudflare for SaaS, cPanel Parked Domain UAPI, or Caddy Reverse Proxy) |

---

## 2. Domain Lifecycle

The application separates DNS ownership, traffic routing, tenant activation, and SSL status into distinct lifecycle stages rather than collapsing them into a single boolean:

```
[Customer Registers Domain]
            │
            ▼
    Status: PENDING
    Verification: PENDING  ───►  [Customer adds TXT Record]
    Activation: PENDING                       │
    SSL: NOT CONFIGURED                       ▼
            │                         [Verify Ownership]
            │                                 │
            ▼                                 ▼
[Ownership Verified]  ◄──────────────── Status: VERIFIED
            │
            ▼
  [Activate Domain]
            │
            ▼
    Status: ACTIVE
    Verification: VERIFIED
    Activation: ACTIVE ──────► [TenantResolver Serves Public Site]
            │                               │
            │                               ▼
            │                     [Check Real SSL/TLS]
            │                               │
            ▼                               ▼
    [Deactivate / Replace]         SSL: ACTIVE | FAILED
```

### Lifecycle States

1. **`pending`**: Domain registered; cryptographic verification token issued; awaiting DNS TXT propagation.
2. **`verified`**: Cryptographic DNS TXT token confirmed matching `benchero-verification=<token>`. Domain ownership is proven.
3. **`active`**: Domain enabled for tenant traffic routing. Requests arriving with this `HTTP Host` will serve the public club website.
4. **`disabled`**: Domain is verified, but administrator or customer has temporarily disabled public traffic routing.
5. **SSL Lifecycle**:
   - `not_configured`: DNS has not yet reached or resolved on the host.
   - `pending`: TLS handshake failed or certificate is self-signed/untrusted (awaiting issuance).
   - `active`: Valid, trusted TLS certificate successfully verified over port 443 matching the hostname.
   - `failed`: TCP timeout, host unreachable, or certificate domain mismatch.

---

## 3. Domain Validation (`DomainValidator`)

The `DomainValidator` enforces RFC 1035 and RFC 1123 compliance and eliminates malicious input or malformed configurations:

### Normalization Pipeline
1. `trim()` leading and trailing whitespace.
2. Normalize all characters to lowercase (e.g. `CHEETAHSFC.CO.KE` -> `cheetahsfc.co.ke`).
3. Strip optional trailing dot (e.g. `cheetahsfc.co.ke.` -> `cheetahsfc.co.ke`).

### Rejection Rules
- **Protocols / Schemes**: Rejects `http://`, `https://`, `ftp://`, etc.
- **Paths & Slashes**: Rejects `/`, `\`, `/teams`, `/about`.
- **Query Strings & Fragments**: Rejects `?param=1`, `#section`.
- **Port Numbers**: Rejects `:80`, `:443`, `:8080`.
- **Invalid Characters**: Only lowercase letters `a-z`, numbers `0-9`, hyphens `-`, and dots `.` are permitted. Underscores `_` and punctuation are rejected.
- **IP Addresses**: Strictly rejects IPv4 (e.g. `127.0.0.1`, `154.56.40.10`) and IPv6 (e.g. `::1`).
- **Internal / Reserved Hostnames**: Strictly rejects `localhost`, `.local`, `.internal`, `.test`, `.example`, `.invalid`, `.lan`.
- **Benchero Infrastructure Domains**: Rejects `benchero.co.ke`, `benchero.com`, `benchero.app`, `teamora.co.ke`, and any subdomain thereof to protect platform endpoints.

---

## 4. DNS Ownership Verification (`DomainVerificationService`)

Ownership verification proves that the customer controls DNS for the target hostname before Benchero routes traffic.

### Token Security
- Generated using `bin2hex(random_bytes(24))` (48 hex characters / 192 bits of entropy).
- Cryptographically unpredictable. Timestamps, organization slugs, auto-increment IDs, or weak PRNGs are strictly prohibited.

### TXT Record Convention
- **Host**: `_benchero-verification.<normalized_domain>`
- **Value**: `benchero-verification=<token>`
- **Lookup Method**: Authoritative DNS TXT lookup using PHP's `dns_get_record($host, DNS_TXT)` (or injected custom resolver for unit testing).
- **Match Requirement**: Exact constant-time string comparison (`hash_equals()`).

### Elimination of Old A-Record Fallback
The legacy implementation allowed A-record IP matches as verification. **This has been completely eliminated**. Pointing an A record to Benchero's IP address does not prove domain ownership and is susceptible to subdomain hijacking. TXT verification is mandatory.

---

## 5. Tenant Resolution & Tenant Isolation (`TenantResolver`)

Tenant resolution is the security foundation of Benchero's multi-tenancy.

### Authoritative Resolution Chain
```
HTTP Host Header
      │
      ▼
Strip Port (e.g. :443)
      │
      ▼
DomainValidator::normalize()
      │
      ▼
Database Query:
  SELECT * FROM custom_domains
  WHERE normalized_domain = ?
    AND activation_status = 'active'
    AND deleted_at IS NULL
      │
      ├── Not Found / Inactive ──► Return 404 Custom Error View
      │
      ▼
Fetch Organization Record
      │
      ▼
Check Entitlements (EntitlementService & SubscriptionService)
      │
      ├── Expired / Non-Pro ─────► Return 402 Locked Profile View
      │
      ▼
Set CustomDomainContext & Inject Request Attributes
```

### Untrusted Inputs
Tenant resolution **never** trusts:
- `$_GET` or `$_POST` parameters
- `$_COOKIE` values
- Hidden form fields
- Path-based organization IDs or slugs

### Cross-Tenant Boundary Protection
If an attacker or user accesses `https://club-a.com/club/club-b` on Club A's custom domain, `TenantMiddleware` rejects the request immediately with an HTTP 404 response. Organization A's domain can only ever serve Organization A's content.

---

## 6. Authentication & Admin Boundary

Customer custom domains exist exclusively for public-facing club digital identities (fixtures, rosters, results, history, gallery, contact).

`TenantMiddleware` intercepts all administrative and authentication paths:
- `/login`
- `/register`
- `/logout`
- `/forgot-password`
- `/reset-password`
- `/verify-email`
- `/admin` & `/admin/*`
- `/o` & `/o/*` (Tenant back-office dashboard and settings)

When any of these routes are requested on a custom domain, `TenantMiddleware` immediately responds with an **HTTP 302 Redirect** to the canonical Benchero domain (`APP_URL`, e.g. `https://benchero.co.ke/login`). Session cookies and login forms are never exposed across arbitrary customer hostnames.

---

## 7. URL Generation & Canonical SEO

### Helper `club_url($path, $orgSlug = null)`
- **On Canonical Benchero Domain**:
  Generates `https://benchero.co.ke/club/{slug}/teams` or `/club/{slug}/teams`.
- **On Active Custom Domain**:
  Generates clean root-relative links: `/`, `/teams`, `/players`, `/fixtures`, `/results`, `/contact`.

### Canonical Tags & Open Graph
- `<link rel="canonical">` rendered in `theme_header.php` points to `https://{custom-domain}/teams` when accessed via custom domain.
- `SeoService` automatically detects `CustomDomainContext::isActive()` and strips redundant `/club/{slug}` prefixes from canonical and Open Graph URLs.

---

## 8. Domain Replacement & Removal

### Safe Staged Replacement (Zero-Downtime)
When an organization with an active domain (`club-old.com`) registers a replacement domain (`club-new.com`):
1. `club-old.com` remains `is_primary = 1` and `activation_status = 'active'`. It continues serving live traffic uninterrupted.
2. `club-new.com` is inserted with `is_primary = 0` and `activation_status = 'pending'`.
3. Customer configures TXT verification for `club-new.com`.
4. Once verified and routing is confirmed, customer activates `club-new.com`.
5. The activation transaction sets `club-new.com` to `is_primary = 1` and marks `club-old.com` as `disabled`.
6. At no point is the organization left without a working live domain.

### Domain Removal
When disconnected:
- Record is removed from active routing.
- Audit entry logged with user ID, organization ID, and timestamps.
- Global unique index on `normalized_domain` prevents unauthorized hijacking.

---

## 9. Production Infrastructure Architecture & Requirements

### The Current Hosting Environment
Benchero is deployed on a Linux cPanel/Apache shared/dedicated environment:
- Document Root: `/home/xqtrqexj/benchero.co.ke`
- Web Server: Apache 2.4 with `.htaccess` rewrite rules pointing all requests to `public/index.php`.
- SSL Engine: cPanel AutoSSL (Sectigo / Let's Encrypt).

### Why Apache/cPanel Cannot Automatically Issue Arbitrary Custom Domain SSL
Under Apache, incoming HTTPS requests for arbitrary hostnames (`https://myclub.com`) arrive before PHP is executed. If Apache does not have an active VirtualHost or ServerAlias with a provisioned SSL certificate for `myclub.com`, Apache terminates the TLS handshake with `SSL_ERROR_UNRECOGNIZED_NAME_ALERT` or presents the server's default fallback certificate (generating a browser security warning).

PHP cannot dynamically intercept the TLS handshake in Apache. Therefore, production HTTPS requires an **Infrastructure Ingress Layer**.

### Recommended Production Solutions

#### Option 1: Cloudflare for SaaS (Recommended)
- **Mechanism**: Cloudflare Custom Hostnames / SSL for SaaS.
- **Workflow**:
  1. Benchero acts as the SaaS provider on Cloudflare Enterprise/Pro plan.
  2. Customer creates a CNAME: `www.myclub.com` -> `cname.benchero.co.ke`.
  3. Benchero's backend calls Cloudflare API (`POST /zones/{zone_id}/custom_hostnames`) when domain is saved.
  4. Cloudflare provisions and renews SSL certificates automatically at the edge within 60 seconds.
  5. Traffic arrives at Benchero server over SNI with Benchero's origin certificate.
- **Pros**: Zero Apache configuration changes; DDoS mitigation; worldwide CDN caching; automated certificate lifecycle.

#### Option 2: cPanel UAPI Parked Domain Automation
- **Mechanism**: Use cPanel UAPI `Park::park` to add customer domain as a ServerAlias to the Benchero cPanel account.
- **Workflow**:
  1. Upon domain TXT verification, application executes cPanel UAPI call via HTTPS with API token:
     `https://cpanel.benchero.co.ke:2083/execute/Park/park?domain=www.myclub.com`
  2. cPanel adds the alias to Apache's `httpd.conf` and triggers AutoSSL.
  3. cPanel AutoSSL requests a Let's Encrypt certificate for the domain.
- **Limitations**: Rate-limited by cPanel AutoSSL polling cycles (typically 1–4 hours); requires root/cPanel API credentials.

#### Option 3: Reverse Proxy Ingress (Caddy / Traefik / Nginx)
- **Mechanism**: Place Caddy as an edge reverse proxy in front of Apache.
- **Workflow**:
  1. Customer points CNAME / A record to the edge server.
  2. Caddy has On-Demand TLS enabled (`on_demand_tls`).
  3. When TLS handshake arrives for `myclub.com`, Caddy queries Benchero's internal API:
     `GET http://localhost/api/internal/check-domain?domain=myclub.com`
  4. If Benchero confirms the domain is verified and active, Caddy obtains a Let's Encrypt certificate on the fly in ~3 seconds.
  5. Caddy proxies decrypted traffic to Apache `public/index.php`.
- **Pros**: 100% automated, free Let's Encrypt certificates, instant issuance on first request.

---

## 10. Audit Logging & Rate Limiting Reference

### Rate Limits (`RateLimiter`)
| Action | Key Pattern | Limit | Cooldown |
| :--- | :--- | :--- | :--- |
| **Save / Register Domain** | `domain_save_{org_id}` | 10 attempts | 10 minutes |
| **Verify DNS TXT** | `domain_verify_{org_id}` | 15 attempts | 5 minutes |
| **Regenerate Token** | `domain_regen_{org_id}` | 5 attempts | 10 minutes |
| **Activate Domain** | `domain_activate_{org_id}` | 10 attempts | 5 minutes |
| **Live SSL Check** | `domain_ssl_{org_id}` | 10 attempts | 5 minutes |
| **Delete / Disconnect** | `domain_delete_{org_id}` | 5 attempts | 10 minutes |

### Audit Log Actions (`audit_logs`)
- `custom_domain_saved`
- `custom_domain_verified`
- `custom_domain_verification_failed`
- `custom_domain_activated`
- `custom_domain_deactivated`
- `custom_domain_token_regenerated`
- `custom_domain_ssl_verified`
- `custom_domain_deleted`
