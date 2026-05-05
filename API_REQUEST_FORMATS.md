# API Request Formats - Complete Guide

## 1. Dealer Complete Profile API

### Endpoint
`POST /api/v1/dealer/profile/complete`

### Headers
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

### Request Body (Full Example with Mill Brand)

```json
{
  "materials": [
    {
      "material_id": 45,
      "mill_brand_id": 1,
      "mill_brand_name": "ITC Paperboards",
      "prefer_not_to_disclose": false,
      "relationship": "authorized-agent",
      "agent_type": "AUTHORIZED_AGENT",
      "finish_ids": [1, 5, 12],
      "thickness_ranges": [
        {
          "unit": "GSM",
          "min": 200,
          "max": 400
        },
        {
          "unit": "MM",
          "min": 0.5,
          "max": 2.0
        }
      ]
    },
    {
      "material_id": 46,
      "mill_brand_id": null,
      "relationship": "independent-dealer",
      "agent_type": "DEALER",
      "finish_ids": [2, 8],
      "thickness_ranges": [
        {
          "unit": "GSM",
          "min": 250,
          "max": 350
        }
      ]
    }
  ],
  "machines_available": [1, 2, 3],
  "capacity_daily": 1000.50,
  "capacity_monthly": 30000.00,
  "capacity_unit": "kg",
  "has_warehouse": true,
  "bulk_orders_note": null,
  "locations": [
    {
      "type": "factory",
      "address": "123 Industrial Street, Sector 5",
      "latitude": 28.6139,
      "longitude": 77.2090,
      "city": "Delhi",
      "state": "Delhi"
    },
    {
      "type": "warehouse",
      "address": "456 Warehouse Road, Sector 18",
      "latitude": 28.7041,
      "longitude": 77.1025,
      "city": "Noida",
      "state": "Uttar Pradesh"
    }
  ]
}
```

### Request Body (Without Warehouse - Bulk Orders Only)

```json
{
  "materials": [
    {
      "material_id": 45,
      "mill_brand_id": null,
      "finish_ids": [],
      "thickness_ranges": [
        {
          "unit": "GSM",
          "min": 200,
          "max": 400
        }
      ]
    }
  ],
  "machines_available": [1, 2],
  "capacity_daily": 5000.00,
  "capacity_monthly": 150000.00,
  "capacity_unit": "kg",
  "has_warehouse": false,
  "bulk_orders_note": "We handle bulk orders only. Minimum order quantity: 10 tons",
  "locations": []
}
```

### Field Descriptions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `materials` | Array | ✅ Yes | Array of materials the dealer deals in |
| `materials[].material_id` | Integer | ✅ Yes | Material ID from materials list |
| `materials[].mill_brand_id` | Integer | ❌ No | Mill/Brand ID (use existing brand) |
| `materials[].mill_brand_name` | String | ❌ No | Mill/Brand name (if creating new) |
| `materials[].prefer_not_to_disclose` | Boolean | ❌ No | If true, don't show brand |
| `materials[].relationship` | String | ❌ No | "authorized-agent" or "independent-dealer" |
| `materials[].agent_type` | String | ❌ No | "AUTHORIZED_AGENT" or "DEALER" (alternative to relationship) |
| `materials[].finish_ids` | Array | ❌ No | Array of finish/grade IDs |
| `materials[].thickness_ranges` | Array | ✅ Yes | Thickness ranges per unit |
| `materials[].thickness_ranges[].unit` | String | ✅ Yes | "GSM", "MM", "OUNCE", "BF", or "MICRON" |
| `materials[].thickness_ranges[].min` | Number | ✅ Yes | Minimum thickness value |
| `materials[].thickness_ranges[].max` | Number | ✅ Yes | Maximum thickness value (must be >= min) |
| `machines_available` | Array | ❌ No | Array of machine IDs |
| `capacity_daily` | Number | ✅ Yes | Daily capacity |
| `capacity_monthly` | Number | ✅ Yes | Monthly capacity |
| `capacity_unit` | String | ✅ Yes | Unit for capacity (e.g., "kg", "tons") |
| `has_warehouse` | Boolean | ✅ Yes | Whether dealer has warehouse |
| `bulk_orders_note` | String | ❌ No | Note if only bulk orders (required if has_warehouse = false) |
| `locations` | Array | Conditional | Required if has_warehouse = true |
| `locations[].type` | String | ✅ Yes | "factory" or "warehouse" |
| `locations[].address` | String | ❌ No | Address |
| `locations[].latitude` | Number | ✅ Yes | Latitude coordinate |
| `locations[].longitude` | Number | ✅ Yes | Longitude coordinate |
| `locations[].city` | String | ❌ No | City name |
| `locations[].state` | String | ❌ No | State name |

