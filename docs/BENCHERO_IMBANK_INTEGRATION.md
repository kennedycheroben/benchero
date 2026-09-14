# Benchero I&M Bank M-Pesa Integration Guide

## 1. Gateway Architecture
Benchero integrates Safaricom M-Pesa STK Push payments through I&M Bank's official payment gateway standard.

To ensure long-term architectural stability, Benchero implements a decoupled gateway abstraction:
- **`PaymentGatewayInterface`**: System contract for payment providers.
- **`ImBankPaymentGateway`**: Production adapter implementing I&M API communication, signature verification, phone normalization, and payload parsing.
- **`PaymentGatewayFactory`**: Resolves gateway implementations dynamically (`imbank`).

---

## 2. Environment Configuration
All provider credentials are managed securely via `.env` environment variables and must NEVER be committed to Git.

```env
# I&M Bank Integration Config
IMBANK_ENVIRONMENT=production # or 'sandbox'
IMBANK_CONSUMER_KEY=your_imbank_consumer_key
IMBANK_CONSUMER_SECRET=your_imbank_consumer_secret
IMBANK_STK_PUSH_URL=https://api.imbank.co.ke/mpesa/stkpush/v1/processrequest
IMBANK_SHORTCODE=123456
IMBANK_PASSKEY=your_imbank_passkey
IMBANK_CALLBACK_SECRET=your_webhook_signature_secret
```

---

## 3. Implemented vs. Production-Required Features

### IMPLEMENTED IN BENCHERO:
- [x] Payment Gateway Contract (`PaymentGatewayInterface`)
- [x] I&M Bank Gateway Adapter (`ImBankPaymentGateway`)
- [x] Phone number normalization (`07XX` / `01XX` / `2547XX` / `2541XX` → `2547XXXXXXXX`)
- [x] Safe development sandbox simulation mode when credentials are missing or during testing (`APP_ENV=testing`)
- [x] Webhook Callback Controller (`MpesaCallbackController`)
- [x] HMAC SHA-256 Signature Verification (`X-ImBank-Signature`)
- [x] Flexible JSON payload parsing (supporting flat JSON & Safaricom nested `stkCallback` structures)
- [x] Server-side price validation & payment intent state machine
- [x] Database transaction wrapping for payment creation + subscription activation
- [x] Callback idempotency and duplicate transaction protection
- [x] Real-time AJAX status polling endpoint (`GET /o/{slug}/billing/payment-intent/{id}/status`)
- [x] Super Admin Reconciliation interface (`/admin/payments`)

### REQUIRES I&M BANK ONBOARDING / PRODUCTION CONFIGURATION:
- [ ] Provisioning of live I&M Bank PayBill API credentials (`IMBANK_CONSUMER_KEY`, `IMBANK_CONSUMER_SECRET`, `IMBANK_PASSKEY`).
- [ ] Registration of the official production Callback URL in the I&M Portal:
  `https://benchero.co.ke/billing/imbank/callback` (or `https://your-domain.com/billing/imbank/callback`)
- [ ] Configuring server firewall / cPanel to allow incoming webhook requests from I&M Bank IP ranges if applicable.
- [ ] Setting `IMBANK_ENVIRONMENT=production` in `.env`.

---

## 4. End-to-End Payment Flow

1. **Client Action**: Customer selects a plan and enters their M-Pesa phone number in Benchero.
2. **Intent Creation**: Benchero validates the plan price on the server, generates a unique `payment_intent_id`, and stores the record in `payment_intents` with status `pending`.
3. **STK Push Initiation**: Benchero formats the request payload (including `BusinessShortCode`, `Timestamp`, base64 `Password`, `Amount`, `PhoneNumber`, and `CallBackURL`) and posts it to the I&M STK Push URL.
4. **M-Pesa PIN Prompt**: Customer receives the Safaricom STK prompt on their mobile phone and enters their M-Pesa PIN.
5. **Callback Verification**: I&M posts the transaction result to Benchero's callback endpoint (`/billing/imbank/callback`).
6. **Activation**: Benchero verifies the callback signature, matches the checkout reference, confirms the amount, updates the intent status to `completed`, writes the ledger entry to `payments`, and activates/extends the subscription via `SubscriptionService`.
7. **Frontend Auto-Update**: The customer's browser polls `/billing/payment-intent/{id}/status`, detects the `completed` status, displays a success notification, and updates the UI automatically.
