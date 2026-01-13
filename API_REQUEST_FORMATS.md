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

## 4. Brand Complete Profile API

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

## Notes

1. **Relationship Field**: Use `relationship` field with values "authorized-agent" or "independent-dealer" - it will automatically map to `agent_type` ("AUTHORIZED_AGENT" or "DEALER")

2. **Mill Brand**: You can either:
   - Use existing: `mill_brand_id: 1`
   - Create new: `mill_brand_name: "New Mill"`

3. **Finishes**: If finish is not in the list, add it manually first, then use the returned `id` in `finish_ids` array

4. **Thickness Ranges**: Must have at least one range, and `max` must be >= `min`

5. **Locations**: Required only if `has_warehouse: true`

