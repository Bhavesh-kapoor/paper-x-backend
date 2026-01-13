# DEALER API - COMPLETE DOCUMENTATION
## B2B Matchmaking Platform - Packaging & Printing Industry

---

## TABLE OF CONTENTS

1. [Authentication Flow](#1-authentication-flow)
2. [Dealer Profile Completion](#2-dealer-profile-completion)
3. [Dealer Dashboard](#3-dealer-dashboard)
4. [Opportunity Feed & Details](#4-opportunity-feed--details)
5. [Accept/Decline Opportunity](#5-acceptdecline-opportunity)
6. [Session Management](#6-session-management)
7. [Chat System](#7-chat-system)
8. [Quotation Submission](#8-quotation-submission)
9. [Notifications](#9-notifications)
10. [Role Switching](#10-role-switching)
11. [Complete Flow Example](#11-complete-flow-example)

---

## 1. AUTHENTICATION FLOW

### Step 1: Request OTP

**Endpoint:** `POST /api/v1/auth/otp/request`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Payload:**
```json
{
  "mobile": "9876543210"
}
```

**Response Payload (200 OK):**
```json
{
  "success": true,
  "message": "auth.otp.success",
  "data": {
    "type": "otp_sent",
    "message": "OTP sent successfully"
  }
}
```

---

### Step 2: Verify OTP & Login

**Endpoint:** `POST /api/v1/auth/otp/verify`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Payload:**
```json
{
  "mobile": "9876543210",
  "otp": "123456"
}
```

**Response Payload (200 OK):**
```json
{
  "success": true,
  "message": "auth.otp.success",
  "data": {
    "type": "login_success",
    "token": "1|MWvNaGn83puRCSajczQFh99397eb",
    "user": {
      "id": 1,
      "name": "John Doe",
      "mobile": "9876543210",
      "email": "john@example.com",
      "primary_role": "DEALER",
      "has_secondary_role": false
    }
  }
}
```

**Important:** Save the `token` from this response. You'll need it for all subsequent API calls.

---

## 2. DEALER PROFILE COMPLETION

### Endpoint
`POST /api/v1/dealer/profile/complete`

### Headers
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

### Request Payload

**Full Example:**
```json
{
  "materials": [
    {
      "material_id": 45,
      "brand_id": 1,
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
      "brand_id": null,
      "agent_type": null,
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

**Without Warehouse (Bulk Orders Only):**
```json
{
  "materials": [
    {
      "material_id": 45,
      "brand_id": null,
      "agent_type": null,
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

### Response Payload (201 Created)

**Success Response:**
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

**Error Response (422 Validation Error):**
```json
{
  "success": false,
  "message": "validation.failed",
  "errors": {
    "materials.0.thickness_ranges.0.max": [
      "The materials.0.thickness_ranges.0.max must be greater than or equal to materials.0.thickness_ranges.0.min."
    ]
  }
}
```

### Field Descriptions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `materials` | Array | Yes | Array of materials the dealer deals in |
| `materials[].material_id` | Integer | Yes | Material ID from materials list |
| `materials[].brand_id` | Integer | No | Mill/Brand ID (optional) |
| `materials[].agent_type` | String | Conditional | "AUTHORIZED_AGENT" or "DEALER" (required if brand_id is provided) |
| `materials[].finish_ids` | Array | No | Array of finish/grade IDs |
| `materials[].thickness_ranges` | Array | Yes | Thickness ranges per unit |
| `materials[].thickness_ranges[].unit` | String | Yes | "GSM", "MM", "OUNCE", "BF", or "MICRON" |
| `materials[].thickness_ranges[].min` | Number | Yes | Minimum thickness value |
| `materials[].thickness_ranges[].max` | Number | Yes | Maximum thickness value |
| `machines_available` | Array | Yes | Array of machine IDs |
| `capacity_daily` | Number | Yes | Daily production capacity |
| `capacity_monthly` | Number | Yes | Monthly production capacity |
| `capacity_unit` | String | Yes | Unit for capacity (kg, tons, pieces, etc.) |
| `has_warehouse` | Boolean | Yes | Whether dealer has warehouse |
| `bulk_orders_note` | String | No | Note if only bulk orders (required if has_warehouse = false) |
| `locations` | Array | Conditional | Required if has_warehouse = true |
| `locations[].type` | String | Yes | "factory" or "warehouse" |
| `locations[].address` | String | No | Full address |
| `locations[].latitude` | Number | Yes | Latitude coordinate |
| `locations[].longitude` | Number | Yes | Longitude coordinate |
| `locations[].city` | String | No | City name |
| `locations[].state` | String | No | State name |

### Business Logic
- Creates/updates dealer record
- Marks profile as COMPLETE
- Sets dealer status to ACTIVE
- Stores per-material details (mill, agent type, finishes, thickness ranges)
- Stores warehouse locations (if applicable)
- Dealer becomes eligible for matching after completion

### DB Tables Involved
- `dealers`
- `dealer_material_details`
- `dealer_materials` (pivot)
- `dealer_machines` (pivot)
- `dealer_locations`

---

## 3. DEALER DASHBOARD

### Endpoint
`GET /api/v1/dealer/dashboard`

### Headers
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

### Request Payload
None (authenticated via Bearer token)

### Response Payload (200 OK)

**Success Response:**
```json
{
  "success": true,
  "message": "Dashboard data retrieved successfully!",
  "data": {
    "profile_completion_percentage": 100,
    "active_opportunities_count": 5,
    "locked_sessions_count": 2,
    "expired_sessions_count": 1,
    "unread_notifications_count": 3
  }
}
```

**If Dealer Profile Not Completed:**
```json
{
  "success": true,
  "message": "Dashboard data retrieved successfully!",
  "data": {
    "profile_completion_percentage": 0,
    "active_opportunities_count": 0,
    "locked_sessions_count": 0,
    "expired_sessions_count": 0,
    "unread_notifications_count": 2
  }
}
```

### Field Descriptions

| Field | Type | Description |
|-------|------|-------------|
| `profile_completion_percentage` | Integer | Profile completion percentage (0-100) |
| `active_opportunities_count` | Integer | Number of active opportunities (sessions with status SESSION_LOCKED) |
| `locked_sessions_count` | Integer | Number of locked sessions where dealer is participant |
| `expired_sessions_count` | Integer | Number of expired sessions |
| `unread_notifications_count` | Integer | Number of unread notifications |

### Business Logic
- Calculates profile completion based on required fields
- Counts active opportunities (sessions with status SESSION_LOCKED)
- Counts locked sessions where dealer is participant
- Counts expired sessions
- Counts unread notifications

---

## 4. OPPORTUNITY FEED & DETAILS

### 4.1 Get Opportunities List

**Endpoint:** `GET /api/v1/dealer/opportunities`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Request Payload:** None

**Response Payload (200 OK):**
```json
{
  "success": true,
  "message": "Opportunities retrieved successfully!",
  "data": [
    {
      "inquiry_id": 1,
      "title": "Bulk Paper Order - Duplex Board",
      "quantity": 5000,
      "quantity_unit": "kg",
      "urgency": "urgent",
      "location": "Mumbai, Maharashtra",
      "time_left_to_accept": 86400,
      "match_score": 95
    },
    {
      "inquiry_id": 2,
      "title": "Kraft Paper Requirement",
      "quantity": 2000,
      "quantity_unit": "kg",
      "urgency": "normal",
      "location": "Delhi, NCR",
      "time_left_to_accept": 172800,
      "match_score": 87
    }
  ]
}
```

**Empty List (No Matching Opportunities):**
```json
{
  "success": true,
  "message": "Opportunities retrieved successfully!",
  "data": []
}
```

### Matching Criteria
Opportunities are shown ONLY IF:
- `inquiry.status = MATCHING`
- `inquiry.materials ⊆ dealer.materials` (all inquiry materials match dealer materials)
- `inquiry.required_machines ⊆ dealer.machines` (all inquiry machines match dealer machines)
- `inquiry.quantity ≤ dealer.capacity_monthly`
- Dealer has at least one location (if has_warehouse = true)
- Thickness matches with tolerance (see matching logic below)

### Match Score Calculation (0-100)
- Material match: 30 points
- Machine match: 30 points
- Capacity match: 20 points
- Geography match: 20 points

---

### 4.2 Get Opportunity Details

**Endpoint:** `GET /api/v1/dealer/opportunity/{inquiry_id}`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Example:** `GET /api/v1/dealer/opportunity/1`

**Request Payload:** None (inquiry_id in URL)

**Response Payload (200 OK):**
```json
{
  "success": true,
  "message": "Opportunity details retrieved successfully!",
  "data": {
    "inquiry_id": 1,
    "title": "Bulk Paper Order - Duplex Board",
    "description": "Requiring high-quality duplex board for packaging. Need 5000 kg of 350 GSM Duplex Board (White Back).",
    "quantity": 5000,
    "quantity_unit": "kg",
    "thickness": 350,
    "thickness_unit": "GSM",
    "urgency": "urgent",
    "location": "Mumbai, Maharashtra",
    "specs": {
      "grade": "A",
      "finish": "coated",
      "color": "white"
    },
    "attachments": [
      "https://example.com/attachments/specs.pdf"
    ],
    "timeline": {
      "deadline": "2026-01-15T00:00:00Z"
    },
    "brand_name": null,
    "materials": ["Duplex Board (White Back)"],
    "machines": ["Printing Machine", "Cutting Machine"]
  }
}
```

**Note:** Brand name is hidden (null) before session lock.

---

## 5. ACCEPT/DECLINE OPPORTUNITY

### 5.1 Accept Opportunity

**Endpoint:** `POST /api/v1/dealer/opportunity/{id}/accept`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

**Example:** `POST /api/v1/dealer/opportunity/1/accept`

**Request Payload:**
```json
{}
```

**Response Payload (200 OK):**

**Success (Before 10 acceptances):**
```json
{
  "success": true,
  "message": "Opportunity accepted successfully!",
  "data": {
    "message": "Opportunity accepted successfully",
    "accepted_count": 7
  }
}
```

**Success (10th Acceptance - Session Locked):**
```json
{
  "success": true,
  "message": "Opportunity accepted successfully!",
  "data": {
    "message": "Opportunity accepted successfully",
    "accepted_count": 10,
    "session_locked": true,
    "session_id": 5
  }
}
```

**Error Response (400 Bad Request - Already Accepted):**
```json
{
  "success": false,
  "message": "Already accepted or declined this opportunity",
  "errors": null
}
```

**Error Response (400 Bad Request - Maximum Reached):**
```json
{
  "success": false,
  "message": "Maximum acceptances reached",
  "errors": null
}
```

### Business Logic
- Validates inquiry status is MATCHING
- Checks if dealer already accepted/declined (idempotent)
- Counts current acceptances
- If accepted_count < 10 → allows acceptance
- If accepted_count >= 10 → throws error (auto reject)
- Creates DealerAcceptance record
- If new count = 10 → automatically locks session
- Notifies all 10 accepted dealers

---

### 5.2 Decline Opportunity

**Endpoint:** `POST /api/v1/dealer/opportunity/{id}/decline`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

**Example:** `POST /api/v1/dealer/opportunity/1/decline`

**Request Payload:**
```json
{
  "reason": "Capacity constraints - currently at full capacity"
}
```

**Or (without reason):**
```json
{}
```

**Response Payload (200 OK):**
```json
{
  "success": true,
  "message": "Opportunity declined successfully!",
  "data": {
    "message": "Opportunity declined successfully"
  }
}
```

### Business Logic
- Validates inquiry status is MATCHING
- Creates/updates DealerAcceptance with status DECLINED
- Stores optional decline reason
- Idempotent operation

---

## 6. SESSION MANAGEMENT

### 6.1 Get Session Details

**Endpoint:** `GET /api/v1/dealer/session/{session_id}`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Example:** `GET /api/v1/dealer/session/5`

**Request Payload:** None

**Response Payload (200 OK):**
```json
{
  "success": true,
  "message": "Session details retrieved successfully!",
  "data": {
    "session_id": 5,
    "expires_in": 1800,
    "brand_details": {
      "name": "ABC Packaging Ltd",
      "company_name": "ABC Packaging Ltd"
    },
    "inquiry": {
      "id": 1,
      "title": "Bulk Paper Order - Duplex Board",
      "description": "Requiring high-quality duplex board for packaging",
      "quantity": 5000,
      "quantity_unit": "kg",
      "thickness": 350,
      "thickness_unit": "GSM"
    },
    "chat_enabled": true,
    "status": "ACTIVE"
  }
}
```

**Error Response (403 Forbidden - Not Participant):**
```json
{
  "success": false,
  "message": "You are not part of this session",
  "errors": null
}
```

### Business Logic
- Verifies dealer is participant in session
- Returns session details with brand name (now visible)
- Calculates expires_in (seconds remaining)
- Returns inquiry details
- Chat is always enabled for active sessions

---

### 6.2 Get Session History

**Endpoint:** `GET /api/v1/dealer/history`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Request Payload:** None

**Response Payload (200 OK):**
```json
{
  "success": true,
  "message": "Session history retrieved successfully!",
  "data": [
    {
      "session_id": 5,
      "inquiry_id": 1,
      "inquiry_title": "Bulk Paper Order - Duplex Board",
      "brand_name": "ABC Packaging Ltd",
      "status": "DEAL_WON",
      "result": "DEAL_WON",
      "locked_at": "2026-01-03T09:00:00Z",
      "expires_at": "2026-01-03T09:30:00Z"
    },
    {
      "session_id": 3,
      "inquiry_id": 2,
      "inquiry_title": "Kraft Paper Requirement",
      "brand_name": "XYZ Industries",
      "status": "EXPIRED",
      "result": "SESSION_EXPIRED",
      "locked_at": "2026-01-02T10:00:00Z",
      "expires_at": "2026-01-02T10:30:00Z"
    }
  ]
}
```

### Result Values
- `DEAL_WON`: Dealer won the deal
- `DEAL_LOST`: Another dealer won the deal
- `SESSION_EXPIRED`: Session expired without deal
- `BRAND_CANCELLED`: Brand cancelled the inquiry
- `PENDING`: Session still active

---

## 7. CHAT SYSTEM

### 7.1 Get Chat Messages

**Endpoint:** `GET /api/v1/dealer/chat/{session_id}`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Example:** `GET /api/v1/dealer/chat/5`

**Request Payload:** None

**Response Payload (200 OK):**
```json
{
  "success": true,
  "message": "Chat messages retrieved successfully!",
  "data": [
    {
      "id": 1,
      "sender_id": 1,
      "sender_type": "DEALER",
      "sender_name": "John Doe",
      "message": "Hello, I can deliver within 15 days at ₹50 per kg",
      "attachment_path": null,
      "attachment_url": null,
      "status": "SENT",
      "created_at": "2026-01-03T10:00:00Z",
      "is_own_message": true
    },
    {
      "id": 2,
      "sender_id": 10,
      "sender_type": "BRAND",
      "sender_name": "ABC Packaging Ltd",
      "message": "Can you deliver by next week?",
      "attachment_path": null,
      "attachment_url": null,
      "status": "SENT",
      "created_at": "2026-01-03T10:05:00Z",
      "is_own_message": false
    }
  ]
}
```

---

### 7.2 Send Chat Message

**Endpoint:** `POST /api/v1/dealer/chat/{session_id}/message`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: multipart/form-data
Accept: application/json
```

**Example:** `POST /api/v1/dealer/chat/5/message`

**Request Payload (Text Message):**
```
message: "I can deliver within 15 days at ₹50 per kg. Please confirm."
```

**Request Payload (With Attachment):**
```
message: "Please see the attached quotation"
attachment: <file>
```

**Response Payload (201 Created):**
```json
{
  "success": true,
  "message": "Message sent successfully!",
  "data": {
    "id": 3,
    "session_id": 5,
    "sender_id": 1,
    "sender_type": "DEALER",
    "message": "I can deliver within 15 days at ₹50 per kg. Please confirm.",
    "attachment_path": null,
    "status": "SENT",
    "created_at": "2026-01-03T10:10:00Z"
  }
}
```

**Response Payload (With Attachment):**
```json
{
  "success": true,
  "message": "Message sent successfully!",
  "data": {
    "id": 4,
    "session_id": 5,
    "sender_id": 1,
    "sender_type": "DEALER",
    "message": "Please see the attached quotation",
    "attachment_path": "chat_attachments/1704276600_quotation.pdf",
    "attachment_url": "http://example.com/chat_attachments/1704276600_quotation.pdf",
    "status": "SENT",
    "created_at": "2026-01-03T10:15:00Z"
  }
}
```

### File Upload Rules
- Maximum file size: 10MB
- Allowed file types: Any
- Files stored in: `public/chat_attachments/`

---

## 8. QUOTATION SUBMISSION

### Endpoint
`POST /api/v1/dealer/quote/submit/{inquiry_id}`

### Headers
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

### Example
`POST /api/v1/dealer/quote/submit/1`

### Request Payload
```json
{
  "quoted_price": 500000.00,
  "currency": "INR",
  "delivery_days": 15,
  "notes": "Can provide additional 5% discount for bulk orders above 10 tons. Price includes GST."
}
```

### Response Payload (201 Created)

**Success Response:**
```json
{
  "success": true,
  "message": "Quotation submitted successfully!",
  "data": {
    "id": 1,
    "dealer_id": 1,
    "inquiry_id": 1,
    "session_id": 5,
    "quoted_price": "500000.00",
    "currency": "INR",
    "delivery_days": 15,
    "notes": "Can provide additional 5% discount for bulk orders above 10 tons. Price includes GST.",
    "deal_status": "PENDING",
    "created_at": "2026-01-03T10:20:00Z",
    "updated_at": "2026-01-03T10:20:00Z"
  }
}
```

**Error Response (403 Forbidden - Not Participant):**
```json
{
  "success": false,
  "message": "You are not part of this session",
  "errors": null
}
```

### Field Descriptions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `quoted_price` | Number | Yes | Quoted price in specified currency |
| `currency` | String | No | Currency code (default: INR) |
| `delivery_days` | Integer | Yes | Number of days for delivery |
| `notes` | String | No | Additional notes/comments |

### Business Logic
- Verifies dealer is participant in session
- Creates/updates Quotation record
- Links to session_id
- Sets deal_status = PENDING
- Stores quoted price, delivery timeline, notes
- Idempotent (updateOrCreate)

---

## 9. NOTIFICATIONS

### 9.1 Get Notifications

**Endpoint:** `GET /api/v1/dealer/notifications?unread_only=false`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Query Parameters:**
- `unread_only` (boolean, optional): Filter unread only (default: false)

**Examples:**
- `GET /api/v1/dealer/notifications` (all notifications)
- `GET /api/v1/dealer/notifications?unread_only=true` (unread only)

**Response Payload (200 OK):**
```json
{
  "success": true,
  "message": "Notifications retrieved successfully!",
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "type": "SESSION_LOCKED",
      "title": "Session Locked",
      "message": "Session for inquiry #1 has been locked. You can now view brand details and start chatting.",
      "notifiable_type": "App\\Models\\MatchingSession",
      "notifiable_id": 5,
      "read": false,
      "read_at": null,
      "created_at": "2026-01-03T09:00:00Z"
    },
    {
      "id": 2,
      "user_id": 1,
      "type": "NEW_MESSAGE",
      "title": "New Message",
      "message": "You have a new message in session #5",
      "notifiable_type": "App\\Models\\MatchingSession",
      "notifiable_id": 5,
      "read": true,
      "read_at": "2026-01-03T10:00:00Z",
      "created_at": "2026-01-03T09:30:00Z"
    }
  ]
}
```

### Notification Types
- `NEW_OPPORTUNITY`: New matching inquiry available
- `SESSION_LOCKED`: Session locked (10 acceptances reached)
- `NEW_MESSAGE`: New chat message received
- `DEAL_RESULT`: Deal won/lost notification
- `SESSION_EXPIRED`: Session expired notification

---

### 9.2 Mark Notification as Read

**Endpoint:** `POST /api/v1/dealer/notification/{id}/read`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Example:** `POST /api/v1/dealer/notification/1/read`

**Request Payload:** None

**Response Payload (200 OK):**
```json
{
  "success": true,
  "message": "Notification marked as read successfully!"
}
```

---

### 9.3 Mark All Notifications as Read

**Endpoint:** `POST /api/v1/dealer/notifications/read-all`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
```

**Request Payload:** None

**Response Payload (200 OK):**
```json
{
  "success": true,
  "message": "All notifications marked as read successfully!",
  "data": {
    "count": 5
  }
}
```

---

## 10. ROLE SWITCHING

### Endpoint
`POST /api/v1/user/switch-role`

### Headers
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

### Request Payload
```json
{
  "role": "BRAND"
}
```

### Response Payload (200 OK)

**Success Response:**
```json
{
  "success": true,
  "message": "Role switched successfully!",
  "data": {
    "role": "BRAND",
    "dashboard_type": "brand"
  }
}
```

**Error Response (403 Forbidden - No Access):**
```json
{
  "success": false,
  "message": "You do not have access to this role",
  "errors": null
}
```

### Business Logic
- Validates user has access to requested role (primary_role OR secondary_role)
- Swaps roles if needed (if requested role is secondary, swap with primary)
- Returns role-specific dashboard config

---

## 11. COMPLETE FLOW EXAMPLE

### Scenario: Dealer completes profile and wins a deal

#### Step 1: Login
```bash
POST /api/v1/auth/otp/verify
{
  "mobile": "9876543210",
  "otp": "123456"
}
```
**Response:** Token received → `1|MWvNaGn83puRCSajczQFh99397eb`

---

#### Step 2: Complete Profile
```bash
POST /api/v1/dealer/profile/complete
Authorization: Bearer 1|MWvNaGn83puRCSajczQFh99397eb
{
  "materials": [
    {
      "material_id": 45,
      "brand_id": 1,
      "agent_type": "AUTHORIZED_AGENT",
      "finish_ids": [1, 5],
      "thickness_ranges": [
        {"unit": "GSM", "min": 200, "max": 400}
      ]
    }
  ],
  "machines_available": [1, 2],
  "capacity_daily": 1000,
  "capacity_monthly": 30000,
  "capacity_unit": "kg",
  "has_warehouse": true,
  "locations": [
    {
      "type": "warehouse",
      "address": "123 Industrial Street",
      "latitude": 28.6139,
      "longitude": 77.2090,
      "city": "Delhi",
      "state": "Delhi"
    }
  ]
}
```
**Response:** Profile completed, status = ACTIVE

---

#### Step 3: Check Dashboard
```bash
GET /api/v1/dealer/dashboard
Authorization: Bearer 1|MWvNaGn83puRCSajczQFh99397eb
```
**Response:** Shows profile completion, opportunities count, etc.

---

#### Step 4: View Opportunities
```bash
GET /api/v1/dealer/opportunities
Authorization: Bearer 1|MWvNaGn83puRCSajczQFh99397eb
```
**Response:** List of matching opportunities with match scores

---

#### Step 5: View Opportunity Details
```bash
GET /api/v1/dealer/opportunity/1
Authorization: Bearer 1|MWvNaGn83puRCSajczQFh99397eb
```
**Response:** Full opportunity details (brand name hidden)

---

#### Step 6: Accept Opportunity
```bash
POST /api/v1/dealer/opportunity/1/accept
Authorization: Bearer 1|MWvNaGn83puRCSajczQFh99397eb
{}
```
**Response:** Accepted (7th acceptance)

---

#### Step 7: Session Locked (Automatic)
When 10th dealer accepts → Session automatically locked
**Notification Received:** "Session for inquiry #1 has been locked"

---

#### Step 8: View Session Details
```bash
GET /api/v1/dealer/session/5
Authorization: Bearer 1|MWvNaGn83puRCSajczQFh99397eb
```
**Response:** Session details with brand name (now visible), expires_in: 1800 seconds

---

#### Step 9: Send Chat Message
```bash
POST /api/v1/dealer/chat/5/message
Authorization: Bearer 1|MWvNaGn83puRCSajczQFh99397eb
{
  "message": "Hello, I can deliver within 15 days at ₹50 per kg"
}
```
**Response:** Message sent successfully

---

#### Step 10: Submit Quotation
```bash
POST /api/v1/dealer/quote/submit/1
Authorization: Bearer 1|MWvNaGn83puRCSajczQFh99397eb
{
  "quoted_price": 500000,
  "currency": "INR",
  "delivery_days": 15,
  "notes": "Price includes GST"
}
```
**Response:** Quotation submitted

---

#### Step 11: Deal Won (Brand Action)
Brand accepts dealer's quotation
**Notification Received:** "Deal Result: You won the deal for inquiry #1"

---

#### Step 12: View History
```bash
GET /api/v1/dealer/history
Authorization: Bearer 1|MWvNaGn83puRCSajczQFh99397eb
```
**Response:** Session history showing DEAL_WON status

---

## ERROR CODES & MESSAGES

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Authentication required or token invalid |
| 403 | Forbidden | Access denied |
| 404 | Not Found | Resource not found |
| 422 | Validation Error | Request validation failed |
| 500 | Internal Server Error | Server error |

### Common Error Responses

**401 Unauthenticated:**
```json
{
  "success": false,
  "message": "Unauthenticated",
  "errors": {
    "token": "Token is missing"
  }
}
```

**403 Forbidden:**
```json
{
  "success": false,
  "message": "You are not part of this session",
  "errors": null
}
```

**404 Not Found:**
```json
{
  "success": false,
  "message": "No query results for model [App\\Models\\Inquiry].",
  "errors": null
}
```

**422 Validation Error:**
```json
{
  "success": false,
  "message": "validation.failed",
  "errors": {
    "materials.0.material_id": [
      "The selected materials.0.material_id is invalid."
    ],
    "capacity_daily": [
      "The capacity daily field is required."
    ]
  }
}
```

---

## AUTHENTICATION

All endpoints (except authentication) require Bearer token authentication:

```
Authorization: Bearer YOUR_TOKEN_HERE
```

### Token Format
- Token is received from `/api/v1/auth/otp/verify` endpoint
- Format: `{id}|{hash}` (e.g., `1|MWvNaGn83puRCSajczQFh99397eb`)
- Include the entire token string in the Authorization header

### Token Expiration
- Tokens do not expire by default (Laravel Sanctum)
- To logout/invalidate: Delete token on client side
- Server-side: Tokens can be revoked programmatically

---

## BASE URL

All endpoints are prefixed with `/api/v1/`

**Example:**
- Base URL: `https://api.example.com`
- Full URL: `https://api.example.com/api/v1/dealer/dashboard`

---

## RATE LIMITING

(To be configured based on server requirements)

---

## SUPPORT

For API support, contact: support@example.com

---

**Last Updated:** January 3, 2026
**API Version:** v1





