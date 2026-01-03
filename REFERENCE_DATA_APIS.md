# REFERENCE DATA APIs
## GET APIs for Dealer Profile Completion

These APIs provide reference data needed when completing dealer profiles.

**Base URL:** `{{BASE_URL}}/api/v1`

**Authentication:** Not Required (Public endpoints)

---

## 📋 AVAILABLE ENDPOINTS

### 1. Get All Machines
**Endpoint:** `GET /machines`

**Query Parameters:**
- `type` (optional): Filter by machine type (e.g., `printing`, `die_cutting`, `folding_gluing`)
- `category` (optional): Alias for `type`

**Response:**
```json
{
  "success": true,
  "message": "Machines retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Automatic Folder Gluer",
      "type": "folding_gluing",
      "description": "Automatic folder gluer machine"
    },
    {
      "id": 2,
      "name": "4-Color Offset Machine",
      "type": "printing",
      "description": "Four color offset printing machine"
    }
  ]
}
```

**Example:**
```http
GET /api/v1/machines?type=printing
```

---

### 2. Get Material Finishes
**Endpoint:** `GET /material-finishes`

**Query Parameters:**
- `material_id` (optional): Filter by material ID
- `type` (optional): Filter by finish type (e.g., `finish`, `coating`, `grade`, `variant`)

**Response:**
```json
{
  "success": true,
  "message": "Material finishes retrieved successfully",
  "data": [
    {
      "id": 1,
      "material_id": 1,
      "name": "Uncoated",
      "type": "finish"
    },
    {
      "id": 2,
      "material_id": 1,
      "name": "Coated",
      "type": "finish"
    },
    {
      "id": 3,
      "material_id": 1,
      "name": "Gloss",
      "type": "coating"
    }
  ]
}
```

**Example:**
```http
GET /api/v1/material-finishes?material_id=1
GET /api/v1/material-finishes?material_id=1&type=coating
```

---

### 3. Get Material Mills/Brands
**Endpoint:** `GET /material-mills`

**Query Parameters:**
- `material_id` (required): Material ID to get mills for

**Response:**
```json
{
  "success": true,
  "message": "Material mills retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "ITC",
      "material_id": 1
    },
    {
      "id": 2,
      "name": "JK Paper",
      "material_id": 1
    }
  ]
}
```

**Example:**
```http
GET /api/v1/material-mills?material_id=1
```

**Note:** Returns brands/mills that manufacture the specified material.

---

### 4. Get Material Thickness Types
**Endpoint:** `GET /material-thickness-types`

**Query Parameters:**
- `material_id` (required): Material ID to get thickness units for

**Response:**
```json
{
  "success": true,
  "message": "Material thickness types retrieved successfully",
  "data": [
    {
      "id": 1,
      "unit": "GSM",
      "is_primary": true,
      "material_id": 1
    },
    {
      "id": 2,
      "unit": "MM",
      "is_primary": false,
      "material_id": 1
    }
  ]
}
```

**Example:**
```http
GET /api/v1/material-thickness-types?material_id=1
```

**Note:** Returns valid thickness units (GSM, MM, OUNCE, BF, MICRON) for the specified material. `is_primary` indicates the primary unit.

---

### 5. Get All Brands/Mills
**Endpoint:** `GET /brands`

**Response:**
```json
{
  "success": true,
  "message": "Brands (mills) retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "ITC"
    },
    {
      "id": 2,
      "name": "JK Paper"
    },
    {
      "id": 3,
      "name": "Century Pulp & Paper"
    }
  ]
}
```

**Example:**
```http
GET /api/v1/brands
```

**Note:** Returns all mill brands (paper mills), not user brand profiles.

---

### 6. Get Complete Material Details
**Endpoint:** `GET /materials/{id}/details`

**Response:**
```json
{
  "success": true,
  "message": "Material details retrieved successfully",
  "data": {
    "material": {
      "id": 1,
      "name": "Duplex Board",
      "category": "PAPERBOARDS"
    },
    "mills": [
      {
        "id": 1,
        "name": "ITC"
      },
      {
        "id": 2,
        "name": "JK Paper"
      }
    ],
    "finishes": [
      {
        "id": 1,
        "material_id": 1,
        "name": "Uncoated",
        "type": "finish"
      },
      {
        "id": 2,
        "material_id": 1,
        "name": "Coated",
        "type": "finish"
      }
    ],
    "thickness_types": [
      {
        "id": 1,
        "unit": "GSM",
        "is_primary": true,
        "material_id": 1
      },
      {
        "id": 2,
        "unit": "MM",
        "is_primary": false,
        "material_id": 1
      }
    ]
  }
}
```

**Example:**
```http
GET /api/v1/materials/1/details
```

**Note:** Returns all related data for a material in one call (mills, finishes, thickness types).

---

## 🔄 TYPICAL FLOW FOR DEALER PROFILE COMPLETION

### Step 1: Get All Materials
```http
GET /api/v1/materials
```
User selects materials they deal in.

### Step 2: For Each Selected Material, Get Details
```http
GET /api/v1/materials/1/details
```
This returns:
- Available mills for this material
- Available finishes for this material
- Valid thickness units for this material

### Step 3: Get All Machines
```http
GET /api/v1/machines
```
User selects machines they have available.

### Step 4: Submit Profile
```http
POST /api/v1/dealer/profile/complete
{
  "materials": [
    {
      "material_id": 1,
      "brand_id": 1,  // From mills list
      "agent_type": "AUTHORIZED_AGENT",
      "finish_ids": [1, 2],  // From finishes list
      "thickness_ranges": [
        {
          "unit": "GSM",  // From thickness_types
          "min": 200,
          "max": 400
        }
      ]
    }
  ],
  "machines_available": [1, 2],  // From machines list
  ...
}
```

---

## 📝 NOTES

1. All endpoints are **public** (no authentication required)
2. All endpoints support filtering via query parameters
3. Material mills endpoint requires `material_id` parameter
4. Material thickness types endpoint requires `material_id` parameter
5. The `/materials/{id}/details` endpoint is a convenience endpoint that combines mills, finishes, and thickness types in one call
6. All responses follow the standard API response format with `success`, `message`, and `data` fields

---

## ✅ COMPLETE API LIST

1. ✅ `GET /api/v1/materials` - Get all materials (existing)
2. ✅ `GET /api/v1/machines` - Get all machines
3. ✅ `GET /api/v1/material-finishes` - Get material finishes
4. ✅ `GET /api/v1/material-mills` - Get mills for a material
5. ✅ `GET /api/v1/material-thickness-types` - Get thickness units for a material
6. ✅ `GET /api/v1/brands` - Get all mill brands
7. ✅ `GET /api/v1/materials/{id}/details` - Get complete material details

---

**All APIs are ready to use!** 🚀

