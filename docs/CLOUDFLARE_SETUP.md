# Benchero Cloudflare for SaaS: Infrastructure & Edge Worker Setup Guide

**Author**: Senior Infrastructure & SaaS Architect  
**Platform**: Benchero Multi-Tenant Sports SaaS  
**Scope**: Production Cloudflare Zone, Cloudflare for SaaS, Ingress Worker (`benchero-saas-proxy`), and Origin Configuration  
**Status**: VALIDATED SPECIFICATION (PRE-PRODUCTION)  
**Last Updated**: 2026-09-30  

---

## 1. Overview & Operational Status

Benchero uses **Cloudflare for SaaS (Custom Hostnames)** to dynamically issue edge SSL/TLS certificates and route customer domains (e.g. `cheetahsfc.co.ke`, `sports.customer.com`) to the Benchero application origin without requiring manual server reconfiguration or Apache VirtualHost changes.

### Implementation Separation Matrix

| Phase | Description | Status |
| :--- | :--- | :--- |
| **Category A: Implemented in Code** | Application services, database migrations, security proxy hardening, status normalization, test suites. | **COMPLETE** |
| **Category B: Dashboard Configuration** | Zone DNS, Cloudflare for SaaS Fallback Origin, Worker deployment, Route precedence rules. | **PENDING OPERATOR ACTION** |
| **Category C: Production Validation** | Staging smoke tests, real DCV CNAME validation, Orange-to-Orange (O2O) customer testing. | **POST-DEPLOYMENT** |

---

## 2. DNS Infrastructure Requirements

In the Cloudflare Dashboard for the primary SaaS zone (`benchero.co.ke`), create the following DNS records:

| Record Name | Type | Target / Value | Proxy Status | Purpose |
| :--- | :--- | :--- | :--- | :--- |
| `cname.benchero.co.ke` | `CNAME` or `AAAA` | `benchero.co.ke` (or `100::` originless) | **Proxied (Orange Cloud)** | Target for customer CNAME records; designated as SaaS Fallback Origin. |
| `origin.benchero.co.ke` | `A` | `198.244.209.74` | **Proxied (Orange Cloud)** (with Route Exception) OR **DNS Only (Grey Cloud)** | Real Benchero web server origin. Receives traffic forwarded by Worker. |
| `benchero.co.ke` | `A` | `198.244.209.74` | **Proxied (Orange Cloud)** | Primary marketing & SaaS dashboard application. |
| `www.benchero.co.ke` | `CNAME` | `benchero.co.ke` | **Proxied (Orange Cloud)** | Canonical redirect to apex. |

> [!IMPORTANT]
> If `origin.benchero.co.ke` is set to **Proxied (Orange Cloud)**, you **MUST** configure the Route Exception (`origin.benchero.co.ke/*` -> Worker: None) as detailed in Section 5 **BEFORE** enabling the wildcard route.

---

## 3. Cloudflare for SaaS Zone Configuration

1. In the Cloudflare Dashboard, select the `benchero.co.ke` zone.
2. Navigate to **SSL/TLS** > **Custom Hostnames**.
3. Click **Enable Cloudflare for SaaS** (if not already enabled).
4. Configure the **Fallback Origin**:
   - Set Fallback Origin hostname to: `cname.benchero.co.ke`
   - Confirm that `cname.benchero.co.ke` is **Proxied (Orange Cloud)** in DNS.
   - Status must show **Active** in the dashboard.
