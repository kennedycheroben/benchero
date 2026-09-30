# Benchero Custom Domains: Operations & Troubleshooting Runbook

**Author**: Senior Infrastructure & SaaS Architect  
**Platform**: Benchero Multi-Tenant Sports SaaS  
**Target Systems**: `custom_domains` MySQL Table, Cloudflare for SaaS API, `bin/cloudflare_sync.php`  
**Status**: VALIDATED OPERATOR SPECIFICATION (PRE-PRODUCTION)  
**Last Updated**: 2026-09-30  

---

## 1. Operational Overview & Status Separation

Benchero enables Pro-tier sports clubs to brand their public team profiles using custom domains (e.g. `cheetahsfc.co.ke`, `sports.customer.com`).

### Operational Status Categorization
- **Currently Implemented in Code (Category A)**:
  - Full domain state machine in `app/Services/DomainService.php`.
  - Cloudflare Custom Hostnames client in `app/Services/Cloudflare/CloudflareCustomHostnameService.php`.
  - Ingress proxy security in `app/Core/Http/Request.php`.
  - Background synchronization script in `bin/cloudflare_sync.php`.
- **Requires Dashboard Configuration (Category B)**:
  - Fallback origin registration (`cname.benchero.co.ke`).
  - Worker deployment (`benchero-saas-proxy`) with secret variable `WORKER_SECRET`.
  - Worker Route precedence (`origin.benchero.co.ke/*` -> None, `*/*` -> Worker).
- **Requires Production Validation (Category C)**:
  - Staging verification of live DNS TXT and CNAME issuance.
  - Verification of Orange-to-Orange (O2O) routing with a Cloudflare-hosted test domain.

---

## 2. The Three Onboarding Phases

To prevent premature routing, security vulnerabilities, and API quota abuse, custom domain onboarding is strictly divided into three phases:

```
+-----------------------------------------------------------------------------------+
|                            THE THREE ONBOARDING PHASES                            |
+-------------------+-------------------------------+-------------------------------+
| Phase             | Action                        | Status Transition             |
+-------------------+-------------------------------+-------------------------------+
| Phase 1:          | Tenant adds DNS TXT record    | verification_status:          |
| Ownership         | _benchero-verification.domain | pending -> verified           |
| Verification      | Benchero verifies token       |                               |
+-------------------+-------------------------------+-------------------------------+
| Phase 2:          | Benchero calls Cloudflare API | cloudflare_status:            |
| Cloudflare        | POST /custom_hostnames        | pending                       |
| Provisioning      | Tenant creates DNS CNAME      | cloudflare_ssl_status:        |
|                   | to cname.benchero.co.ke       | pending_validation            |
+-------------------+-------------------------------+-------------------------------+
| Phase 3:          | Cloudflare completes DCV and  | cloudflare_status: active     |
| Edge SSL &        | issues certificate.           | cloudflare_ssl_status: active |
| Activation        | Tenant or cron activates      | activation_status: active     |
|                   | live routing.                 | is_primary: 1                 |
+-------------------+-------------------------------+-------------------------------+
```

---

## 3. Background Reconciliation CLI (`bin/cloudflare_sync.php`)

The CLI script `bin/cloudflare_sync.php` synchronizes domain states between the Cloudflare API and Benchero's MySQL database.

### Common Operational Invocations

#### Reconcile all domains and auto-activate ready domains:
```bash
php bin/cloudflare_sync.php --auto-activate
```

#### Reconcile a specific custom domain:
```bash
php bin/cloudflare_sync.php --domain=sports.kennedycheroben.co.ke
```

#### Reconcile all domains for a specific organization:
```bash
php bin/cloudflare_sync.php --org=cheetahs-fc
```

#### Dry-Run Mode (Preview status changes without modifying database):
```bash
php bin/cloudflare_sync.php --dry-run
```

#### Force Re-check (Bypasses the 5-minute cache cooldown):
```bash
php bin/cloudflare_sync.php --force
```

---

## 4. Production Crontab Setup

To maintain automated synchronization without blocking web requests:

