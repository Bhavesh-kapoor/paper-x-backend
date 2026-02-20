# RTD API – Postman collection

Import **`RTD-API.postman_collection.json`** and the two environments for structured testing.

## Setup

1. **Import**  
   Postman → Import → select:
   - `RTD-API.postman_collection.json`
   - `RTD-Converter.postman_environment.json`
   - `RTD-Brand.postman_environment.json`

2. **Environments**  
   - **RTD – Converter:** set `token` to the converter user’s Sanctum token.  
   - **RTD – Brand:** set `token` to the brand user’s Sanctum token.  
   - In both: set `base_url` (e.g. `http://localhost:8000/api`).  
   - `product_id` and `order_id` are set automatically by test scripts when you run the happy-path requests.

3. **Auth**  
   Collection uses **Bearer Token** with `{{token}}`. The active environment supplies `token`.

## Test scripts (automated checks)

These requests run assertions and save IDs:

- **Create Product (Converter)** – 201, `status` active, saves `product_id`.  
- **Request Order (Brand)** – 201, `status` REQUESTED, financials present, saves `order_id`.  
- **Accept Order (Converter)** – 200, `status` ACCEPTED.  
- **Confirm Payment (Brand)** – 200, `status` PAID, `paid_at` set.  
- **Confirm Delivery (Brand)** – 200, `status` COMPLETED, payout RELEASED.

Run the **Negative (expect 4xx)** folder with the right env: each request asserts 422 (or 403 where applicable).

## Suggested flow (happy path)

1. Select env **RTD – Converter**. Create Product → My Products → Get Product by ID.  
2. Select env **RTD – Brand**. Catalog → Request Order (uses saved `product_id`).  
3. Select **RTD – Converter**. Accept Order (uses saved `order_id`).  
4. Select **RTD – Brand**. Confirm Payment.  
5. Select **RTD – Converter**. Mark In Production → Dispatch (tracking or file).  
6. Select **RTD – Brand**. Confirm Delivery (or Raise Dispute).

Cancel is only valid when order status is ACCEPTED (before payment).

## Negative testing

Use the **Negative (expect 4xx)** folder. Run after you have a valid `product_id` and `order_id` where needed (e.g. “Accept Twice” and “Cancel After Payment” need an order in the right state).  
- Overlapping slabs, qty &lt; MOQ, accept twice, confirm payment before accept, cancel after payment – all expect 422.

## Notes

- **Request Order** with logo: use **form-data**, key `logo` (file).  
- **Confirm Payment** body and URL both use `{{order_id}}`.  
- `lead_time`: `SAME_DAY`, `H24`, `H48`, `DAYS_3_5`.