5. In Custom Hostnames settings:
   - Certificate Authority: **Default CA** (Let's Encrypt / Google Trust Services).
   - Minimum TLS Version: **1.2**.
   - HTTP/2: **Enabled**.

---

## 4. Ingress Cloudflare Worker: `benchero-saas-proxy`

The Worker intercepts incoming traffic from custom hostnames, terminates edge TLS, applies DDoS protection, injects trusted authentication headers, and forwards the request to the Benchero Apache/PHP origin server.

### 4.1 Worker Source Code (`worker.js`)

```javascript
/**
 * Benchero SaaS Ingress Proxy Worker
 * Worker Name: benchero-saas-proxy
 * 
 * Functions:
 * 1. Terminate edge TLS for custom hostnames and SaaS subdomains.
 * 2. Prevent routing loops via in-script hostname check.
 * 3. Forward request to origin with Host: origin.benchero.co.ke.
 * 4. Inject trusted proxy headers:
 *    - X-Forwarded-Host: <original_custom_hostname>
 *    - X-Forwarded-Proto: https
 *    - X-Forwarded-For: <client_ip>
 *    - X-Benchero-Worker-Secret: <SECRET>
 *    - X-Benchero-Proxy-Hop: 1 (Diagnostic only)
 */

export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);
    const originalHost = url.hostname.toLowerCase();

    // Guard 1: Direct requests to origin hostname bypass proxying to prevent loops
    if (originalHost === "origin.benchero.co.ke") {
      return fetch(request);
    }

    // Prepare headers for the origin request
    const originHeaders = new Headers(request.headers);
    originHeaders.set("X-Forwarded-Host", originalHost);
    originHeaders.set("X-Forwarded-Proto", "https");
    originHeaders.set("X-Forwarded-For", request.headers.get("CF-Connecting-IP") || "127.0.0.1");
    originHeaders.set("X-Benchero-Proxy-Hop", "1"); // Diagnostic marker for origin access logs

    // Authenticate Worker to Origin (Verified by Benchero Request::isTrustedProxy())
    if (env.WORKER_SECRET) {
      originHeaders.set("X-Benchero-Worker-Secret", env.WORKER_SECRET);
    }

    // Build origin target URL
    const targetUrl = new URL(request.url);
    targetUrl.hostname = "origin.benchero.co.ke";
    targetUrl.protocol = "https:";
    targetUrl.port = "443";

    // Forward request to origin server
    return fetch(targetUrl.toString(), {
      method: request.method,
      headers: originHeaders,
      body: request.body,
      redirect: "manual"
    });
  }
};
```

### 4.2 Worker Environment Variables
In the Worker settings (**Workers & Pages** > `benchero-saas-proxy` > **Settings** > **Variables**):
- Variable Name: `WORKER_SECRET`
- Type: **Secret** (encrypted)
- Value: The exact 64-character hexadecimal secret configured in Benchero `.env` (`CLOUDFLARE_WORKER_SECRET`).

---

## 5. Worker Routing Rules & Precedence Hierarchy

Cloudflare evaluates Worker routes based on **Specificity**, NOT creation order. More specific hostname routes always take precedence over wildcard routes.

Configure routes in **Workers & Pages** > **Routes** (Zone: `benchero.co.ke`):

| Route Pattern | Worker | Precedence | Behavior |
| :--- | :--- | :--- | :--- |
| `origin.benchero.co.ke/*` | **None** | **HIGHEST** (Specific Hostname) | Requests targeting the origin hostname bypass the Worker and route straight to Apache. |
| `benchero.co.ke/*` | **None** | **HIGH** (Primary Apex) | Primary marketing & web dashboard routes directly to Apache without Worker interception. |
| `www.benchero.co.ke/*` | **None** | **HIGH** (Primary WWW) | WWW requests route directly to Apache without Worker interception. |
| `*/*` | `benchero-saas-proxy` | **LOWEST** (Wildcard Catch-all) | Captures all custom hostnames routed to the zone and executes the ingress Worker. |

### Ingress & Recursion Resolution Flow
1. Visitor requests `https://cheetahsfc.co.ke/fixtures`.
2. Cloudflare Edge routes the request to SaaS zone `benchero.co.ke` (via Fallback Origin).
3. The request URL is `https://cheetahsfc.co.ke/fixtures`.
4. Route evaluation:
   - Does NOT match `origin.benchero.co.ke/*`
   - Does NOT match `benchero.co.ke/*`
   - **Matches `*/*`** -> Ingress Worker `benchero-saas-proxy` executes.
5. Worker injects `X-Forwarded-Host: cheetahsfc.co.ke` and `X-Benchero-Worker-Secret`.
6. Worker issues subrequest: `fetch("https://origin.benchero.co.ke/fixtures")`.
7. Subrequest route evaluation on zone:
   - **Matches `origin.benchero.co.ke/*` (Worker: None)** with higher specificity.
   - Bypasses Worker and connects to origin server IP `198.244.209.74`.
8. Apache/PHP validates `X-Benchero-Worker-Secret`, reads `X-Forwarded-Host`, resolves the Cheetahs FC tenant, and returns the response.

---

## 6. Orange-to-Orange (O2O) Setup for Cloudflare Customers

When a customer club already uses Cloudflare for their domain (e.g. `customerclub.com` is hosted on Cloudflare):

### Customer-Side Configuration:
1. Customer creates a CNAME in their Cloudflare DNS:
   - Name: `sports` (or `@` if using CNAME Flattening on apex)
   - Target: `cname.benchero.co.ke`
   - Proxy status: **Proxied (Orange Cloud)**
2. In an O2O setup, Cloudflare allows traffic to proxy through both zones sequentially:
   - Zone 1 (Customer Zone): Applies customer WAF rules and edge caching.
   - Zone 2 (Benchero SaaS Zone): Terminates custom hostname SSL, injects Benchero Worker headers, and routes to origin.
3. Requests arriving at Benchero include the header `CF-Connecting-O2O: 1`.

### SaaS Provider Invariance:
Benchero requires no custom application logic for O2O:
- The `benchero-saas-proxy` Worker runs in Zone 2 and injects `X-Benchero-Worker-Secret` normally.
- The origin PHP application authenticates the request through the identical trusted proxy mechanism.

---

## 7. Cloudflare API Token Configuration

Generate a dedicated API token for the Benchero backend:

1. In the Cloudflare Dashboard, go to **My Profile** > **API Tokens** > **Create Token**.
2. Select **Create Custom Token** with the following permissions:
   - `Zone` > `Custom Hostnames` > `Edit`
   - `Zone` > `SSL and Certificates` > `Edit`
   - `Zone` > `Zone` > `Read`
3. Resource:
   - `Include` > `Specific zone` > `benchero.co.ke`
4. Add credentials to Benchero production environment:
   ```ini
   CLOUDFLARE_CUSTOM_HOSTNAMES_ENABLED=true
   CLOUDFLARE_API_TOKEN="your_token_here"
   CLOUDFLARE_ZONE_ID="your_zone_id_here"
   CLOUDFLARE_FALLBACK_ORIGIN="cname.benchero.co.ke"
   CLOUDFLARE_WORKER_SECRET="your_secure_hex_secret_here"
   CLOUDFLARE_VALIDATE_PROXY_IP=false
   ```
