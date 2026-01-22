# Postman Testing Guide - Dealer Requirement Session Creation

## Prerequisites

1. **Set up Postman Environment Variables:**
   - `base_url`: `http://127.0.0.1:8000` (or your backend URL)
   - `auth_token`: Your authentication token (Bearer token)

2. **Authentication:**
   - First, authenticate to get a token:
   - `POST {{base_url}}/api/v1/auth/otp/request` (send OTP)
   - `POST {{base_url}}/api/v1/auth/otp/verify` (verify OTP and get token)

---

## Test Flow: Post Requirement → Verify Session Created

### Step 1: Post a Dealer Requirement

**Endpoint:** `POST {{base_url}}/api/v1/dealer/requirement/post`

**Headers:**
```
Authorization: Bearer {{auth_token}}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "inquiry_type": "material",
  "intent": "buy",
  "material_id": 1,
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
  "longitude": 72.8777,
  "finish_ids": null,
  "location_id": null
}
```

**Expected Response (200/201):**
```json
{
  "success": true,
  "message": "Requirement posted successfully",
  "data": {
    "id": 123,
    "poster_id": 1,
    "poster_type": "dealer",
    "inquiry_type": "material",
    "intent": "buy",
    "title": "Material Name - 5000 sheets",
    "status": "MATCHING",
    "items": [
      {
        "id": 456,
        "material_id": 1,
        "material_category": "Material Name",
        "quantity": 5000,
        "quantity_unit": "sheets",
        "thickness_gsm": 350,
        "thickness_unit": "gsm"
      }
    ],
    "session": {
      "id": 789,
      "status": "ACTIVE",
      "expires_at": "2026-01-23T12:00:00Z"
    }
  }
}
```

**✅ What to Check:**
- Response status: `200` or `201`
- `data.status` should be `"MATCHING"`
- `data.session` should exist with `status: "ACTIVE"`
- `data.items` array should have the inquiry item
- Note the `inquiry.id` and `session.id` for next steps

---

### Step 2: Verify Session Appears in Active Sessions

**Endpoint:** `GET {{base_url}}/api/v1/sessions/active`

**Headers:**
```
Authorization: Bearer {{auth_token}}
Accept: application/json
```

**Query Parameters (Optional):**
- `filter`: `all` | `finding_matches` | `active` | `locked` (default: `all`)
- `page`: `1` (default: `1`)
- `per_page`: `15` (default: `15`)

**Expected Response (200):**
```json
{
  "success": true,
  "message": "Active sessions retrieved successfully",
  "data": {
    "data": [
      {
        "id": 789,
        "inquiry_id": 123,
        "title": "Material Name - 5000 sheets",
        "status": "MATCHING",
        "status_label": "FINDING",
        "urgency": "normal",
        "created_at": "2026-01-22T12:00:00Z",
        "items": [
          {
            "material_category": "Material Name",
            "quantity": 5000,
            "quantity_unit": "sheets"
          }
        ],
        "countdown": {
          "days": 0,
          "hours": 23,
          "minutes": 59,
          "seconds": 59,
          "formatted": "00 DAYS 23 HOURS 59 MINS 59 SECS"
        },
        "responses_received": 0,
        "matched_dealers_count": 5,
        "matching_progress": {
          "matched": 5,
          "total": 15,
          "status": "Scanning suppliers..."
        }
      }
    ],
    "current_page": 1,
    "total": 1,
    "per_page": 15,
    "last_page": 1
  }
}
```

**✅ What to Check:**
- Your posted requirement should appear in the `data.data` array
- `status` should be `"MATCHING"` or `"ACTIVE"`
- `inquiry_id` should match the ID from Step 1
- `matched_dealers_count` should be > 0 (if dealers were matched)
- `matching_progress` should show progress if status is `"MATCHING"`

---

### Step 3: Get Session Details

**Endpoint:** `GET {{base_url}}/api/v1/sessions/{{session_id}}`

**Headers:**
```
Authorization: Bearer {{auth_token}}
Accept: application/json
```

**Replace `{{session_id}}`** with the session ID from Step 1 (e.g., `789`)

**Expected Response (200):**
```json
{
  "success": true,
  "message": "Session details retrieved",
  "data": {
    "id": 789,
    "project_id": "PRJ-0789",
    "status": "ACTIVE",
    "inquiry": {
      "id": 123,
      "title": "Material Name - 5000 sheets",
      "items": [
        {
          "material_category": "Material Name",
          "quantity": 5000,
          "quantity_unit": "sheets"
        }
      ]
    },
    "selected_partners_count": 0,
    "selected_partners": [],
    "chat_enabled": false,
    "chat_thread_id": null,
    "locked_at": "2026-01-22T12:00:00Z"
  }
}
```

**✅ What to Check:**
- Session exists and has correct `inquiry_id`
- `status` is `"ACTIVE"` or `"MATCHING"`
- `inquiry.items` array contains the requirement details

---

## Complete Test Checklist

### ✅ Test 1: Post Requirement Creates Session
- [ ] Post requirement successfully (200/201)
- [ ] Response includes `session` object
- [ ] Session status is `"ACTIVE"`
- [ ] Inquiry status is `"MATCHING"`

### ✅ Test 2: Session Appears in Active Sessions
- [ ] Call `GET /api/v1/sessions/active`
- [ ] Posted requirement appears in the list
- [ ] Session has correct `inquiry_id`
- [ ] Status is `"MATCHING"` or `"ACTIVE"`

