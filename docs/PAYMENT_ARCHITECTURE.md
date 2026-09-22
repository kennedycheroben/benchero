# Benchero Unified Two-Gateway Payment Architecture

## 1. Overview

Benchero utilizes a unified two-gateway payment architecture designed for reliability, strict security, and simplicity. All payments—whether originating from Kenya via Safaricom M-Pesa or internationally via PayPal / Card—feed into a single normalized billing pipeline that controls organization subscription lifecycles and platform access.

```
                   Benchero Customer Checkout
                                │
                 ┌──────────────┴──────────────┐
                 │                             │
          Kenya (M-Pesa)           International (PayPal/Card)
                 │                             │
        I&M Bank M-Pesa Adapter        PayPal Checkout Adapter
                 │                             │
                 └──────────────┬──────────────┘
                                │
             Server-Side Verification / Webhook Callback
                                │
                 Unified Payment Intent & Payment Record
                                │
                     Subscription Lifecycle
                     (Active / Trial / Expired)
                                │
                      Access Control Gate
                 (Public Club Profile Visibility)
```

---

## 2. Supported Gateways & Providers

### A. Kenya: I&M Bank M-Pesa Gateway (`imbank`)
- **Payment Method**: Safaricom M-Pesa STK Push (`mpesa`)
- **Transaction Currency**: Kenyan Shilling (`KES`)
- **Target Audience**: Kenyan sports clubs, administrators, and teams
- **Flow**: Customer enters phone number (e.g., `0712345678` or `254712345678`) -> Benchero creates pending payment intent -> I&M STK Push prompt sent to handset -> Customer inputs PIN -> I&M server-to-server callback verified -> Payment recorded -> Subscription activated.

### B. International: PayPal Checkout (`paypal`)
- **Payment Methods**: PayPal Wallet and eligible Debit/Credit Cards (Visa, MasterCard, American Express) processed through PayPal's checkout experience.
- **Transaction Currency**: Configurable international currency (Default: `USD`; also supports `EUR`, `GBP`).
- **Target Audience**: International clubs, diaspora supporters, and overseas academies.
- **Payment Model**: **One-time checkout payments** using PayPal v2 Orders API with `intent: CAPTURE`. Benchero internally controls and calculates the subscription period (e.g., 30 days for monthly, 1 year for yearly). Benchero intentionally does not use PayPal's recurring subscription engine, keeping billing unified with M-Pesa.
- **Flow**: Customer selects plan -> Benchero determines international tier pricing -> Customer clicks "Pay with PayPal or Card" -> PayPal Order created with capture intent -> Customer approves in PayPal window -> Server-side capture executed -> Payment recorded -> Subscription activated.

---

## 3. Canonical Pricing & Currency Architecture

Benchero enforces a single canonical source of truth for subscription plans:

| Plan ID | Plan Name | Interval | Canonical Price (KES) | International Tier (USD) | Entitlements |
|---|---|---|---|---|---|
| 1 | Free Trial | 14 Days | KSh 0 | $0.00 | Full feature trial for new clubs |
| 2 | Standard Monthly | Monthly | KSh 1,000 | $8.00 | 100 Players, 10 Teams, 500 Fixtures |
| 3 | Standard Yearly | Yearly | KSh 10,000 | $80.00 | 500 Players, 25 Teams, 2,000 Fixtures |
| 4 | Benchero Pro Yearly | Yearly | KSh 20,000 | $160.00 | Full Pro: Custom Domain, Video, Media, QR, Digital Card |
| 5 | Benchero Pro Monthly | Monthly | KSh 2,500 | $20.00 | Full Pro: Custom Domain, Video, Media, QR, Digital Card |

### Currency Architecture & Policy:
1. **Canonical Source of Truth**: All plan pricing is canonically defined in Kenyan Shillings (`KES`):
   - Pro Monthly: **KSh 2,500 / month**
   - Pro Yearly: **KSh 20,000 / year**
2. **Explicit International Pricing Tiers**:
   - Because PayPal does not support Kenyan Shillings (`KES`) for merchant checkout or direct settlement, international customers are charged in a supported provider checkout currency (primarily `USD`).
   - The USD amounts ($20.00 / month, $160.00 / year) are **explicit international subscription pricing tiers**, configured via `PAYPAL_PLAN_PRO_MONTHLY_USD` and `PAYPAL_PLAN_PRO_YEARLY_USD`.
   - Benchero does **NOT** use a dynamic or fake foreign exchange rate. In the database, `exchange_rate` is tracked as `NULL` (or omitted) to truthfully reflect that no FOREX conversion was promised or computed by Benchero.
