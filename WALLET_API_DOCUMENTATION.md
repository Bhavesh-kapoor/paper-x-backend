# Wallet & Payment API Documentation

## Overview
This document describes the Wallet and Payment APIs for the Zupply B2B Platform. The wallet system allows users to purchase credits, track transactions, and use credits for various platform features.

## Base URL
```
/api/v1/wallet
```

All endpoints require authentication via Sanctum token.

---

## 1. Get Wallet Balance

Get the current wallet balance and details for the authenticated user.

**Endpoint:** `GET /api/v1/wallet`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response:**
```json
{
    "success": true,
    "data": {
        "wallet_id": "B2B-00001-WP",
        "balance": 150.00,
        "status": "ACTIVE",
        "created_at": "2026-01-16 22:00:00"
    }
}
```

---

## 2. Get Credit Packs

Get all available credit packs for purchase.

**Endpoint:** `GET /api/v1/wallet/credit-packs`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Starter",
            "slug": "starter",
            "credits": 50,
            "price": 500.00,
            "gst_percentage": 18.00,
            "gst_amount": 90.00,
            "total_price": 590.00,
            "description": "Perfect for getting started with matchmaking",
            "validity": "Lifetime",
            "is_best_value": false
        },
        {
            "id": 2,
            "name": "Growth",
            "slug": "growth",
            "credits": 120,
            "price": 1000.00,
            "gst_percentage": 18.00,
            "gst_amount": 180.00,
            "total_price": 1180.00,
            "description": "Best value for growing businesses",
            "validity": "Lifetime",
            "is_best_value": true
        },
        {
            "id": 3,
            "name": "Business",
            "slug": "business",
            "credits": 300,
            "price": 2500.00,
            "gst_percentage": 18.00,
            "gst_amount": 450.00,
            "total_price": 2950.00,
            "description": "Ideal for established businesses",
            "validity": "Lifetime",
            "is_best_value": false
        },
        {
            "id": 4,
            "name": "Factory",
            "slug": "factory",
            "credits": 750,
            "price": 5000.00,
            "gst_percentage": 18.00,
            "gst_amount": 900.00,
            "total_price": 5900.00,
            "description": "Maximum credits for large operations",
            "validity": "Lifetime",
            "is_best_value": false
        }
    ]
}
```

---

## 3. Calculate Custom Credits

Calculate credits and pricing for a custom amount.

**Endpoint:** `POST /api/v1/wallet/calculate`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

**Request Body:**
```json
{
    "amount": 1000
}
```

**Validation Rules:**
- `amount`: Required, numeric, minimum 100

**Response:**
```json
{
    "success": true,
    "data": {
        "amount": 1000.00,
        "gst_percentage": 18.00,
        "gst_amount": 180.00,
        "total_amount": 1180.00,
        "credits": 100
    }
}
```

**Note:** Credit calculation rate: ₹100 = 10 credits

---

## 4. Purchase Credits

Purchase credits from a pack or custom amount.

**Endpoint:** `POST /api/v1/wallet/purchase`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

**Request Body (From Pack):**
```json
{
    "credit_pack_id": 2,
    "payment_method": "UPI"
}
```

**Request Body (Custom Amount):**
```json
{
    "amount": 1000,
    "gst_percentage": 18,
    "payment_method": "NET_BANKING"
}
```

**Validation Rules:**
- `credit_pack_id`: Required if `amount` not provided, must exist in `credit_packs` table
- `amount`: Required if `credit_pack_id` not provided, numeric, minimum 100
- `gst_percentage`: Optional, numeric, 0-100 (default: 18)
- `payment_method`: Optional, string, one of: UPI, NET_BANKING, CARDS

**Response:**
```json
{
    "success": true,
    "message": "Credits added successfully!",
    "data": {
        "transaction_id": "TXN-00001",
        "credits_added": 120,
        "new_balance": 120.00,
        "amount_paid": 1180.00
    }
}
```

**Note:** Payment gateway integration will be added later. Currently, this records the purchase and adds credits to the wallet.

---

## 5. Add Credits (Admin/System)

Add credits to a user's wallet (for admin adjustments, referrals, etc.).

**Endpoint:** `POST /api/v1/wallet/add`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

**Request Body:**
```json
{
    "credits": 50,
    "description": "Referral Bonus",
    "transaction_type": "REFERRAL_BONUS",
    "reference_id": "REF-123",
    "reference_type": "referral",
    "metadata": {
        "referral_code": "FRIEND50"
    }
}
```

**Validation Rules:**
- `credits`: Required, numeric, minimum 1
- `description`: Required, string, max 255
- `transaction_type`: Optional, string, one of: PURCHASE, REFERRAL_BONUS, REFUND, ADMIN_ADJUSTMENT, OTHER
- `reference_id`: Optional, string
- `reference_type`: Optional, string
- `metadata`: Optional, array

**Response:**
```json
{
    "success": true,
    "message": "Credits added successfully!",
    "data": {
        "transaction_id": "TXN-00002",
        "credits_added": 50,
        "new_balance": 170.00
    }
}
```

---

## 6. Get Transaction History

Get wallet transaction history with filters and pagination.

**Endpoint:** `GET /api/v1/wallet/transactions`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters:**
- `type`: Optional, string, one of: ALL, ADDED, DEDUCTED (default: ALL)
- `transaction_type`: Optional, string (e.g., PURCHASE, REQUIREMENT_POSTED, etc.)
- `date_from`: Optional, date (YYYY-MM-DD)
- `date_to`: Optional, date (YYYY-MM-DD)
- `per_page`: Optional, integer (default: 20)

**Example Request:**
```
GET /api/v1/wallet/transactions?type=ADDED&per_page=10
```

**Response:**
```json
{
    "success": true,
    "data": {
        "wallet_id": "B2B-00001-WP",
        "balance": 150.00,
        "status": "ACTIVE",
        "transactions": [
            {
                "id": 1,
                "transaction_id": "TXN-00001",
                "type": "ADDED",
                "amount": 120.00,
                "credits": "+120",
                "balance_after": 120.00,
                "description": "Growth - 120 Credits",
                "transaction_type": "PURCHASE",
                "reference_id": null,
                "reference_type": null,
                "created_at": "2026-01-16 22:00:00",
                "date": "Jan 16",
                "time": "22:00"
            },
            {
                "id": 2,
                "transaction_id": "TXN-00002",
                "type": "DEDUCTED",
                "amount": 50.00,
                "credits": "-50",
                "balance_after": 70.00,
                "description": "Requirement Posted",
                "transaction_type": "REQUIREMENT_POSTED",
                "reference_id": "INQ-123",
                "reference_type": "inquiry",
                "created_at": "2026-01-17 10:30:00",
                "date": "Jan 17",
                "time": "10:30"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 2,
            "last_page": 1
        }
    }
}
```

---

## 7. Deduct Credits

Deduct credits from wallet (for posting requirements, deals, etc.).

**Endpoint:** `POST /api/v1/wallet/deduct`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

**Request Body:**
```json
{
    "credits": 50,
    "description": "Requirement Posted",
    "transaction_type": "REQUIREMENT_POSTED",
    "reference_id": "INQ-123",
    "reference_type": "inquiry",
    "metadata": {
        "inquiry_title": "Need 1000kg Paper"
    }
}
```

**Validation Rules:**
- `credits`: Required, numeric, minimum 1
- `description`: Required, string, max 255
- `transaction_type`: Optional, string, one of: REQUIREMENT_POSTED, DEAL_CLOSED, MACHINERY_INSPECTION, LISTING_FEE, PREMIUM_FEATURE, OTHER
- `reference_id`: Optional, string
- `reference_type`: Optional, string
- `metadata`: Optional, array

**Response (Success):**
```json
{
    "success": true,
    "message": "Credits deducted successfully!",
    "data": {
        "transaction_id": "TXN-00003",
        "credits_deducted": 50,
        "new_balance": 100.00
    }
}
```

**Response (Insufficient Balance):**
```json
{
    "success": false,
    "message": "Insufficient credits. Please purchase more credits.",
    "data": {
        "current_balance": 30.00,
        "required": 50
    }
}
```

---

## Transaction Types

### For Added Credits:
- `PURCHASE`: Credits purchased from pack or custom amount
- `REFERRAL_BONUS`: Credits from referral program
- `REFUND`: Credits refunded
- `ADMIN_ADJUSTMENT`: Credits added by admin
- `OTHER`: Other types

### For Deducted Credits:
- `REQUIREMENT_POSTED`: Credits used for posting a requirement
- `DEAL_CLOSED`: Credits used when a deal is closed
- `MACHINERY_INSPECTION`: Credits used for machinery inspection
- `LISTING_FEE`: Credits used for listing fees
- `PREMIUM_FEATURE`: Credits used for premium features
- `OTHER`: Other types

---

## Error Responses

All endpoints may return the following error responses:

**401 Unauthorized:**
```json
{
    "message": "Unauthenticated."
}
```

**422 Validation Error:**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "amount": ["The amount must be at least 100."]
    }
}
```

**500 Server Error:**
```json
{
    "success": false,
    "message": "Failed to purchase credits. Please try again."
}
```

---

## Notes

1. **Wallet Auto-Creation**: A wallet is automatically created when a user first accesses wallet-related endpoints.

2. **Credit Calculation**: The default rate is ₹100 = 10 credits. This can be adjusted in the controller.

3. **Payment Gateway**: Currently, the purchase endpoint records the transaction but doesn't process actual payments. Payment gateway integration will be added later.

4. **Transaction IDs**: Automatically generated in the format `TXN-XXXXX` (5-digit number).

5. **Wallet IDs**: Automatically generated in the format `B2B-XXXXX-WP` (5-digit number).

6. **GST**: Default GST percentage is 18%. This can be customized per pack or in the request.

---

## Integration Example

### React Native Example:

```javascript
// Get wallet balance
const getWallet = async () => {
  const response = await fetch('https://api.zupply.com/api/v1/wallet', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });
  const data = await response.json();
  return data;
};

// Purchase credits
const purchaseCredits = async (packId) => {
  const response = await fetch('https://api.zupply.com/api/v1/wallet/purchase', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      credit_pack_id: packId,
      payment_method: 'UPI',
    }),
  });
  const data = await response.json();
  return data;
};

// Get transaction history
const getTransactions = async (type = 'ALL') => {
  const response = await fetch(`https://api.zupply.com/api/v1/wallet/transactions?type=${type}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });
  const data = await response.json();
  return data;
};
```

---

## Future Enhancements

1. Payment Gateway Integration (Razorpay, Stripe, etc.)
2. Payment Status Tracking
3. Refund Processing
4. Credit Expiry Management
5. Promotional Codes/Discounts
6. Bulk Purchase Discounts
7. Subscription Plans