### ✅ Test 3: Session Details Are Correct
- [ ] Call `GET /api/v1/sessions/{session_id}`
- [ ] Session details match the posted requirement
- [ ] Inquiry items are present
- [ ] Matchmaking has started (`matched_dealers_count` > 0)

### ✅ Test 4: Multiple Requirements
- [ ] Post 2-3 different requirements
- [ ] All appear in active sessions
- [ ] Each has unique session ID
- [ ] Filter by `filter=all` shows all sessions

---

## Troubleshooting

### Issue: Session not appearing in active sessions

**Check:**
1. Verify the requirement was posted successfully (check response)
2. Check if `session` object exists in the post response
3. Verify you're using the correct auth token (same user who posted)
4. Check session status - only `MATCHING`, `RESPONSES_RECEIVED`, `LOCKED`, `CHAT_ACTIVE` appear in active sessions
5. Check database directly:
   ```sql
   SELECT * FROM matching_sessions WHERE inquiry_id = {your_inquiry_id};
   ```

### Issue: Matchmaking not working

**Check:**
1. Verify `InquiryItem` was created:
   ```sql
   SELECT * FROM inquiry_items WHERE inquiry_id = {your_inquiry_id};
   ```
2. Check if dealers exist with matching materials
3. Check matchmaking logs:
   ```sql
   SELECT * FROM matchmaking_logs WHERE inquiry_id = {your_inquiry_id};
   ```

### Issue: Error 500 or Database Error

**Check:**
1. Verify all required fields are present
2. Check `material_id` exists in database
3. Check dealer profile is complete
4. Check Laravel logs: `storage/logs/laravel.log`

---

## Example Postman Collection

You can import this into Postman:

```json
{
  "info": {
    "name": "Dealer Requirement Session Test",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "1. Post Requirement",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{auth_token}}"
          },
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"inquiry_type\": \"material\",\n  \"intent\": \"buy\",\n  \"material_id\": 1,\n  \"thickness\": 350,\n  \"thickness_unit\": \"GSM\",\n  \"size\": \"28x40\",\n  \"size_unit\": \"inches\",\n  \"quantity\": 5000,\n  \"quantity_unit\": \"sheets\",\n  \"urgency\": \"normal\",\n  \"visibility\": \"all\",\n  \"location_source\": \"manual\",\n  \"location\": \"Mumbai, Maharashtra\",\n  \"latitude\": 19.0760,\n  \"longitude\": 72.8777\n}"
        },
        "url": {
          "raw": "{{base_url}}/api/v1/dealer/requirement/post",
          "host": ["{{base_url}}"],
          "path": ["api", "v1", "dealer", "requirement", "post"]
        }
      },
      "event": [
        {
          "listen": "test",
          "script": {
            "exec": [
              "if (pm.response.code === 200 || pm.response.code === 201) {",
              "    var jsonData = pm.response.json();",
              "    if (jsonData.data && jsonData.data.id) {",
              "        pm.collectionVariables.set('inquiry_id', jsonData.data.id);",
              "    }",
              "    if (jsonData.data && jsonData.data.session && jsonData.data.session.id) {",
              "        pm.collectionVariables.set('session_id', jsonData.data.session.id);",
              "        console.log('✅ Session created:', jsonData.data.session.id);",
              "    }",
              "}"
            ]
          }
        }
      ]
    },
    {
      "name": "2. Get Active Sessions",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{auth_token}}"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/v1/sessions/active?filter=all",
          "host": ["{{base_url}}"],
          "path": ["api", "v1", "sessions", "active"],
          "query": [
            {
              "key": "filter",
              "value": "all"
            }
          ]
        }
      }
    },
    {
      "name": "3. Get Session Details",
      "request": {
        "method": "GET",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{auth_token}}"
          }
        ],
        "url": {
          "raw": "{{base_url}}/api/v1/sessions/{{session_id}}",
          "host": ["{{base_url}}"],
          "path": ["api", "v1", "sessions", "{{session_id}}"]
        }
      }
    }
  ]
}
```

---

## Quick Test Script

Save this as a Postman test script for the "Post Requirement" request:

```javascript
// Test that session was created
pm.test("Status code is 200 or 201", function () {
    pm.expect(pm.response.code).to.be.oneOf([200, 201]);
});

pm.test("Response has session object", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data).to.have.property('session');
    pm.expect(jsonData.data.session).to.have.property('id');
    pm.expect(jsonData.data.session).to.have.property('status');
    pm.expect(jsonData.data.session.status).to.equal('ACTIVE');
});

pm.test("Inquiry has MATCHING status", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data.status).to.equal('MATCHING');
});

pm.test("InquiryItem was created", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data).to.have.property('items');
    pm.expect(jsonData.data.items).to.be.an('array');
    pm.expect(jsonData.data.items.length).to.be.greaterThan(0);
});

// Save session ID for next request
if (pm.response.code === 200 || pm.response.code === 201) {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.session) {
        pm.collectionVariables.set('session_id', jsonData.data.session.id);
        pm.collectionVariables.set('inquiry_id', jsonData.data.id);
    }
}
```

---

## Notes

- **Material ID**: Make sure the `material_id` exists in your database. Check with: `GET {{base_url}}/api/v1/materials`
- **Dealer Profile**: Your dealer profile must be complete before posting requirements
- **Location**: Coordinates must be valid (latitude: -90 to 90, longitude: -180 to 180)
- **Size Format**: Must be in format `"WidthxHeight"` (e.g., `"28x40"`, `"20.5x30"`)