### Response (Success - 201 Created)

```json
{
  "success": true,
  "message": "Dealer profile completed successfully!",
  "data": {
    "id": 1,
    "user_id": 1,
    "status": "ACTIVE",
    "profile_complete": true,
    "capacity_daily": "1000.50",
    "capacity_monthly": "30000.00",
    "capacity_unit": "kg",
    "has_warehouse": true,
    "created_at": "2026-01-03T10:00:00.000000Z",
    "updated_at": "2026-01-03T10:00:00.000000Z"
  }
}
```

---

## 2. Add Mill/Brand Manually API

### Endpoint
`POST /api/v1/dealer/mill/add`

### Headers
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

### Request Body (Create New Mill Brand)

```json
{
  "mill_brand_name": "ABC Paper Mills",
  "prefer_not_to_disclose": false,
  "relationship": "authorized-agent",
  "material_id": 45
}
```

### Request Body (Use Existing Mill Brand)

```json
{
  "mill_brand_id": 1,
  "prefer_not_to_disclose": false,
  "relationship": "independent-dealer",
  "material_id": 45
}
```

### Field Descriptions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `mill_brand_id` | Integer | ❌ No | Use existing brand ID (if provided, mill_brand_name not needed) |
| `mill_brand_name` | String | Conditional | Required if mill_brand_id not provided - name of new mill brand |
| `prefer_not_to_disclose` | Boolean | ❌ No | If true, don't show brand name |
| `relationship` | String | ❌ No | "authorized-agent" or "independent-dealer" |
| `material_id` | Integer | ❌ No | Associate this mill with a material |

### Response (Success - 201 Created)

```json
{
  "success": true,
  "message": "Mill brand added successfully",
  "data": {
    "mill_brand_id": 1,
    "mill_brand_name": "ABC Paper Mills",
    "prefer_not_to_disclose": false,
    "relationship": "authorized-agent",
    "agent_type": "AUTHORIZED_AGENT"
  }
}
```

### Response (Error - 422 Validation Error)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "mill_brand_name": [
      "The mill brand name field is required when mill brand id is not present."
    ]
  }
}
```

---

## 3. Add Finish Manually API

### Endpoint
`POST /api/v1/dealer/finish/add`

### Headers
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

### Request Body

```json
{
  "name": "Custom Gloss Finish",
  "material_id": 45,
  "type": "finish"
}
```

### Field Descriptions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | String | ✅ Yes | Name of the finish |
| `material_id` | Integer | ❌ No | Associate with specific material |
| `type` | String | ❌ No | Type: "finish", "coating", "grade", "variant", "surface", "treatment" (default: "finish") |

### Response (Success - 201 Created)

```json
{
  "success": true,
  "message": "Finish added successfully",
  "data": {
    "id": 150,
    "name": "Custom Gloss Finish",
    "material_id": 45,
    "type": "finish"
  }
}
```

### Response (Error - 422 Validation Error)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": [
      "The name field is required."
    ]
  }
}
```

---

## 4. Dealer Post Requirement API

### Endpoint
`POST /api/v1/dealer/requirement/post`

### Headers
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

### Request Body (Material - Buy)

```json
{
  "inquiry_type": "material",
  "intent": "buy",
  "title": "Need Duplex Board 350 GSM",
  "description": "Looking for high quality duplex board",
  "material_ids": [1, 2],
  "thickness": 350,
  "thickness_unit": "GSM",
  "size": "28x40",
  "quantity": 2000,
  "quantity_unit": "sheets",
  "price": 50,
  "price_unit": "per_sheet",
  "price_negotiable": true,
  "urgency": "urgent",
  "location": "Mumbai",
  "latitude": 19.1136,
  "longitude": 72.8697,
  "deadline": "2026-01-15"
}
```

