# Notifications V2 Contract

## Canonical API Namespace

- `GET /api/v1/notifications`
- `GET /api/v1/notifications/unread-count`
- `POST /api/v1/notifications/{id}/read`
- `POST /api/v1/notifications/read-all`

## NotificationType Enum

- `MATCH_FOUND`
- `FIRST_RESPONSE`
- `OFFER_ACCEPTED`
- `OFFER_REJECTED`
- `RTD_STARTED`
- `PAYMENT_STATUS_CHANGED`

## NavigationType Enum

- `CHAT_THREAD`
- `SESSION`
- `INQUIRY`
- `RTD_ORDER`
- `PAYMENT`

## Required Meta Schema per NotificationType

- `MATCH_FOUND`: `inquiry_id`, `material_name`, `counterparty_name`
- `FIRST_RESPONSE`: `inquiry_id`, `responder_name`
- `OFFER_ACCEPTED`: `offer_id`, `inquiry_id`
- `OFFER_REJECTED`: `offer_id`, `inquiry_id`
- `RTD_STARTED`: `rtd_order_id`, `counterparty_name`
- `PAYMENT_STATUS_CHANGED`: `payment_id`, `status`, `reference_id`

## Dedupe Key Spec

- `MATCH_FOUND`: `match_found_{match_id}` or recipient-scoped `match_found_{match_id}_{user_id}`
- `FIRST_RESPONSE`: `first_response_{thread_id}`
- `OFFER_ACCEPTED`: `offer_accepted_{offer_id}`
- `OFFER_REJECTED`: `offer_rejected_{offer_id}`
- `RTD_STARTED`: `rtd_started_{rtd_order_id}`
- `PAYMENT_STATUS_CHANGED`: `payment_status_{payment_id}_{status}`

## Invariants

- Chat notifications must always use:
  - `navigation_type = CHAT_THREAD`
  - `navigation_id = {thread_id}`
- Read state is `read_at` (`NULL` = unread).
- Duplicate notifications are blocked by `UNIQUE (user_id, dedupe_key)`.

