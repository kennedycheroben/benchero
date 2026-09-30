# Benchero Cloudflare for SaaS: Architecture Validation & Engineering Design

**Author**: Senior Infrastructure & SaaS Architect  
**Target System**: Benchero SaaS (`/opt/lampp/htdocs/benchero`)  
**Status**: VALIDATED & HARDENED (PRE-PRODUCTION)  
**Last Updated**: 2026-09-30  

---

## 1. Executive Summary

This document establishes the authoritative technical architecture for integrating **Cloudflare for SaaS (Custom Hostnames)** into the Benchero multi-tenant sports management platform.

### Core Business & Technical Objectives
1. **Self-Service Custom Domain Automation**: Allow Pro tier clubs (e.g., `cheetahsfc.co.ke`, `sports.kennedycheroben.co.ke`) to onboard custom hostnames without manual server reconfiguration or Apache VirtualHost changes.
2. **Dynamic SNI & Automated Edge SSL**: Issue, manage, and renew edge TLS certificates automatically via Cloudflare for SaaS.
3. **Preservation of Tenant Isolation Architecture**: Maintain Benchero's host-based tenant resolution (`TenantResolver`), cross-tenant route protection (`TenantMiddleware`), and canonical authentication boundary without regressions.
4. **Resilient Fail-Safe Operation**: Ensure all Cloudflare API calls are idempotent, rate-limited, safely error-handled, and gated behind a feature flag (`CLOUDFLARE_CUSTOM_HOSTNAMES_ENABLED=false` by default).
5. **Zero-Trust Ingress Security**: Enforce cryptographic Worker-to-Origin mutual authentication (`X-Benchero-Worker-Secret`), eliminating `X-Forwarded-Host` header spoofing risks between Cloudflare Edge and origin PHP.

---

## 2. Implementation & Deployment Readiness Status

To ensure absolute clarity for operations and engineering, all components of this architecture are categorized into three distinct operational states:

### Category A: Currently Implemented in Code (Repository State)
* [x] **Database Schema**: Migration `040_add_cloudflare_fields_to_custom_domains.php` adding `cloudflare_custom_hostname_id`, `cloudflare_status`, `cloudflare_ssl_status`, `cloudflare_last_checked_at`, `cloudflare_last_error`, and `cloudflare_created_at`.
* [x] **API Client & Lifecycle Service**: `app/Services/Cloudflare/CloudflareCustomHostnameService.php` with:
  - Idempotent creation (`409 Conflict` recovery via hostname lookup).
  - Centralized status mappings conforming to official Cloudflare Custom Hostname and SSL specifications.
  - Safe 4xx/5xx/timeout exception handling with zero unhandled fatal errors.
* [x] **Domain Service Lifecycle Integration**: `app/Services/DomainService.php` enforcing:
  - Phase 1: DNS TXT cryptographic token ownership verification.
  - Phase 2: Cloudflare custom hostname provisioning upon verification.
  - Phase 3: Activation prerequisite gate requiring Cloudflare edge SSL to be `'active'`.
  - Phase 4: Synchronous Cloudflare custom hostname deletion upon domain removal.
* [x] **Ingress Security Hardening**: `app/Core/Http/Request.php`:
  - Enforces `CLOUDFLARE_WORKER_SECRET` constant-time verification (`hash_equals`) in production before trusting `X-Forwarded-Host`.
  - Unconditionally rejects client-supplied bypass headers (`X-Benchero-Proxy-Hop`).
  - Full IPv4 and IPv6 CIDR validation against Cloudflare published IP ranges (`isCloudflareIp()`).
* [x] **Background Reconciliation CLI**: `bin/cloudflare_sync.php` supporting `--auto-activate`, `--domain`, `--org`, `--dry-run`, and `--force`.
* [x] **Automated Test Suite**: `tests/run_custom_domain_tests.php` validating 167 unit and integration test assertions across security, proxy trust, lifecycle gates, and Cloudflare error modes.