### Request Body (Material - Sell)

```json
{
  "inquiry_type": "material",
  "intent": "sell",
  "title": "Selling Duplex Board 350 GSM",
  "description": "Premium quality duplex board available",
  "material_ids": [1],
  "thickness": 350,
  "thickness_unit": "GSM",
  "size": "28x40",
  "quantity": 5000,
  "quantity_unit": "sheets",
  "price": 45,
  "price_unit": "per_sheet",
  "price_negotiable": false,
  "urgency": "normal",
  "location": "Delhi",
  "latitude": 28.6139,
  "longitude": 77.2090
}
```

### Request Body (Machine - Buy)

```json
{
  "inquiry_type": "machine",
  "intent": "buy",
  "title": "Need Automatic Folder Gluer",
  "description": "Looking for good condition machine",
  "machine_ids": [1, 2],
  "machine_condition": "Working Condition",
  "urgency": "normal",
  "location": "Mumbai",
  "latitude": 19.1136,
  "longitude": 72.8697
}
```

### Request Body (Machine - Sell)

```json
{
  "inquiry_type": "machine",
  "intent": "sell",
  "title": "Selling Printing Machine",
  "description": "Excellent condition machine available",
  "machine_ids": [1],
  "machine_condition": "Excellent",
  "urgency": "normal",
  "location": "Delhi",
  "latitude": 28.6139,
  "longitude": 77.2090
}
```

### Request Body (Job - Outsourcing)

```json
{
  "inquiry_type": "job",
  "intent": "buy",
  "title": "Need 10,000 Rigid Boxes",
  "description": "Looking for quality manufacturer",
  "job_type": "Rigid Boxes",
  "quantity": 10000,
  "quantity_unit": "pieces",
  "timeline_days": 5,
  "urgency": "normal",
  "location": "Mumbai",
  "latitude": 19.1136,
  "longitude": 72.8697
}
```

### Field Descriptions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `inquiry_type` | String | ✅ Yes | "material", "machine", or "job" |
| `intent` | String | ✅ Yes | "buy" or "sell" |
| `title` | String | ✅ Yes | Title of the requirement |
| `description` | String | ❌ No | Detailed description |
| `urgency` | String | ✅ Yes | "normal" or "urgent" |
| `material_ids` | Array | Conditional | Required if inquiry_type = "material" |
| `machine_ids` | Array | Conditional | Required if inquiry_type = "machine" |
| `thickness` | Number | ❌ No | Thickness value |
| `thickness_unit` | String | ❌ No | "GSM", "MM", "OUNCE", "BF", or "MICRON" |
| `size` | String | ❌ No | Size (e.g., "28x40") |
| `quantity` | Number | ✅ Yes | Quantity required/available |
| `quantity_unit` | String | ✅ Yes | Unit (kg, tons, sheets, pieces, etc.) |
| `price` | Number | ❌ No | Price |
| `price_unit` | String | ❌ No | Price unit (per_sheet, per_kg, etc.) |
| `price_negotiable` | Boolean | ❌ No | Whether price is negotiable (default: true) |
| `approx_price_note` | String | ❌ No | Approximate price note |
| `machine_condition` | String | ❌ No | "Brand New", "Excellent", "Working Condition", or "Needs Repair" |
| `job_type` | String | Conditional | Required if inquiry_type = "job" |
| `timeline_days` | Integer | ❌ No | Timeline in days |
| `location` | String | ❌ No | Location name |
| `latitude` | Number | ❌ No | Latitude coordinate |
| `longitude` | Number | ❌ No | Longitude coordinate |
| `specs` | Array | ❌ No | Additional specifications |
| `attachment_paths` | Array | ❌ No | Array of attachment file paths |
| `deadline` | Date | ❌ No | Deadline date |

### Response (Success - 201 Created)

