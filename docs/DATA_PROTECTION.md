# Data Protection & Privacy

## Principles
- **Data Minimization:** We only collect data essential for providing the sports management service.
- **Tenant Isolation:** Data belonging to one Organization must never bleed into another. Enforced globally via `TenantMiddleware` and strictly scoped database queries.

## Personally Identifiable Information (PII)
- Passwords are cryptographically hashed (Argon2id).
- Emails are unique identifiers and used strictly for authentication, verification, and critical service notices.

## Deletion and Retention
- **Soft Deletes:** (`deleted_at`) Used extensively across the platform (Users, Organizations, Teams, Players). This allows for audit trails and recovery.
- **Right to be Forgotten:** Hard deletion workflows will be built in Phase 8 (Legal) to comply with data protection regulations, ensuring all PII is scrubbed while maintaining anonymized financial ledgers.