### Category B: Requires Cloudflare Dashboard Configuration (Pending Operator Action)
* [ ] Provision Cloudflare API Token with `Zone.Custom Hostnames (Edit)`, `Zone.SSL and Certificates (Edit)`, and `Zone.Zone (Read)` permissions.
* [ ] Configure Cloudflare for SaaS Fallback Origin to `cname.benchero.co.ke` on zone `benchero.co.ke`.
* [ ] Create DNS records:
  - `cname.benchero.co.ke` (Proxied CNAME target for customer domains).
  - `origin.benchero.co.ke` (A record pointing to origin IP `198.244.209.74`).
* [ ] Deploy Worker `benchero-saas-proxy` with secret environment variable `WORKER_SECRET`.
* [ ] Configure Worker Routes:
  - Route 1 (Exclusion): `origin.benchero.co.ke/*` -> **Worker: None**
  - Route 2 (Wildcard): `*/*` -> **Worker: `benchero-saas-proxy`**

### Category C: Requires Production Validation / Staging Test (Pre-Flight Verification)
* [ ] Deploy Worker to a staging or sandbox zone to verify subrequest behavior and route exclusion under real traffic.
* [ ] Verify real Domain Control Validation (DCV) with a real test domain pointing CNAME to `cname.benchero.co.ke`.
* [ ] Validate Orange-to-Orange (O2O) behavior for a customer domain hosted on Cloudflare.
* [ ] Verify crontab execution on production server.

---

## 3. Evaluation of Ingress Models

Three ingress architectural models were evaluated against official Cloudflare documentation:

```
+----------------------------------------------------------------------------------------------------+
|                                    EVALUATION OF ARCHITECTURAL MODELS                              |
+----------------------+------------------------------------+----------------------------------------+
| Model                | Description                        | Architectural Verdict                  |
+----------------------+------------------------------------+----------------------------------------+
| Model A:             | - Wildcard route: */* -> Worker    | RECOMMENDED (Official SaaS Pattern)   |
| Worker Route         | - Route exception:                 | Supported by Cloudflare documentation. |
| Exception            |   origin.benchero.co.ke/* -> None  | Route specificity ensures origin       |
|                      | - Origin: proxied (orange-cloud)   | subrequests bypass Worker execution.   |
+----------------------+------------------------------------+----------------------------------------+
| Model B:             | - Fallback Origin DNS points to    | COMPLEMENTARY TO MODEL A               |
| Worker-as-Fallback-  |   dummy/originless record (100::)  | "Worker as Fallback Origin" requires   |
| Origin               | - Worker handles requests from     | a wildcard route (*/*) to match custom |
|                      |   custom hostnames                 | hostnames. It is Model A + 100:: IP.   |
+----------------------+------------------------------------+----------------------------------------+
| Model C:             | - Custom hostnames point to CNAME  | REJECTED (Technically Invalid)         |
| Dedicated Hostname   |   cname.benchero.co.ke             | Cloudflare evaluates Worker Routes     |
| Route                | - Worker route on                  | against the incoming URL hostname, NOT |
|                      |   cname.benchero.co.ke/* ONLY      | the CNAME pointer. Customer domains    |
|                      | - No wildcard route used           | will NOT trigger the Worker!           |
+----------------------+------------------------------------+----------------------------------------+
```

### 3.1 Detailed Analysis of Model C (Why Dedicated Hostname Route Fails)
In Cloudflare for SaaS, when an end-user navigates to `https://sports.customer.com`, the request arrives at the Cloudflare edge with:
- **SNI**: `sports.customer.com`
- **Host Header**: `sports.customer.com`
- **Request URL**: `https://sports.customer.com/path`

Cloudflare routes the request to the zone associated with the Custom Hostname (`benchero.co.ke`). When evaluating Worker Routes on that zone, Cloudflare tests the route pattern against the **actual request URL**, NOT the DNS target hostname (`cname.benchero.co.ke`).
Because `https://sports.customer.com/path` does not match `cname.benchero.co.ke/*`, the Worker will **never execute**. The request would fall through directly to the fallback origin without Worker header injection or origin translation.

