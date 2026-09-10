# Task 5 Verification Report

## Verdict
VERIFIED COMPLETE

## Test Results

| Test | Result | Evidence |
| ---- | ------ | -------- |
| Valid registration | PASS | `tests/run_security_tests.php` and DB state verified. User correctly created with `password_hash`. Email normalized to lowercase. |
| Duplicate registration | PASS | Handled gracefully without error. Returns the exact same success response as valid registration, preventing enumeration. |
| Password validation | PASS | Tested short passwords (< 8 chars) and mismatched passwords. Form correctly rejects them with a 400 Bad Request error. |
| CSRF protection | PASS | Tested submitting registration without the `_csrf` token. Middleware correctly intercepted it and returned a 403 Forbidden. |
| Token valid/invalid/expired | PASS | Automated tests in `tests/Task5Test.php` verified logic bounds. Invalid, expired, or reused tokens are rejected. Single-use is enforced. |
| Resend verification | PASS | Rate limiting (3/15m) enforced. Valid requests send an email, while reusing exact anti-enumeration responses regardless of account state. |

## Security Review

*   **Password Hashing:** Safely implements `password_hash()` over the user's password input. The column is correctly labeled `password_hash`.
*   **CSRF:** Registration forms successfully enforce the global CSRF token policy using POST data.
*   **Token Security:** Tokens are generated via `random_bytes(32)` yielding 64-character hex strings, which are then hashed via `sha256` before storing in the database. Raw tokens are never stored. Token lookups use timing-safe verification.
*   **Rate Limiting:** Atomic upserts within `RateLimiter` function correctly, tested directly via `resendVerification` and `register` controllers.
*   **Mail Safety:** `storage/logs/mail.log` is securely maintained outside the web root. However, the raw plaintext URL token *is* logged locally for development. This is purely development-only behavior and must not be pushed to production. Production SMTP does not expose the token in logs.
*   **Error Handling:** Missing fields, DB errors, or validation issues are caught cleanly by the controller and returned with generic or validation-specific UI messages without exposing stack traces, credentials, or SQL.
*   **Session Handling:** The PHP native session securely tracks the CSRF tokens and sets `tmsess` with `HttpOnly` and `SameSite=Lax`.

## Password Algorithm

*   **Actual Development Algorithm:** `PASSWORD_DEFAULT` (resolves to `2y` / BCrypt on this specific PHP 8.2 XAMPP environment because Argon2id bindings were omitted during XAMPP compilation).
*   **Actual Production Recommendation:** Argon2id is strongly recommended. However, since the current local stack throws fatal errors if forced, we use `PASSWORD_DEFAULT`. 
*   **Rehash Strategy:** The application will use `password_needs_rehash()` natively at login. If the production server supports Argon2id and configures it as `PASSWORD_DEFAULT`, the system will seamlessly begin rehashing any BCrypt hashes upon their next successful login.

## Remaining Issues

None critical. 

*   **LOW:** Ensure that production deployments do not inadvertently enable `MAIL_MAILER=log` to prevent plaintext verification links from entering server logs.

## Deferred Items

*   Task 6: Login and Logout (which will leverage `password_verify` and `password_needs_rehash`).
*   Task 7: Forgot/Reset Password flows.
*   Organization Onboarding boundaries (post-login flows).