3. **Database Currency Tracking**:
   - `amount`: Charged provider transaction amount (e.g. `20.00`)
   - `currency`: Charged provider currency (e.g. `USD`)
   - `base_amount`: Canonical plan price (e.g. `2500.00`)
   - `base_currency`: `KES`
   - `exchange_rate`: `NULL` (reflecting explicit tier pricing, not an exact FOREX conversion)

---

## 4. Database Architecture

Database tables are designed for financial integrity and complete auditability:

### `payment_intents`
Tracks every checkout initiation before terminal confirmation:
- `id` (CHAR 26, ULID)
- `organization_id` (CHAR 26)
- `user_id` (CHAR 26, nullable)
- `plan_id` (INT)
- `payment_intent_id` (VARCHAR 64, unique)
- `reference` (VARCHAR 64, unique checkout reference `CHK-...`)
- `amount` (DECIMAL 10,2) — Charged provider amount
- `currency` (VARCHAR 3) — e.g. `KES` or `USD`
- `base_amount` (DECIMAL 10,2) — Canonical KES amount
- `base_currency` (VARCHAR 3) — `KES`
- `exchange_rate` (DECIMAL 10,4, nullable) — `NULL` for international tier pricing
- `phone_number` (VARCHAR 20, nullable for international checkouts)
- `payer_email` (VARCHAR 255, nullable)
- `payer_id` (VARCHAR 100, nullable)
- `provider` (`imbank` or `paypal`)
- `payment_method` (`mpesa`, `paypal`, `card`)
- `provider_reference` (STK checkout ID or PayPal Order ID)
- `status` (`pending`, `initiated`, `completed`, `failed`, `cancelled`, `expired`)
- `result_code`, `result_desc`, `mpesa_receipt_number`
- `expires_at`, `created_at`, `updated_at`

### `payments`
Immutable record of verified payments:
- `id` (CHAR 26, ULID)
- `organization_id` (CHAR 26)
- `user_id` (CHAR 26)
- `subscription_id` (CHAR 26)
- `payment_intent_id` (CHAR 26)
- `amount` (DECIMAL 10,2)
- `currency` (VARCHAR 3)
- `base_amount` (DECIMAL 10,2)
- `base_currency` (VARCHAR 3)
- `exchange_rate` (DECIMAL 10,4, nullable)
- `status` (`completed`, `refunded`)
- `payment_method` (`mpesa`, `paypal`, `card`)
- `provider` (`imbank`, `paypal`)
- `mpesa_receipt_number` (Unique receipt or PayPal capture ID, with UNIQUE constraint)
- `provider_reference` (Unique provider reference, with UNIQUE constraint)
- `payer_email`, `payer_id`
- `metadata` (JSON payload containing safe audit information)
- `created_at`, `updated_at`

### `subscriptions`
- `provider`: records provider used (`imbank`, `paypal`, or legacy `mpesa`).
- `status`: `trialing`, `active`, `expired`, `canceled`, `past_due`.
- `starts_at`, `expires_at`, `current_period_end`.
- `payment_reference`: records latest payment receipt.

---

## 5. Subscription Lifecycle & Expiration Logic

1. **Trial State**:
   - New organizations receive a 14-day free trial on Plan 1.
   - Public club profiles (`/club/{slug}`) are visible during active trial.
2. **Paid Activation**:
   - Only server-side verified payments activate subscriptions.
   - Monthly plans grant `+30 days`.
   - Yearly plans grant `+1 year`.
3. **Renewal & Stacking**:
   - If an active subscription renews on the same plan, the new duration stacks on top of the remaining period (`expires_at`), preventing premature truncation.
4. **Expiration & Access Lockdown**:
   - Once `now() >= expires_at`, `isPublicProfileVisible()` returns `false`.
   - Visitors to `/club/{slug}` receive the locked profile page (`views/public/locked_profile.php`).
   - Tenant dashboard displays expiration banners and renewal actions.

---

## 6. Webhook, Callback & Redirect Security

### I&M / M-Pesa Callback (`/billing/imbank/callback` and `/billing/mpesa/callback`):
- Optional HMAC signature verification via `X-ImBank-Signature` against `IMBANK_CALLBACK_SECRET`.
- Validates `ResultCode === 0` before marking payment completed.
- Matches `CheckoutRequestID` against existing `payment_intents`.
- Validates that callback amount matches intent amount.
- **Idempotency**: If intent is already `completed`, the callback returns success without re-extending the subscription.

### PayPal Webhook (`/billing/paypal/webhook`):
- Validates webhook transmission headers (`PAYPAL-TRANSMISSION-ID`, `PAYPAL-TRANSMISSION-TIME`, `PAYPAL-CERT-URL`, `PAYPAL-AUTH-ALGO`, `PAYPAL-TRANSMISSION-SIG`).
- Verifies authenticity via PayPal's `POST /v1/notifications/verify-webhook-signature` using `PAYPAL_WEBHOOK_ID`.
- Processes `PAYMENT.CAPTURE.COMPLETED` and `CHECKOUT.ORDER.APPROVED`.
- Matches order/capture reference against `payment_intents`.
- Validates amount and currency.
- **Idempotency**: Prevents double-crediting or duplicate subscription extensions on replayed webhooks.

