# Phase 4 Foundation Security Review

## Verdict
**APPROVED FOR TASK 5**

## Findings

| Component | Finding | Severity | Required action |
| --------- | ------- | -------- | --------------- |
| `RateLimiter.php` | The original implementation used a non-atomic `SELECT` followed by `UPDATE/INSERT`. This introduced a Time-Of-Check-To-Time-Of-Use (TOCTOU) race condition where concurrent requests could bypass the limits. | **CRITICAL** | **FIXED:** Refactored `hit()` to use an atomic `INSERT ... ON DUPLICATE KEY UPDATE` and fetch the result afterward. |
| `SmtpMailer.php` | The implementation relied on PHP's internal `mail()` function, ignoring the passed-in SMTP credentials, host, and encryption arguments. This violated the requirement that credentials not be hardcoded and TLS be respected. | **HIGH** | **FIXED:** Replaced the `mail()` wrapper with a raw PHP socket implementation (`fsockopen`) that explicitly authenticates, negotiates STARTTLS, and uses the provided configuration. |
| `AuthTokenService.php` | Cryptographically secure token generation (`random_bytes`) and SHA-256 hashing applied correctly. Lookup by hash is standard and secure. Explicit invalidation handles previously active tokens safely. | **LOW** | None. Architecture is secure. |
| `AuthService.php` | Securely uses `password_hash()` and `password_verify()` with `PASSWORD_ARGON2ID`. Does not log passwords or contain view/rendering logic. | **LOW** | None. |
| `CsrfMiddleware.php` | Token generation is cryptographically secure. Validation uses `hash_equals()` for timing-attack resistance. | **LOW** | None. Ready for integration. |
| `SessionMiddleware.php`| `HttpOnly`, `SameSite=Lax`, and `Secure` (via ENV) flags are implemented properly. Directory handling is safe. | **LOW** | Ensure `AuthService` regenerates IDs correctly during login. |
| DB Migrations | Constraints and column definitions are sound. Deleting a user safely cascades to `auth_tokens`. `rate_limits` operates independently and safely. | **LOW** | None. |

## Required Fixes Status
- [x] Rate Limiter atomic implementation.
- [x] SMTP Mailer socket/TLS implementation.

The foundation is solid and secure. We are ready to proceed with **Task 5: Registration & Email Verification**.