```crontab
# Reconcile Cloudflare Custom Hostname statuses and activate ready domains every 5 minutes
*/5 * * * * php /opt/lampp/htdocs/benchero/bin/cloudflare_sync.php --auto-activate >/dev/null 2>&1
```

---

## 5. Troubleshooting & Diagnostic Matrix

### Issue 1: "Cannot activate domain yet: Cloudflare edge SSL is currently 'pending_validation'"
* **Symptom**: User receives an alert when clicking "Activate Domain".
* **Root Cause**: The domain's CNAME record is not yet resolving to `cname.benchero.co.ke`, or Cloudflare DCV is still in progress.
* **Resolution**:
  1. Inspect the customer CNAME in external DNS:
     ```bash
     dig CNAME customerdomain.com +short
     ```
  2. If the CNAME does not point to `cname.benchero.co.ke`, notify the customer to correct their DNS.
  3. If the CNAME is correct, check status directly via CLI:
     ```bash
     php bin/cloudflare_sync.php --domain=customerdomain.com --force
     ```
  4. Once Cloudflare issues the certificate (typically 60–120 seconds after DNS propagation), status transitions to `active` and activation succeeds.

---

### Issue 2: Customer Domain is on Cloudflare (Orange-to-Orange / O2O)
* **Symptom**: Customer claims they added the CNAME, but certificate issuance is delayed or customer has proxy enabled in their Cloudflare account.
* **Root Cause**: The customer has an active Cloudflare zone for their domain and set the CNAME to **Proxied (Orange Cloud)**.
* **Resolution**:
  1. Cloudflare for SaaS fully supports O2O configurations.
  2. Confirm that the customer's CNAME points to `cname.benchero.co.ke`.
  3. Cloudflare automatically establishes an O2O session between the customer zone and the Benchero SaaS zone.
  4. Incoming requests will carry the header `CF-Connecting-O2O: 1`.
  5. If DCV stalls on an O2O setup, ask the customer to temporarily grey-cloud the CNAME record until the certificate issues, then re-enable the orange cloud.

---

### Issue 3: Ingress Worker Rejects Inbound Request / Host Falls Back to Origin Hostname
* **Symptom**: Requests to custom domain show default Benchero content or fail tenant lookup. Access logs show `origin.benchero.co.ke` instead of custom domain.
* **Root Cause**: Secret mismatch between Cloudflare Worker (`WORKER_SECRET`) and Benchero `.env` (`CLOUDFLARE_WORKER_SECRET`).
* **Resolution**:
  1. Verify the secret configured in Benchero `.env`:
     ```bash
     grep CLOUDFLARE_WORKER_SECRET /opt/lampp/htdocs/benchero/.env
     ```
  2. Verify the secret configured in Cloudflare Worker environment variables (`WORKER_SECRET`).
  3. Ensure both strings match exactly. In production, `Request::isTrustedProxy()` will unconditionally reject `X-Forwarded-Host` if the secret does not match.

---

### Issue 4: Cloudflare API Rate Limit Exceeded (HTTP 429)
* **Symptom**: Reconciliation script logs `Cloudflare API rate limit exceeded (HTTP 429)`.
* **Root Cause**: More than 1,200 requests within a 5-minute rolling window.
* **Resolution**:
  - `CloudflareCustomHostnameService` catches HTTP 429 safely without crashing.
  - The reconciliation CLI automatically records the error in `cloudflare_last_error` and skips subsequent rapid polls until cooldown expires.

---

### Issue 5: Clean Deletion & Orphan Removal
* **Symptom**: Customer disconnects a domain from Benchero, but hostname remains active in Cloudflare.
* **Resolution**:
  - `DomainService::deleteDomain()` synchronously calls `CloudflareCustomHostnameService::deleteCustomHostname()`.
  - If Cloudflare returns `200` or `404` (already deleted), local database records are cleaned up cleanly.
  - If Cloudflare returns a `5xx` error, check `audit_logs` for `custom_domain_deleted` to track manual removal if necessary.