### PayPal Browser Return (`/billing/paypal/return` and `/billing/paypal/cancel`):
- **Server-Side Verification**: Never trusts the browser redirect as proof of payment.
- The handler executes a server-side `capturePayment()` request against the PayPal REST API to verify and capture the funds before activating the subscription.
- If the payment was already completed via AJAX or webhook, it recognizes the completed state idempotently and redirects the user with success.

---

## 7. Multi-Layer Idempotency Defense

To prevent duplicate subscriptions, duplicate payment rows, or stacked expiration periods from duplicate webhooks, replayed callbacks, or race conditions:

1. **Layer 1: Gateway Intent Status Check**:
   Before initiating capture or processing callbacks, checks if `payment_intents.status === 'completed'`.
2. **Layer 2: Database Payment Record Check in `recordSuccessfulPayment`**:
   Before updating or inserting, checks if a payment record with the given `payment_intent_id`, `provider_reference`, or receipt already exists.
3. **Layer 3: Subscription Service Payment Reference Check in `activateSubscription`**:
   Checks if the active subscription's `payment_reference` matches the given payment reference; if it matches, it exits without extending `expires_at` a second time.
4. **Layer 4: Database Unique Constraints**:
   Unique indexes on `payments.provider_reference` and `payments.mpesa_receipt_number` ensure database-level rejection of duplicate transactions.

---

## 8. Environment Configuration Guide

Configure these variables in `.env` (refer to `.env.example`):

### Payment Provider Defaults
```env
PAYMENT_DEFAULT_PROVIDER=imbank
PAYMENT_KENYA_PROVIDER=imbank
PAYMENT_INTERNATIONAL_PROVIDER=paypal
```

### I&M Bank / M-Pesa Configuration
```env
IMBANK_ENVIRONMENT=production
IMBANK_CONSUMER_KEY=your_imbank_consumer_key
IMBANK_CONSUMER_SECRET=your_imbank_consumer_secret
IMBANK_STK_PUSH_URL=https://api.imbank.co.ke/mpesa/stkpush/v1/processrequest
IMBANK_SHORTCODE=your_paybill_number
IMBANK_PASSKEY=your_live_passkey
IMBANK_CALLBACK_SECRET=your_webhook_secret
```

### PayPal Checkout Configuration
```env
PAYPAL_ENVIRONMENT=sandbox # Set to 'production' for live payments
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
PAYPAL_WEBHOOK_ID=your_paypal_webhook_id
PAYPAL_CURRENCY=USD
PAYPAL_SUPPORTED_CURRENCIES=USD,EUR,GBP
PAYPAL_PLAN_PRO_MONTHLY_USD=20.00
PAYPAL_PLAN_PRO_YEARLY_USD=160.00
```

---

## 9. Production Readiness & Activation Checklist

### Classification: **READY FOR SANDBOX TESTING** / **READY FOR PRODUCTION CONFIGURATION**

The codebase architecture, database schemas, controllers, adapters, services, and tests are complete and production-grade.

### Remaining Steps for Live Activation:
1. **Apply Migration on Target Environment**:
   ```bash
   php bin/migrate.php
   ```
2. **Supply Production Credentials in `.env`**:
   - `PAYPAL_ENVIRONMENT=production`
   - `PAYPAL_CLIENT_ID`
   - `PAYPAL_CLIENT_SECRET`
   - `PAYPAL_WEBHOOK_ID`
   - `IMBANK_CONSUMER_KEY`
   - `IMBANK_CONSUMER_SECRET`
   - `IMBANK_SHORTCODE`
   - `IMBANK_PASSKEY`
   - `IMBANK_CALLBACK_SECRET`
3. **Register Live PayPal Webhook**:
   In the PayPal Developer Dashboard for the production app:
   - Webhook URL: `https://benchero.co.ke/billing/paypal/webhook`
   - Subscribed Events:
     - `Payment capture completed`
     - `Checkout order approved`
     - `Payment capture denied`
4. **Register I&M Bank Callback URL**:
   Ensure I&M Bank dashboard has callback configured to:
   `https://benchero.co.ke/billing/imbank/callback`
5. **Execute End-to-End Sandbox Verification**:
   - Test Kenyan customer M-Pesa STK push.
   - Test international customer PayPal checkout with PayPal balance.
   - Test international customer card payment through PayPal guest/card experience.
