# Role-Based Inquiry APIs Guide

## Overview
Different roles use different APIs for posting inquiries because each role has different fields and requirements. This guide explains which API each role should use.

---

## 📋 API Summary by Role

| Role | Create/Post API | Fields | Use Case |
|------|----------------|--------|----------|
| **Brand** | `POST /api/v1/brand/requirement/post` | Packaging/Printing specific | Quick posting (one-step) |
| **Brand** | `POST /api/v1/inquiries` + `POST /api/v1/inquiries/{id}/post` | Material items | Draft workflow (two-step) |
| **Converter** | `POST /api/v1/inquiries` + `POST /api/v1/inquiries/{id}/post` | Material items | Draft workflow (two-step) |
| **Dealer** | `POST /api/v1/dealer/requirement/post` | Material/Machine/Job | Direct posting (one-step) |
| **Machine Dealer** | `POST /api/v1/machine-dealer/machine/post` | Machine specific | Machine listing (one-step) |

---

## 1️⃣ BRAND APIs

### Option A: Brand Requirement Post (Legacy - Quick Post)
**Endpoint:** `POST /api/v1/brand/requirement/post`

**When to Use:**
- Posting packaging/printing requirements
- Want to post immediately (no draft)
- Simple workflow

**Fields:**
```json
{
  "requirement_type": "Packaging | Printing | Packaging + Printing | Corporate Gifting / Stationery",
  "packaging_type": "Rigid Boxes | Folding Cartons | ...",  // Required if Packaging
  "quantity_range": "1000-5000",  // e.g., "1000-5000", "5000-10000"
  "timeline": "Emergency (Urgent) | 3–5 Days | Flexible",
  "special_needs": "Premium finish required",
  "title": "Need 10,000 Rigid Boxes",
  "description": "Looking for high-quality rigid boxes",
  "urgency": "normal | urgent",
  "location": "Mumbai, Maharashtra",
  "latitude": 19.0760,
  "longitude": 72.8777,
  "design_attachments": ["path/to/design1.jpg"]
}
```

**Example:**
```http
POST /api/v1/brand/requirement/post
Authorization: Bearer {token}
Content-Type: application/json

{
  "requirement_type": "Packaging + Printing",
  "packaging_type": "Rigid Boxes",
  "quantity_range": "1000-5000",
  "timeline": "3–5 Days",
  "title": "Need 10,000 Rigid Boxes",
  "urgency": "normal",
  "location": "Mumbai, Maharashtra"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Requirement posted successfully",
  "data": {
    "inquiry_id": 25,
    "session_id": 10,
    "matched_converters_count": 8
  }
}
```

---

### Option B: Unified Inquiry API (New - Draft Workflow)
**Endpoints:** 
1. `POST /api/v1/inquiries` (create draft)
2. `POST /api/v1/inquiries/{id}/post` (post it)

**When to Use:**
- Posting material inquiries (not just packaging/printing)
- Need to save as draft first
- Want to edit before posting

**Fields (Step 1 - Create):**
```json
{
  "title": "Need Duplex Board 350 GSM",
  "description": "Looking for high-quality duplex board",
  "items": [
    {
      "material_id": 5,  // Optional
      "material_category": "Duplex Board",  // Required if no material_id
      "thickness_gsm": 350,
      "thickness_mm": null,
      "thickness_unit": "gsm",  // or "mm"
      "quantity": 2000,
      "quantity_unit": "sheets",
      "finish_coating": "glossy",
      "additional_specs": {}
    }
  ],
  "urgency": "normal | urgent",
  "location": "Mumbai, Maharashtra",
  "latitude": 19.0760,
  "longitude": 72.8777,
  "timeline": "3-5 Days",
  "deadline": "2026-01-25T10:00:00Z"
}
```

**Example:**
```http
# Step 1: Create draft
POST /api/v1/inquiries
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Need Duplex Board 350 GSM",
  "items": [
    {
      "material_category": "Duplex Board",
      "thickness_gsm": 350,
      "thickness_unit": "gsm",
      "quantity": 2000,
      "quantity_unit": "sheets"
    }
  ],
  "urgency": "normal"
}

# Response: { "data": { "id": 25, ... } }

# Step 2: Post it
POST /api/v1/inquiries/25/post
Authorization: Bearer {token}
```

---

## 2️⃣ CONVERTER APIs

