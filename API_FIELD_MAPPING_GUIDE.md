# API Field Mapping Guide - Dealer Requirement Post

## ⚠️ Important Field Mappings

### Urgency Field

**Frontend Display Values → API Values:**

| Frontend Display | API Value | Description |
|-----------------|-----------|-------------|
| "Normal 3-5 Days" | `"normal"` | Standard timeline |
| "Urgent 1-2 Days" | `"urgent"` | Urgent requirement |

**❌ Wrong:**
```json
{
  "urgency": "Normal 3-5 Days"  // ❌ This will fail validation
}
```

**✅ Correct:**
```json
{
  "urgency": "normal"  // ✅ Use lowercase "normal" or "urgent"
}
```

### Location ID Validation

**Important:** When using `location_source: "saved"`, the `location_id` **must belong to the authenticated dealer**.

**Validation Rules:**
- `location_id` must exist in `dealer_locations` table
- `location_id` must belong to the authenticated dealer's locations
- If `location_source` is `"manual"`, `location_id` should be `null`

**❌ Wrong:**
```json
{
  "location_source": "saved",
  "location_id": 49,  // ❌ If this doesn't belong to your dealer account
  "location": "Agra",
  "latitude": 27.1752554,
  "longitude": 78.0098161
}
```

**✅ Correct:**
```json
{
  "location_source": "saved",
  "location_id": 31,  // ✅ Must be a location ID that belongs to your dealer
  "location": "Muzaffarnagar",
  "latitude": 29.49149580,
  "longitude": 77.69830820
}
```

**Or use manual location:**
```json
{
  "location_source": "manual",
  "location_id": null,  // ✅ Must be null for manual locations
  "location": "Agra",
  "latitude": 27.1752554,
  "longitude": 78.0098161
}
```

## Complete Correct Request Example

```json
{
  "inquiry_type": "material",
  "intent": "buy",
  "material_id": 75,
  "thickness": 364,
  "thickness_unit": "GSM",
  "size": "25x46",
  "size_unit": "inches",
  "finish_ids": [13],
  "quantity": 200,
  "quantity_unit": "sheets",
  "urgency": "normal",
  "visibility": "all",
  "location_source": "manual",
  "location_id": null,
  "location": "Agra",
  "latitude": 27.1752554,
  "longitude": 78.0098161
}
```

## How to Get Your Dealer Locations

To get the list of locations that belong to your dealer account, use:

**Endpoint:** `GET /api/v1/dealer/profile` or check your dealer profile completion data.

The locations are stored in the `dealer_locations` table and linked to your dealer via `dealer_id`.

## Common Errors and Solutions

### Error 1: "The selected urgency is invalid"
**Cause:** Frontend is sending display text instead of API value
**Solution:** Map frontend values:
- "Normal 3-5 Days" → `"normal"`
- "Urgent 1-2 Days" → `"urgent"`

### Error 2: "Selected location does not belong to you"
**Cause:** `location_id` doesn't belong to authenticated dealer
**Solutions:**
1. Use a valid `location_id` from your dealer's saved locations
2. Or use `location_source: "manual"` with `location_id: null`

### Error 3: "Location ID should be null when location source is manual"
**Cause:** Sending `location_id` when `location_source` is `"manual"`
**Solution:** Set `location_id` to `null` when using manual locations