```json
{
  "success": true,
  "message": "Requirement posted successfully",
  "data": {
    "id": 1,
    "poster_id": 1,
    "poster_type": "dealer",
    "inquiry_type": "material",
    "intent": "buy",
    "title": "Need Duplex Board 350 GSM",
    "status": "MATCHING",
    "urgency": "urgent",
    "quantity": "2000.00",
    "quantity_unit": "sheets",
    "materials": [
      {
        "id": 1,
        "name": "Duplex Board"
      }
    ],
    "created_at": "2026-01-03T10:00:00.000000Z"
  }
}
```

---

## 5. Dealer Get Requirements API

### Endpoint
`GET /api/v1/dealer/requirements`

### Headers
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `inquiry_type` | String | ❌ No | Filter by type: "material", "machine", or "job" |
| `intent` | String | ❌ No | Filter by intent: "buy" or "sell" |
| `status` | String | ❌ No | Filter by status: "MATCHING", "SESSION_LOCKED", "COMPLETED", "CANCELLED" |
| `urgency` | String | ❌ No | Filter by urgency: "normal" or "urgent" |
| `material_id` | Integer | ❌ No | Filter by material ID |
| `machine_id` | Integer | ❌ No | Filter by machine ID |
| `sort_by` | String | ❌ No | Sort field: "created_at" (default) or "updated_at" |
| `sort_order` | String | ❌ No | Sort order: "asc" or "desc" (default: "desc") |
| `per_page` | Integer | ❌ No | Items per page (default: 15) |
| `page` | Integer | ❌ No | Page number (default: 1) |

### Example Request

```
GET /api/v1/dealer/requirements?inquiry_type=material&intent=buy&urgency=urgent&per_page=20&page=1
```

### Response (Success - 200 OK)

```json
{
  "success": true,
  "message": "Requirements retrieved successfully",
  "data": [
    {
      "id": 1,
      "inquiry_type": "material",
      "intent": "buy",
      "title": "Need Duplex Board 350 GSM",
      "description": "Looking for high quality duplex board",
      "status": "MATCHING",
      "urgency": "urgent",
      "quantity": "2000.00",
      "quantity_unit": "sheets",
      "size": "28x40",
      "price": "50.00",
      "price_unit": "per_sheet",
      "price_negotiable": true,
      "thickness": "350.000",
      "thickness_unit": "GSM",
      "machine_condition": null,
      "job_type": null,
      "timeline_days": null,
      "location": "Mumbai",
      "latitude": "19.11360000",
      "longitude": "72.86970000",
      "materials": [
        {
          "id": 1,
          "name": "Duplex Board"
        }
      ],
      "machines": [],
      "responses_count": 3,
      "created_at": "2026-01-03T10:00:00.000000Z",
      "updated_at": "2026-01-03T10:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total": 25,
    "per_page": 15,
    "last_page": 2,
    "from": 1,
    "to": 15
  }
}
```

---

## 6. Brand Complete Profile API

### Endpoint
`POST /api/v1/brand/profile/complete`

### Headers
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

### Request Body

```json
{
  "company_name": "ABC Paper Mills Ltd",
  "brand_name": "Premium Papers",
  "contact_person_name": "John Doe",
  "mobile": "9876543210",
  "email": "john@abcmills.com",
  "gst": "29ABCDE1234F1Z5",
  "city": "Mumbai",
  "location": "Andheri East",
  "latitude": 19.1136,
  "longitude": 72.8697,
  "brand_type_ids": [1, 2, 3]
}
```

### Field Descriptions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `company_name` | String | ✅ Yes | Company name |
| `brand_name` | String | ❌ No | Brand name |
| `contact_person_name` | String | ✅ Yes | Contact person name |
| `mobile` | String | ❌ No | Mobile number |
| `email` | String | ❌ No | Email address |
| `gst` | String | ❌ No | GST number |
| `city` | String | ❌ No | City |
| `location` | String | ❌ No | Location/address |
| `latitude` | Number | ❌ No | Latitude coordinate |
| `longitude` | Number | ❌ No | Longitude coordinate |
| `brand_type_ids` | Array | ❌ No | Array of brand type IDs |

### Response (Success - 201 Created)

```json
{
  "success": true,
  "message": "Brand profile completed successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "company_name": "ABC Paper Mills Ltd",
    "brand_name": "Premium Papers",
    "status": "ACTIVE",
    "profile_complete": true,
    "brand_types": [
      {
        "id": 1,
        "name": "Paper Manufacturer"
      }
    ]
  }
}
```