### Unified Inquiry API (Draft Workflow)
**Endpoints:** 
1. `POST /api/v1/inquiries` (create draft)
2. `POST /api/v1/inquiries/{id}/post` (post it)

**When to Use:**
- Posting material/machine/job inquiries
- Need draft workflow

**Fields (Same as Brand Option B):**
```json
{
  "title": "Need Duplex Board 350 GSM",
  "description": "Looking for high-quality duplex board",
  "items": [
    {
      "material_category": "Duplex Board",
      "thickness_gsm": 350,
      "thickness_unit": "gsm",
      "quantity": 2000,
      "quantity_unit": "sheets"
    }
  ],
  "urgency": "normal | urgent",
  "location": "Mumbai, Maharashtra",
  "latitude": 19.0760,
  "longitude": 72.8777
}
```

**Example:**
```http
# Step 1: Create draft
POST /api/v1/inquiries
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Need Duplex Board 350 GSM",
  "items": [
    {
      "material_category": "Duplex Board",
      "thickness_gsm": 350,
      "thickness_unit": "gsm",
      "quantity": 2000,
      "quantity_unit": "sheets"
    }
  ],
  "urgency": "normal"
}

# Step 2: Post it
POST /api/v1/inquiries/{id}/post
```

---

## 3️⃣ DEALER APIs

### Dealer Requirement Post
**Endpoint:** `POST /api/v1/dealer/requirement/post`

**When to Use:**
- Posting material/machine/job requirements
- Direct posting (no draft)

**Fields:**
```json
{
  "inquiry_type": "material | machine | job",
  "intent": "buy | sell",
  "title": "Selling Duplex Board 350 GSM",
  "description": "High-quality duplex board available",
  "urgency": "normal | urgent",
  
  // Material inquiry fields
  "material_ids": [1, 2, 3],  // Required if inquiry_type = material
  "thickness": 350,
  "thickness_unit": "GSM | MM | OUNCE | BF | MICRON",
  "size": "28x40",
  "quantity": 2000,
  "quantity_unit": "kg | tons | sheets",
  "price": 50,
  "price_unit": "per_sheet | per_kg",
  "price_negotiable": true,
  "approx_price_note": "Negotiable for bulk orders",
  
  // Machine inquiry fields
  "machine_ids": [5, 6],  // Required if inquiry_type = machine
  "machine_condition": "Brand New | Excellent | Working Condition | Needs Repair",
  
  // Job inquiry fields
  "job_type": "Cutting | Printing",  // Required if inquiry_type = job
  "timeline_days": 5,
  
  // Location
  "location": "Mumbai, Maharashtra",
  "latitude": 19.0760,
  "longitude": 72.8777,
  
  // Additional
  "specs": {},
  "attachment_paths": ["path/to/file.pdf"],
  "deadline": "2026-01-25T10:00:00Z"
}
```

**Example (Material - Sell):**
```http
POST /api/v1/dealer/requirement/post
Authorization: Bearer {token}
Content-Type: application/json

{
  "inquiry_type": "material",
  "intent": "sell",
  "title": "Selling Duplex Board 350 GSM",
  "material_ids": [5],
  "thickness": 350,
  "thickness_unit": "GSM",
  "quantity": 2000,
  "quantity_unit": "sheets",
  "price": 50,
  "price_unit": "per_sheet",
  "price_negotiable": true,
  "urgency": "normal",
  "location": "Mumbai, Maharashtra"
}
```

**Example (Machine - Buy):**
```http
POST /api/v1/dealer/requirement/post
Authorization: Bearer {token}
Content-Type: application/json

{
  "inquiry_type": "machine",
  "intent": "buy",
  "title": "Looking for Printing Machine",
  "machine_ids": [10],
  "machine_condition": "Working Condition",
  "urgency": "urgent",
  "location": "Mumbai, Maharashtra"
}
```

**Example (Job):**
```http
POST /api/v1/dealer/requirement/post
Authorization: Bearer {token}
Content-Type: application/json

{
  "inquiry_type": "job",
  "intent": "buy",
  "title": "Need Cutting Service",
  "job_type": "Cutting",
  "timeline_days": 5,
  "urgency": "normal"
}
```

---

## 4️⃣ MACHINE DEALER APIs

### Machine Dealer Post Machine
**Endpoint:** `POST /api/v1/machine-dealer/machine/post`

