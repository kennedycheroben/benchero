# Billing Architecture

## Design Model
Teamora uses an Organization-level subscription model.

## Entities
1. **Plans (`plans`)**: Platform-level definition of features, pricing (`price_kes`), and eventually billing intervals.
2. **Subscriptions (`subscriptions`)**: The active commercial agreement for an Organization. One per organization. Stores `status` (active, past_due, canceled, trialing), `plan_id`, and `billing_interval`.
3. **Payments (`payments`)**: Individual ledger entries representing money collected or refunded.

## Integrity
- Subscriptions and Payments cannot be cascade-deleted if an Organization is deleted (`ON DELETE RESTRICT`). Financial records are immutable ledgers.
- Unique M-Pesa receipt numbers are enforced via a unique index, preventing double-crediting of the same mobile money transaction. Multiple `NULL` receipts are allowed for pending payments.

## M-Pesa Integration (Phase 6)
- C2B/STK Push interactions will be logged via an unauthenticated, highly-secured webhook endpoint.
- Webhooks will be verified via IP whitelisting and payload signatures.
