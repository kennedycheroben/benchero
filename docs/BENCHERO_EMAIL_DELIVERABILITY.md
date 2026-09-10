# BENCHERO — Email Deliverability & Domain Authentication Guide

**Domain:** `benchero.co.ke`  
**From Address:** `contact@benchero.co.ke`  
**SMTP Provider:** Truehost / Custom cPanel Mail Host  

---

## 1. Overview & Important Principles

Improving email deliverability (preventing registration verification and password reset emails from going to Spam/Junk folders) requires a two-part approach:

1. **Application Code Improvements (Completed):**
   - Setting a legitimate envelope `From:` address (`contact@benchero.co.ke`) matching the domain.
   - Setting standard MIME headers (`MIME-Version: 1.0`, `Content-Type: multipart/alternative`, `Message-ID`, `Date`, `Reply-To`).
   - Providing clean, responsive HTML and plain-text multipart versions.
   - Avoiding spam-triggering language (such as "URGENT", "CLICK FREE NOW", etc.).

2. **External DNS & Domain Authentication (Manual Actions Required):**
   - Changing PHP code alone **CANNOT** bypass spam filters if domain authentication records (SPF, DKIM, DMARC) are missing at the DNS registrar level.
   - Receiving mail servers (Gmail, Yahoo, Outlook) require cryptographic proof that the sending server is authorized to send emails on behalf of `benchero.co.ke`.

---

## 2. Required External DNS Actions

> [!IMPORTANT]
> The following DNS TXT records MUST be added or verified in your cPanel DNS Zone Editor or domain registrar for `benchero.co.ke`.

### A. SPF (Sender Policy Framework) Record
SPF specifies which mail servers are permitted to send email on behalf of your domain.

- **Record Type:** `TXT`
- **Host / Name:** `@` or `benchero.co.ke.`
- **Record Value:**
  ```text
  v=spf1 mx include:_spf.truehost.cloud ~all
  ```
  *(Note: Replace `include:_spf.truehost.cloud` with your actual hosting provider's SPF include string if using a different provider).*

### B. DKIM (DomainKeys Identified Mail) Record
DKIM attaches a digital signature to emails sent from your domain, allowing receiving servers to verify that the message was not tampered with in transit.

- **Record Type:** `TXT`
- **Host / Name:** `default._domainkey` or as generated in cPanel -> Email Deliverability.
- **Record Value:**
  - Obtain the exact public key record string directly from **cPanel -> Email Deliverability -> Manage `benchero.co.ke`**.
  - Do NOT copy placeholder values from external guides; each host generates a unique DKIM key pair.

### C. DMARC (Domain-based Message Authentication, Reporting & Conformance) Record
DMARC tells receiving email servers what to do if an email fails SPF or DKIM checks.

- **Record Type:** `TXT`
- **Host / Name:** `_dmarc` or `_dmarc.benchero.co.ke.`
- **Recommended Initial Value:**
  ```text
  v=DMARC1; p=none; sp=none; rua=mailto:contact@benchero.co.ke; ruf=mailto:contact@benchero.co.ke; rf=afrf; pct=100
  ```
  - `p=none`: Monitors email delivery without rejecting messages while validating SPF and DKIM setup.
  - Once deliverability is verified clean, upgrade policy to `p=quarantine` or `p=reject`.

---

## 3. Production Verification Procedure

After adding the DNS records:

1. **Verify DNS Propagation:**
   - Use `dig TXT benchero.co.ke` or MXToolbox to verify SPF, DKIM, and DMARC TXT records.
2. **Send Test Email:**
   - Trigger a new account registration on `https://benchero.co.ke/register`.
3. **Inspect Email Headers:**
   - Open the verification email in Gmail/Outlook.
   - Click "Show Original" or "View Message Details".
   - Confirm that `SPF`, `DKIM`, and `DMARC` show **`PASS`**.