**When to Use:**
- Posting machines for sale/buy
- Machine-specific fields

**Fields:**
```json
{
  "machine_id": 10,  // Machine type ID
  "intent": "buy | sell",
  "title": "Heidelberg Printing Press for Sale",
  "description": "Excellent condition, well maintained",
  "condition": "Brand New | Excellent | Working Condition | Needs Repair",
  "urgency": "normal | urgent",
  "location": "Mumbai, Maharashtra",
  "latitude": 19.0760,
  "longitude": 72.8777,
  "specs": {
    "year": 2020,
    "hours_used": 5000
  },
  "attachment_paths": ["path/to/machine-photo.jpg"]
}
```

**Example:**
```http
POST /api/v1/machine-dealer/machine/post
Authorization: Bearer {token}
Content-Type: application/json

{
  "machine_id": 10,
  "intent": "sell",
  "title": "Heidelberg Printing Press for Sale",
  "condition": "Excellent",
  "urgency": "normal",
  "location": "Mumbai, Maharashtra"
}
```

---

## 📊 Comparison Table

| Feature | Brand Requirement Post | Unified Inquiry API | Dealer Requirement Post | Machine Dealer Post |
|---------|----------------------|-------------------|----------------------|-------------------|
| **Role** | Brand only | Brand, Converter | Dealer | Machine Dealer |
| **Inquiry Types** | Job (Packaging/Printing) | Material (with items) | Material, Machine, Job | Machine |
| **Workflow** | One-step | Two-step (draft → post) | One-step | One-step |
| **Draft Support** | ❌ No | ✅ Yes | ❌ No | ❌ No |
| **Fields** | Packaging-specific | Material items array | Material/Machine/Job | Machine-specific |
| **Matching** | Converters | Dealers | Dealers/Converters | Machine Dealers |

---

## 🎯 Quick Decision Guide

### For Brands:
- **Quick posting (packaging/printing):** Use `POST /api/v1/brand/requirement/post`
- **Material inquiries with drafts:** Use `POST /api/v1/inquiries` + `POST /api/v1/inquiries/{id}/post`

### For Converters:
- **All inquiries:** Use `POST /api/v1/inquiries` + `POST /api/v1/inquiries/{id}/post`

### For Dealers:
- **All inquiries:** Use `POST /api/v1/dealer/requirement/post`

### For Machine Dealers:
- **Machine listings:** Use `POST /api/v1/machine-dealer/machine/post`

---

## 📝 Field Differences Summary

### Brand Requirement Post Fields:
- `requirement_type` (Packaging, Printing, etc.)
- `packaging_type` (Rigid Boxes, Folding Cartons, etc.)
- `quantity_range` (e.g., "1000-5000")
- `timeline` (Emergency, 3-5 Days, Flexible)
- `design_attachments`

### Unified Inquiry API Fields:
- `items[]` (array of material items)
- Each item has: `material_category`, `thickness_gsm/mm`, `quantity`, etc.
- More detailed material specifications

### Dealer Requirement Post Fields:
- `inquiry_type` (material, machine, job)
- `intent` (buy, sell)
- `material_ids[]` or `machine_ids[]` or `job_type`
- `price`, `price_unit`, `price_negotiable`
- `machine_condition` (for machines)

### Machine Dealer Post Fields:
- `machine_id` (specific machine type)
- `condition` (Brand New, Excellent, etc.)
- `specs` (year, hours_used, etc.)

---

## 🔗 Code References

- **Brand Requirement Post:** `app/Services/BrandService.php::postRequirement()`
- **Unified Inquiry Create:** `app/Http/Controllers/api/InquiryController.php::store()`
- **Unified Inquiry Post:** `app/Http/Controllers/api/InquiryController.php::post()`
- **Dealer Requirement Post:** `app/Services/DealerService.php::postRequirement()`
- **Machine Dealer Post:** `app/Services/MachineDealerService.php::postMachine()`

---

## 📚 Related Documentation

- [Why Two Post APIs Exist](./WHY_TWO_POST_APIS.md) - Explanation of brand vs unified APIs
- [Inquiry ID Flow](./INQUIRY_ID_FLOW.md) - Where the inquiry ID comes from
- [Session Creation Flow](./SESSION_CREATION_FLOW.md) - When sessions are created

