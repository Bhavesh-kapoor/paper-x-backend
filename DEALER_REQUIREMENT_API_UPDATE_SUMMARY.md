# Dealer Requirement Post API - Update Summary

## 📋 Overview

The `/api/v1/dealer/requirement/post` API has been updated to accept only the specific fields required for material buy requirements as specified in the requirements document.

## ✅ Changes Made

### 1. Database Migrations

#### Migration: `add_dealer_requirement_fields_to_inquiries_table`
**New Fields Added:**
- `size_unit` (enum: 'inches', 'cm', 'mm') - Unit for size dimensions
- `visibility` (enum: 'dealers', 'converters', 'all') - Who can see the requirement
- `location_source` (enum: 'saved', 'manual') - Source of location data
- `location_id` (foreign key to `dealer_locations`) - Reference to saved location

**Note:** The `visibility` enum was updated to remove 'manufacturers' option. Existing records with 'manufacturers' are automatically updated to 'all'.

#### Migration: `create_inquiry_finishes_table`
**New Pivot Table:**
- Links inquiries to material finishes (grade/finish/variant)
- Fields: `inquiry_id`, `finish_id`
- Unique constraint prevents duplicate entries

### 2. Request Validation (`PostRequirementRequest`)

**Updated Fields:**
- ✅ `inquiry_type` - Now only accepts `'material'` (was: material, machine, job)
- ✅ `intent` - Now only accepts `'buy'` (was: buy, sell)
- ✅ `material_id` - Changed from `material_ids` (array) to single `material_id` (integer)
- ✅ `size_unit` - New required field (inches, cm, mm)
- ✅ `finish_ids` - New optional field (array of integers or null)
- ✅ `visibility` - New required field (dealers, converters, all)
- ✅ `location_source` - New required field (saved, manual)
- ✅ `location_id` - New optional field (only if location_source is 'saved')

**Removed Fields:**
- ❌ `title` - No longer required (auto-generated from material and quantity)
- ❌ `description` - Removed
- ❌ `price`, `price_unit`, `price_negotiable` - Removed
- ❌ `machine_ids`, `machine_condition` - Removed (machine requirements not supported)
- ❌ `job_type`, `timeline_days` - Removed (job requirements not supported)
- ❌ `specs`, `attachment_paths`, `deadline` - Removed

**Validation Rules:**
- Custom validation ensures `location_id` belongs to authenticated dealer if provided
- Custom validation ensures `location_id` is null when `location_source` is 'manual'
- `finish_ids` must all exist in `material_finishes` table
- `size` must match format: `WidthxHeight` (e.g., "28x40" or "20.5x30")

### 3. Service Layer (`DealerService::postRequirement`)

**Updated Logic:**
- Generates title automatically from material name and quantity if not provided
- Handles single `material_id` instead of array
- Syncs `finish_ids` to `inquiry_finishes` pivot table
- Stores new fields: `size_unit`, `visibility`, `location_source`, `location_id`
- Sets inquiry status to `MATCHING` immediately
- Sets `posted_at` and `matching_started_at` timestamps
- Sets `is_visible_to_dealers` to true

### 4. Model Updates (`Inquiry`)

**New Fillable Fields:**
- `size_unit`
- `visibility`
- `location_source`
- `location_id`

**New Relationships:**
- `finishes()` - BelongsToMany relationship with MaterialFinish via `inquiry_finishes` pivot
- `dealerLocation()` - BelongsTo relationship with DealerLocation

### 5. Postman Collection

**Updated Requests:**
- "Post Material Buy Requirement" - Updated with new field structure
- "Post Material Buy (With Saved Location)" - New example with saved location
- Removed: "Post Material Sell Requirement", "Post Machine Buy Requirement", "Post Job Outsourcing Requirement" (not supported in new API)

## 📝 API Request Format

### Required Fields

```json
{
  "inquiry_type": "material",
  "intent": "buy",
  "material_id": 75,
  "thickness": 350,
  "thickness_unit": "GSM",
  "size": "28x40",
  "size_unit": "inches",
  "quantity": 5000,
  "quantity_unit": "sheets",
  "urgency": "normal",
  "visibility": "all",
  "location_source": "manual",
  "location": "Mumbai, Maharashtra",
  "latitude": 19.0760,
  "longitude": 72.8777
}
```

### Optional Fields

```json
{
  "finish_ids": [12, 45, 67],  // or null
  "location_id": 31            // only if location_source is "saved"
}
```

### Example with Saved Location

```json
{
  "inquiry_type": "material",
  "intent": "buy",
  "material_id": 75,
  "thickness": 200,
  "thickness_unit": "GSM",
  "size": "20x30",
  "size_unit": "cm",
  "finish_ids": null,
  "quantity": 2,
  "quantity_unit": "tonnes",
  "urgency": "urgent",
  "visibility": "converters",
  "location_id": 31,
  "location_source": "saved",
  "location": "Muzaffarnagar",
  "latitude": 29.49149580,
  "longitude": 77.69830820
}
```

## 🔄 Migration Steps

1. **Run Migrations:**
   ```bash
   php artisan migrate
   ```

2. **Update Existing Data (if needed):**
   - Existing inquiries with `visibility = 'manufacturers'` will be automatically updated to `'all'`
   - No other data migration required

## ⚠️ Breaking Changes

1. **API Only Accepts Material Buy Requirements:**
   - Machine and job requirements are no longer supported via this endpoint
   - `inquiry_type` must be `'material'`
   - `intent` must be `'buy'`

2. **Material Selection Changed:**
   - Changed from `material_ids` (array) to `material_id` (single integer)
   - Only one material can be selected per requirement

3. **Removed Fields:**
   - `title` and `description` are no longer accepted (title is auto-generated)
   - All price-related fields removed
   - Machine and job-specific fields removed

4. **New Required Fields:**
   - `size_unit` - Must be provided
   - `visibility` - Must be provided
   - `location_source` - Must be provided

## 🧪 Testing Checklist

- [x] Migration creates new fields successfully
- [x] Migration creates `inquiry_finishes` pivot table
- [x] Request validation accepts only new fields
- [x] Request validation rejects old fields
- [x] Service handles single `material_id`
- [x] Service syncs `finish_ids` to pivot table
- [x] Service stores new fields correctly
- [x] Model relationships work correctly
- [x] Postman collection updated

## 📚 Related Files

- `database/migrations/2026_01_22_080659_add_dealer_requirement_fields_to_inquiries_table.php`
- `database/migrations/2026_01_22_080701_create_inquiry_finishes_table.php`
- `app/Http/Requests/Dealer/PostRequirementRequest.php`
- `app/Services/DealerService.php`
- `app/Models/Inquiry.php`
- `Paper_X_Posting_Requirements_Collection.postman_collection.json`

## ✅ Summary

The API now strictly accepts only the fields specified in the requirements document:
- Material buy requirements only
- Single material selection
- New fields: `size_unit`, `visibility`, `location_source`, `location_id`
- Optional: `finish_ids` for grade/finish/variant selection
- Removed: title, description, price fields, machine/job support

All changes are backward compatible with database structure (new fields are nullable where appropriate), but the API validation is stricter and only accepts the new format.
