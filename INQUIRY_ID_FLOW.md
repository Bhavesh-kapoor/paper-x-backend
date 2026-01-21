# Where Does the Inquiry ID Come From?

## Question
For `POST /api/v1/inquiries/{id}/post`, where does the `{id}` come from?

## Answer: Two-Step Process

The inquiry ID comes from **first creating an inquiry** using `POST /api/v1/inquiries`. This endpoint returns the created inquiry with its ID, which you then use to post it.

---

## Complete Flow

### Step 1: Create Inquiry (DRAFT)
**Endpoint:** `POST /api/v1/inquiries`

**Purpose:** Creates a new inquiry in DRAFT status

**Request:**
```json
POST /api/v1/inquiries
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Need Duplex Board 350 GSM",
  "description": "Looking for high-quality duplex board",
  "inquiry_type": "material",
  "intent": "buy",
  "urgency": "normal",
  "location": "Mumbai, Maharashtra",
  "latitude": 19.0760,
  "longitude": 72.8777,
  "items": [
    {
      "material_category": "Duplex Board",
      "thickness_gsm": 350,
      "quantity": 2000,
      "quantity_unit": "sheets"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Inquiry created successfully",
  "data": {
    "id": 25,  // ← THIS IS THE ID YOU NEED!
    "title": "Need Duplex Board 350 GSM",
    "status": "DRAFT",
    "inquiry_type": "material",
    "intent": "buy",
    "urgency": "normal",
    "location": "Mumbai, Maharashtra",
    "items": [
      {
        "id": 1,
        "material_category": "Duplex Board",
        "thickness_gsm": 350,
        "quantity": 2000,
        "quantity_unit": "sheets"
      }
    ],
    "created_at": "2026-01-21T17:00:00Z",
    "updated_at": "2026-01-21T17:00:00Z"
  }
}
```

**Key Point:** The response includes `"id": 25` - this is the inquiry ID you'll use in the next step.

---

### Step 2: Post Inquiry (Using the ID from Step 1)
**Endpoint:** `POST /api/v1/inquiries/{id}/post`

**Purpose:** Posts the inquiry (changes status from DRAFT to MATCHING)

**Request:**
```json
POST /api/v1/inquiries/25/post  // ← Using ID from Step 1
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Inquiry posted successfully",
  "data": {
    "inquiry": {
      "id": 25,
      "title": "Need Duplex Board 350 GSM",
      "status": "MATCHING",  // ← Changed from DRAFT
      "posted_at": "2026-01-21T17:05:00Z",
      "session": {
        "id": 10,
        "status": "ACTIVE",
        "expires_at": "2026-01-22T17:05:00Z"
      }
    },
    "matched_dealers_count": 10
  }
}
```

---

## Visual Flow Diagram

```
┌─────────────────────────────────────────────────────────┐
│ Step 1: Create Inquiry (DRAFT)                           │
│ POST /api/v1/inquiries                                   │
│                                                           │
│ Request Body: { title, description, items, ... }        │
│                                                           │
│ Response: { id: 25, status: "DRAFT", ... }              │
│            ↑                                            │
│            └─── Save this ID                            │
└─────────────────────────────────────────────────────────┘
                        │
                        │ Use ID: 25
                        ▼
┌─────────────────────────────────────────────────────────┐
│ Step 2: Post Inquiry                                    │
│ POST /api/v1/inquiries/25/post                         │
│                                                           │
│ Request: (no body needed)                               │
│                                                           │
│ Response: { status: "MATCHING", session: {...} }       │
└─────────────────────────────────────────────────────────┘
```

---

## Code Example (JavaScript/Fetch)

```javascript
// Step 1: Create inquiry
const createResponse = await fetch('http://127.0.0.1:8000/api/v1/inquiries', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    title: "Need Duplex Board 350 GSM",
    description: "Looking for high-quality duplex board",
    inquiry_type: "material",
    intent: "buy",
    urgency: "normal",
    location: "Mumbai, Maharashtra",
    items: [
      {
        material_category: "Duplex Board",
        thickness_gsm: 350,
        quantity: 2000,
        quantity_unit: "sheets"
      }
    ]
  })
});

const createData = await createResponse.json();
const inquiryId = createData.data.id;  // ← Get ID from response
console.log('Created inquiry ID:', inquiryId);

// Step 2: Post inquiry using the ID
const postResponse = await fetch(`http://127.0.0.1:8000/api/v1/inquiries/${inquiryId}/post`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

const postData = await postResponse.json();
console.log('Inquiry posted:', postData);
```

---

## Alternative: Save Step API

If you want to save the inquiry in steps (multi-step form), you can use:

**Endpoint:** `POST /api/v1/inquiries/save-step`

This also creates/updates an inquiry and returns the ID:

```json
POST /api/v1/inquiries/save-step
{
  "step": 1,
  "title": "Need Duplex Board",
  "inquiry_type": "material"
}

Response:
{
  "success": true,
  "data": {
    "inquiry_id": 25,  // ← ID returned here too
    "step": 1,
    "message": "Step saved successfully"
  }
}
```

Then use this ID to post: `POST /api/v1/inquiries/25/post`

---

## Summary

**The inquiry ID comes from:**

1. **Creating an inquiry first** using:
   - `POST /api/v1/inquiries` (creates new inquiry)
   - `POST /api/v1/inquiries/save-step` (saves step-by-step)

2. **Extracting the ID** from the response:
   ```json
   {
     "data": {
       "id": 25  // ← This is your inquiry ID
     }
   }
   ```

3. **Using that ID** to post:
   ```
   POST /api/v1/inquiries/25/post
   ```

---

## Why Two Steps?

### Benefits of Two-Step Process:

1. **Draft Support** - Save inquiry before posting
2. **Edit Before Posting** - Make changes to draft
3. **Multi-Step Forms** - Save progress step by step
4. **Validation** - Validate data before posting
5. **Payment Check** - Ensure wallet balance before posting

### Single-Step Alternative:

If you want to create and post in one step, use:
- `POST /api/v1/brand/requirement/post` (brands only, legacy API)

But the new unified API (`POST /api/v1/inquiries/{id}/post`) requires the two-step process for flexibility.

---

## API Endpoints Summary

| Endpoint | Purpose | Returns ID? |
|----------|---------|------------|
| `POST /api/v1/inquiries` | Create inquiry (DRAFT) | ✅ Yes (`data.id`) |
| `POST /api/v1/inquiries/save-step` | Save inquiry step | ✅ Yes (`data.inquiry_id`) |
| `POST /api/v1/inquiries/{id}/post` | Post inquiry | Uses ID from above |
| `POST /api/v1/brand/requirement/post` | Create + post (one-step) | ✅ Yes (but posts immediately) |

---

## Code References

- **Create Inquiry:** `app/Http/Controllers/api/InquiryController.php::store()` (line 62-107)
- **Post Inquiry:** `app/Http/Controllers/api/InquiryController.php::post()` (line 141-260)
- **Save Step:** `app/Http/Controllers/api/InquiryController.php::saveStep()` (line 300+)

