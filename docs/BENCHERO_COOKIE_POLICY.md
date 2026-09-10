# BENCHERO — Cookie Policy & Cookie Management

**Application:** Benchero SaaS  
**Domain:** `https://benchero.co.ke`  

---

## 1. Executive Summary

Benchero adheres to strict privacy and security principles regarding cookie usage. The platform utilizes only essential first-party cookies necessary for secure user authentication, CSRF protection, and session management.

---

## 2. Cookie Classification

### Essential First-Party Cookies
- `benchero_session`: Server-side session identifier cookie.
  - **Attributes:** `HttpOnly=true`, `SameSite=Lax`, `Secure=true` (on HTTPS/production), `Path=/`.
  - **Purpose:** Identifies authenticated user session and tenant context.
  - **Duration:** 8 Hours.
- `_csrf`: CSRF security token cookie/session attribute.
  - **Purpose:** Prevents cross-site request forgery attacks on state-changing forms.

### Optional Preference Cookies
- `benchero_cookie_consent`: Stores user acknowledgment of the cookie notice.
  - **Duration:** 1 Year.

---

## 3. Cookie Policy Route & Page

- **URL:** `https://benchero.co.ke/cookies`
- Explains cookie categories in simple, non-technical language for sports administrators and public visitors.
