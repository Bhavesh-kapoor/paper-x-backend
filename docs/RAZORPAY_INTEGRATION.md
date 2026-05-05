# Razorpay Wallet Integration (Phase 1 - Backend)

This document describes how to configure, operate, and switch keys for the Razorpay
collection flow that powers credit-pack purchases. Phase 1 is backend-only; the React
Native checkout integration is Phase 2.

## Endpoints

| Method | Path | Auth | Purpose |
|--------|------|------|---------|
| `POST` | `/api/v1/wallet/payments/razorpay/order` | Bearer | Create a Razorpay order for a `credit_pack_id`. |
| `POST` | `/api/v1/wallet/payments/razorpay/verify` | Bearer | Verify checkout signature, cross-check payment, credit wallet (idempotent). |
| `POST` | `/api/v1/webhooks/razorpay` | Public (HMAC) | Razorpay reconciliation webhook (`payment.captured`, `payment.failed`). |

The legacy `POST /api/v1/wallet/purchase` endpoint returns `410 Gone` unless
`APP_FAKE_PAYMENTS=true`.

## Environment variables

Add to `.env` (already documented in `.env.example`):

```dotenv
RAZORPAY_KEY_ID=
RAZORPAY_KEY_SECRET=
RAZORPAY_WEBHOOK_SECRET=
RAZORPAY_CURRENCY=INR

APP_FAKE_PAYMENTS=false
```

- `RAZORPAY_KEY_ID` / `RAZORPAY_KEY_SECRET` — issued by Razorpay (test or live mode).
  **Never** commit secrets; never log them.
- `RAZORPAY_WEBHOOK_SECRET` — set in the Razorpay dashboard when you create the webhook.
- `APP_FAKE_PAYMENTS` — when `true`, the legacy `/wallet/purchase` endpoint is enabled
  for local development. Production must be `false`.

The service reads keys exclusively via `config('services.razorpay.*')` and
`config('app.fake_payments')`. They are never read from anywhere else.

## Razorpay dashboard setup

1. Create test mode keys → store in `.env`.
2. Add a webhook:
   - **URL:** `<API_BASE>/api/v1/webhooks/razorpay`
   - **Events:** at minimum `payment.captured` and `payment.failed`.
   - **Secret:** copy into `RAZORPAY_WEBHOOK_SECRET`.
3. After KYC, repeat for live-mode keys/webhook and rotate `.env`.

## Test cards (Razorpay test mode)

- Success card: `4111 1111 1111 1111`, any future expiry, any CVV.
- Failure card: `5104 0600 0000 0008`.
- See [Razorpay test card numbers](https://razorpay.com/docs/payments/payments/test-card-details/)
  for the canonical list (UPI, netbanking, EMI variants).

## Switching keys (test ↔ live)

1. Update `.env`:
   - `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`.
2. Run `php artisan config:clear` (and `config:cache` in production).
3. Restart workers / PHP-FPM / `php artisan serve`.
4. Verify with a small test pack purchase and check the Razorpay dashboard payment.

## Operational notes

- **Idempotency anchor:** the `wallet_payment_orders.razorpay_order_id` unique index.
  Both `/verify` and the webhook lock that row with `SELECT ... FOR UPDATE` and skip
  re-crediting if `status = 'paid'`.
- **Wallet lock:** the wallet row is also locked inside the same transaction, so
  concurrent fulfillments for the same user cannot lose updates.
- **`payment.fetch` cross-check:** before crediting, the service calls
  `payments.fetch($paymentId)` and asserts `order_id`, `status === 'captured'`,
  `amount === amount_paise`, `currency`. Any mismatch sets `status = 'failed'` and
  returns `422` (no credits granted).
- **Throttles:** `create-order` is `10/min/user`, `verify` is `30/min/user`.
- **Logs:** failure paths log under `rzp.create_order_failed`, `rzp.verify_failed`,
  `rzp.webhook_domain`, `rzp.webhook_failed`. Secret values must never be logged.

## Local sandbox checklist

1. `composer install`
2. Set test-mode env vars and run `php artisan migrate`.
3. Start the app: `php artisan serve`.
4. (If testing the webhook locally) Expose the dev URL via a tunnel (ngrok / cloudflared)
   and update the dashboard webhook URL to the tunneled domain.
5. Use Razorpay Postman / SDK to create a test order via the API, complete checkout
   with a test card, and confirm:
   - `wallet_payment_orders.status` flips to `paid`.
   - A new `wallet_transactions` row with `transaction_type = 'PURCHASE'` is created.
   - `wallets.balance` increased by `pack.credits` exactly once.

## Related code

- Service: `app/Services/Payments/RazorpayPaymentService.php`
- Client contract: `app/Services/Payments/Contracts/RazorpayClient.php`
  - Real impl: `app/Services/Payments/RazorpayGatewayClient.php`
  - Test fake: `app/Services/Payments/FakeRazorpayClient.php`
- Controller: `app/Http/Controllers/api/WalletPaymentController.php`
- Form requests: `app/Http/Requests/Wallet/{CreateRazorpayOrderRequest,VerifyRazorpayPaymentRequest}.php`
- Model: `app/Models/WalletPaymentOrder.php`
- Migration: `database/migrations/2026_05_04_000001_create_wallet_payment_orders_table.php`
- Tests: `tests/Feature/Wallet/`, `tests/Unit/Payments/`
