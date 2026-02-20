# RTD Module – Test Suite

Structured tests for the Ready-to-Dispatch API in three layers: **Happy flow**, **Negative flow**, and **Edge/security**.

## Prerequisites

- **Fix `routes/web.php`** if you see:  
  `Cannot redeclare function renderMarkdown()`.  
  Ensure that function is declared only once (e.g. wrap in `if (!function_exists('renderMarkdown')) { ... }`).
- Database: tests use `RefreshDatabase` (e.g. SQLite in memory or your `.env.test` DB).

## Run all RTD tests

```bash
php artisan test tests/Feature/RTD tests/Unit/RTD
```

Run only unit tests (no Laravel bootstrap):

```bash
./vendor/bin/phpunit tests/Unit/RTD/CommissionCalculatorTest.php
```

## Test mapping to your test cases

| Layer | File | Test cases |
|-------|------|------------|
| Product | `RtdProductTest` | TC-P1, P2, P3, P5, P6 + My Products, Get by ID, Catalog |
| Happy flow | `RtdOrderFlowTest` | TC-O1, A1, PAY1, PAY2, D1, D3, C1, A4 (decline), C3 (dispute), cancel |
| Negative | `RtdOrderNegativeTest` | TC-A2, PAY3, O2, O3, O5, D2, D4, C2, cancel after payment |
| Financial | `RtdOrderFinancialTest` | TC-O4 (cap), Layer 9 commission slabs |
| Role & security | `RtdOrderRoleTest` | TC-A3, A5, PAY4, P4, brand/convert wrong actions |
| Unit | `CommissionCalculatorTest` | Commission %, cap, GST, total |
| Unit | `RTDOrderStateMachineTest` | Allowed transitions, no PAYMENT_PENDING |

## Order of execution (recommended)

1. Product → 2. Order request → 3. Accept → 4. Payment → 5. Production → 6. Dispatch → 7. Delivery → 8. Negative → 9. Timer (manual/shortened) → 10. Concurrency (manual).

Timer and concurrency are covered by: **TC-A3** (accept after expiry) and service design (lockForUpdate). For full timer runs, temporarily reduce acceptance/delivery in enums or config and run jobs in queue.
