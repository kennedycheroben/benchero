# Teamora Authentication Architecture

## 1. Core Principles
- **Authentication**: Establishes user identity.
- **Tenant Context**: Determined separately by the URL path (`/o/{org-slug}`).
- **Authorization**: Validated after Auth & Context are established.

## 2. Password Security
- Passwords MUST be hashed using PHP's native `password_hash()` and `password_verify()`.
- Algorithm: Argon2id (`PASSWORD_ARGON2ID`) or `PASSWORD_DEFAULT` if Argon2 is unsupported by the target PHP runtime.
- Raw passwords are never logged, stored, or transmitted via GET requests.

## 3. Session Security
- **Regeneration**: `session_regenerate_id(true)` MUST be called immediately upon login and logout to prevent session fixation.
- **Cookies**: 
  - `HttpOnly`: true (prevent XSS access).
  - `Secure`: true in production (HTTPS only).
  - `SameSite`: 'Lax' or 'Strict'.
- **Invalidation**: Logout MUST use a `POST` request to destroy the session completely (`session_destroy()` and clear cookie).

## 4. Flows

### Registration
**Fields:** Name, Email, Password, Password Confirmation.
**Post-Registration Flow (Organization Onboarding Boundary):**
1. User completes registration form.
2. `User` record created.
3. User is prompted to verify their email.
4. **Onboarding**: The User is redirected to an onboarding flow to create an Organization.
5. The Organization is created, initiating the Trial period.
6. The User is redirected to `/o/{org-slug}/setup` to create their first Team.
*Note: Authentication does NOT prematurely create an organization.*

### Login
- Accepts Email and Password.
- Checks `deleted_at` (account status).
- Checks `email_verified_at` (requires verification before accessing orgs).
- Rate-limited to prevent brute-force attacks.
- Generic failure messages: "Invalid credentials."

### Email Verification
**Flow:** Registration -> Token Generation -> Email Sent -> Verification Endpoint -> Verified Account
- **Tokens**: Cryptographically random (via `random_bytes`), single-use, expires in 24 hours. Stored as a hash in the database.

### Password Reset
**Flow:** Forgot Password -> Token Generation -> Email Sent -> Reset Form -> New Password -> Invalidate Tokens -> Invalidate Sessions
- Tokens are single-use, expire in 60 minutes, stored hashed.
- To prevent enumeration, the API always returns a generic "If that email exists, a link has been sent" message.

## 5. Email Abstraction
Email delivery is required for verification and password resets.
- We will define a `MailerInterface`.
- **Development**: A `LogMailer` implementation will write email contents to `storage/logs/mail.log`.
- **Production**: An `SmtpMailer` implementation will use standard SMTP credentials (suitable for Truehost/cPanel).
- Configured via `.env` (SMTP host, port, encryption, user, pass, sender_address, sender_name, MAIL_MAILER).

## 6. Rate Limiting
Required for endpoints: Login, Registration, Forgot Password, Reset Password, Resend Verification.
- **Identifier**: IP Address (for reg/login), User ID/Email (for resets).
- **Storage**: Initially MySQL-compatible (e.g., a `rate_limits` table or simple file-based cache) to support shared hosting without requiring Redis.
- **Behavior**: Return HTTP 429 Too Many Requests after max attempts are reached within the time window.