**Conclusion**: A wildcard route pattern (`*/*`) on the SaaS zone is **strictly required** to intercept arbitrary customer custom hostnames.

### 3.2 Detailed Analysis of Model A & B (The Standard Cloudflare Pattern)
According to official Cloudflare documentation (*"Workers as your fallback origin"*), routing custom hostnames through a Worker requires:
1. **Fallback Origin Record**: A proxied DNS record in the zone (e.g. `cname.benchero.co.ke` or `fallback.benchero.co.ke`).
2. **Wildcard Route**: A route of `*/*` assigned to the Worker to capture all incoming custom hostname traffic.
3. **Route Exceptions**: Specific routes for any hostnames in the zone that must bypass the Worker (e.g., `origin.benchero.co.ke/*` set to **Worker: None**).

---

## 4. Worker Subrequests & Recursion Analysis

### 4.1 Mechanics of Same-Zone `fetch()` Subrequests
When a Cloudflare Worker handles an incoming request and issues an outbound `fetch()` to another URL:
- If the destination URL is an external third-party domain, Cloudflare executes standard public DNS resolution and sends the subrequest over the Internet.
- If the destination URL belongs to the **same Cloudflare zone**:
  - The request passes through Cloudflare's internal edge pipeline.
  - If the target hostname matches a Worker route, Cloudflare restricts recursive invocations. A Worker calling itself or another Worker on the same zone can result in **Error 1042** or recursion aborts (**Error 1019**).
  - Cloudflare tracks nested Worker invocations using the internal `CF-EW-Via` header and enforces a maximum call depth of 16 subrequests.

### 4.2 Multi-Layered Defense Against Recursion
Rather than asserting that recursion is "mathematically impossible," the Benchero architecture implements a **rigorous multi-layered defense** against routing loops:

```
[Layer 1: Edge Route Precedence]
    Route 1: origin.benchero.co.ke/*  --> Worker: None (Exact match / Highest Precedence)
    Route 2: */*                      --> Worker: benchero-saas-proxy (Wildcard fallback)
    Outcome: When Worker calls fetch("https://origin.benchero.co.ke/..."),
             Cloudflare matches Route 1 and dispatches directly to the origin server.

[Layer 2: In-Worker Loop Prevention Guard]
    if (url.hostname === "origin.benchero.co.ke") {
        return fetch(request); // Pass-through to origin without re-proxying
    }

[Layer 3: Cloudflare Runtime Recursion Termination]
    CF-EW-Via counter prevents runaway cascades; edge aborts with error 1019
    if hop depth exceeds platform limits.
```

### 4.3 Origin Proxied (Orange-Cloud) vs DNS-Only (Grey-Cloud)