---

## 5. Example Flow for React Native

### Step 1: Get Materials List
```javascript
GET /api/v1/materials
```

### Step 2: Get Mill Brands for Material
```javascript
GET /api/v1/material-mills?material_id=45
```

### Step 3: If Mill Not Found, Add Manually
```javascript
POST /api/v1/dealer/mill/add
{
  "mill_brand_name": "New Paper Mill",
  "relationship": "authorized-agent",
  "material_id": 45
}
```

### Step 4: Get Finishes for Material
```javascript
GET /api/v1/material-finishes?material_id=45
```

### Step 5: If Finish Not Found, Add Manually
```javascript
POST /api/v1/dealer/finish/add
{
  "name": "Custom Finish",
  "material_id": 45,
  "type": "finish"
}
```

### Step 6: Complete Profile
```javascript
POST /api/v1/dealer/profile/complete
{
  "materials": [
    {
      "material_id": 45,
      "mill_brand_id": 1, // From step 3
      "relationship": "authorized-agent",
      "finish_ids": [1, 2, 150], // 150 from step 5
      "thickness_ranges": [
        {
          "unit": "GSM",
          "min": 200,
          "max": 400
        }
      ]
    }
  ],
  "capacity_daily": 1000,
  "capacity_monthly": 30000,
  "capacity_unit": "kg",
  "has_warehouse": true,
  "locations": [...]
}
```

---

## 7. Wallet - Razorpay Create Order

### Endpoint
```
POST /api/v1/wallet/payments/razorpay/order
```

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Request Body
```json
{
    "credit_pack_id": 2
}
```

### Field Descriptions
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `credit_pack_id` | integer | yes | Must exist in `credit_packs.id`. Server reads `total_price` from the pack; request-supplied amounts are ignored. |

### Response (Success - 201 Created)
```json
{
    "success": true,
    "message": "Order created",
    "data": {
        "key_id": "rzp_test_xxxx",
        "razorpay_order_id": "order_LxYzABC",
        "amount": 11800,
        "currency": "INR",
        "receipt": "WPO-42-9f0d5d2c",
        "pack": {"id": 2, "name": "Starter Pack", "credits": 100, "total_price": 118.00}
    }
}
```

---

## 8. Wallet - Razorpay Verify Payment

### Endpoint
```
POST /api/v1/wallet/payments/razorpay/verify
```

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Request Body
```json
{
    "razorpay_order_id": "order_LxYzABC",
    "razorpay_payment_id": "pay_LxYzABC",
    "razorpay_signature": "0a1b2c... (HMAC-SHA256 of order_id|payment_id with key secret)"
}
```

### Field Descriptions
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `razorpay_order_id` | string (≤64) | yes | Order id returned by `/payments/razorpay/order`. |
| `razorpay_payment_id` | string (≤64) | yes | Payment id from the Razorpay checkout success callback. |
| `razorpay_signature` | string (≤256) | yes | HMAC-SHA256 from the Razorpay checkout success callback. |

### Response (Success - 200 OK)
```json
{
    "success": true,
    "message": "Payment verified",
    "data": {
        "transaction_id": "TXN-00001",
        "credits_added": 100,
        "new_balance": 100.00,
        "amount_paid": 118.00
    }
}
```

### Notes
- **Idempotent.** A duplicate `/verify` call for an already-paid order returns `200`
  with the same payload; credits are not added a second time.
- Failure modes: `403` (signature invalid), `404` (order not for this user),
  `409` (order in non-fulfillable state), `422` (`payments.fetch` mismatch).

---

## Notes

1. **Relationship Field**: Use `relationship` field with values "authorized-agent" or "independent-dealer" - it will automatically map to `agent_type` ("AUTHORIZED_AGENT" or "DEALER")

2. **Mill Brand**: You can either:
   - Use existing: `mill_brand_id: 1`
   - Create new: `mill_brand_name: "New Mill"`

3. **Finishes**: If finish is not in the list, add it manually first, then use the returned `id` in `finish_ids` array

4. **Thickness Ranges**: Must have at least one range, and `max` must be >= `min`

5. **Locations**: Required only if `has_warehouse: true`