| Dimension | Proxied Origin (`origin.benchero.co.ke` Orange-Cloud) | DNS-Only Origin (`origin.benchero.co.ke` Grey-Cloud) |
| :--- | :--- | :--- |
| **Origin IP Protection** | IP `198.244.209.74` is hidden behind Cloudflare edge IPs. | IP `198.244.209.74` is publicly visible via `dig`. |
| **Edge DDoS / WAF** | Active on origin hostname. | Bypassed on origin hostname. |
| **SSL Certificate on Origin** | Can use Cloudflare Origin CA certificate (15-year validity). | Requires publicly trusted cert (e.g. Let's Encrypt) on Apache. |
| **Loop Prevention Reliance** | Relies on Cloudflare Route Specificity (`origin.benchero.co.ke/*` -> None). | Subrequest bypasses Cloudflare edge proxying entirely. |
| **Operational Recommendation** | **RECOMMENDED**: Orange-cloud with Route Exception. | Supported fallback if route exceptions are unavailable. |

---

## 5. Orange-to-Orange (O2O) Architecture

When a customer's custom domain is already managed under their own Cloudflare account (e.g., `customer-club.com` is an active Cloudflare zone):

```
[Visitor Browser]
       │
       ▼
[Customer's Cloudflare Zone: customer-club.com]
       │  (Customer's WAF, Page Rules, Caching, Edge TLS)
       │  DNS: customer-club.com CNAME cname.benchero.co.ke (Proxied: Orange Cloud)
       ▼
[Benchero SaaS Cloudflare Zone: benchero.co.ke]
       │  (Cloudflare for SaaS Edge TLS & Custom Hostname DCV)
       │  Header: CF-Connecting-O2O: 1
       │  Worker: benchero-saas-proxy
       ▼
[Origin Web Server: 198.244.209.74]
       │  Validates X-Benchero-Worker-Secret
       │  Resolves tenant via X-Forwarded-Host: customer-club.com
```

### Key Technical Characteristics of O2O:
1. **Header Identification**: Cloudflare automatically appends the header `CF-Connecting-O2O: 1` when traffic passes from the customer zone into the SaaS provider zone.
2. **Order of Operations**:
   - Customer Zone settings (WAF rules, redirects, transformations) execute **first**.
   - Benchero SaaS Zone settings (Custom Hostname SSL, SaaS Ingress Worker) execute **second**.
3. **Domain Control Validation (DCV)**:
   - In O2O setups, DNS TXT pre-validation (`_benchero-verification.domain.com`) continues to verify tenant ownership before provisioning in Benchero.
   - For Cloudflare edge SSL issuance, Cloudflare's CNAME DCV automatically completes because the customer zone routes traffic to the SaaS zone.
4. **Origin Security Invariance**:
   - Because Benchero's `benchero-saas-proxy` Worker runs in the Benchero SaaS zone (the 2nd zone), it injects the authoritative `X-Benchero-Worker-Secret`.
   - The origin PHP application requires no special O2O exceptions: it treats all requests identically by validating the Worker secret.

---

## 6. End-to-End Security Architecture

### 6.1 Vulnerability in Unauthenticated `X-Forwarded-Host`
In multi-tenant SaaS systems, trusting `X-Forwarded-Host` without verifying the proxy identity creates a critical **Host Header Injection / Tenant Spoofing Vulnerability**. An attacker sending requests directly to the origin server IP could spoof arbitrary tenant hostnames:
```http
GET / HTTP/1.1
Host: origin.benchero.co.ke
X-Forwarded-Host: victim-club.co.ke
```
Without authentication, the application would serve the victim club's content or bind sessions under false context.

### 6.2 Hardened Trusted Proxy Protocol (`app/Core/Http/Request.php`)
Benchero implements a zero-trust proxy verification contract:

```
[Inbound Request at Origin PHP]
              │
              ├── Is APP_ENV === 'production'?
              │      ├── YES:
              │      │     Is CLOUDFLARE_WORKER_SECRET configured?
              │      │     ├── NO  --> Do NOT trust X-Forwarded-Host. Use HTTP_HOST.
              │      │     └── YES --> Compare X-Benchero-Worker-Secret using hash_equals().
              │      │                 ├── Mismatch --> Reject proxy trust. Use HTTP_HOST.
              │      │                 └── Match    --> Validate Optional Proxy IP (if enabled).
              │      │                                 └── TRUSTED: Use X-Forwarded-Host.
              │      └── NO (Local / Test):
              │            Allow X-Forwarded-Host if no secret configured,
              │            or enforce secret if configured.
```

### 6.3 Threat Model of `CLOUDFLARE_VALIDATE_PROXY_IP`
- Cloudflare publishes its official IPv4 and IPv6 CIDR blocks.
- `Request::isCloudflareIp()` verifies that `$_SERVER['REMOTE_ADDR']` falls within these ranges.
- **Important Threat Boundary**: Validating IP ranges is **defense-in-depth**, NOT primary authorization. Because all Cloudflare tenants share Cloudflare edge IP ranges, an IP check alone cannot prove that a request originated from Benchero's Worker rather than another Cloudflare customer.
- **Primary Trust Root**: The cryptographically random 64-hex token `X-Benchero-Worker-Secret` is the authoritative shared secret between Cloudflare Worker and Benchero origin.
- **Client Diagnostic Headers**: Headers such as `X-Benchero-Proxy-Hop` are strictly diagnostic markers for logs and tracing. They are **never** used to establish proxy trust.

---

## 7. Cloudflare Custom Hostname Lifecycle & State Transitions

Benchero strictly separates **Domain Ownership Verification** from **Cloudflare Custom Hostname Provisioning** and **Edge SSL Issuance**:

```
[Phase A: Benchero Ownership Verification]
    1. Tenant enters domain in Benchero UI.
    2. Benchero generates 48-hex CSPRNG token; stores verification_status = 'pending'.
    3. Tenant creates DNS TXT: _benchero-verification.domain.com -> benchero-verification=<TOKEN>.
    4. Tenant clicks "Verify Domain". DomainVerificationService queries DNS TXT.
    5. Upon exact match: verification_status = 'verified'.

[Phase B: Cloudflare Custom Hostname Provisioning]
    1. Triggered automatically upon Phase A success (if CLOUDFLARE_CUSTOM_HOSTNAMES_ENABLED=true).
    2. CloudflareCustomHostnameService calls POST /zones/{zone_id}/custom_hostnames:
       - Hostname: domain.com
       - SSL: { method: "cname", type: "dv" }
    3. Handles 409 Conflict idempotently (retrieves existing hostname ID).
    4. Database records:
       - cloudflare_custom_hostname_id
       - cloudflare_status = 'pending'
       - cloudflare_ssl_status = 'pending_validation'

[Phase C: Edge SSL Issuance & Routing Activation]
    1. Tenant points DNS CNAME: domain.com -> cname.benchero.co.ke.
    2. Cloudflare completes DCV and deploys edge TLS certificate.
    3. Reconciled via cron/bin/cloudflare_sync.php or "Check Live SSL Status":
       - cloudflare_status = 'active'
       - cloudflare_ssl_status = 'active'
    4. Activation Gate: DomainService::activateDomain() validates:
       - verification_status === 'verified'
       - cloudflare_status === 'active'
       - cloudflare_ssl_status === 'active'
    5. If all gates pass: activation_status = 'active'. TenantResolver routes live traffic!
```

---

## 8. Cloudflare SSL Status Mapping Reference

The Cloudflare API returns fine-grained SSL statuses. `CloudflareCustomHostnameService::mapCfSslStatusToBenchero()` normalizes these into Benchero's domain schema:

| Cloudflare `ssl.status` | Cloudflare Meaning | Benchero Normalized Status | Can Activate? |
| :--- | :--- | :--- | :--- |
| `active` | Certificate deployed and serving traffic at edge | `active` | **YES** |
| `pending_validation` | DCV record pending verification | `pending` | NO (Blocked) |
| `pending_issuance` | CA is issuing certificate | `pending` | NO (Blocked) |
| `pending_deployment`| Certificate issued, syncing across global edge | `pending` | NO (Blocked) |
| `initializing` | Cloudflare hostname setup in progress | `pending` | NO (Blocked) |
| `expired` | Certificate expired | `failed` | NO (Blocked) |
| `timed_out` | DCV validation timed out | `failed` | NO (Blocked) |
| `failed` | CA rejected issuance | `failed` | NO (Blocked) |
| `deleted` | Hostname/SSL deleted in Cloudflare | `not_configured` | NO (Blocked) |
| `null` / empty | No SSL configured | `not_configured` | NO (Blocked) |

---

## 9. Next Steps for Production Rollout

1. **Dashboard Configuration**: Complete Category B configurations in Cloudflare Dashboard.
2. **Worker Deployment**: Deploy `benchero-saas-proxy` with route exclusion `origin.benchero.co.ke/*` -> None.
3. **Staging Smoke Test**: Test with a designated test domain (e.g. `sports.kennedycheroben.co.ke`) before enabling general customer onboarding.
