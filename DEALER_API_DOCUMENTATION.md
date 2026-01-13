# DEALER API DOCUMENTATION
## B2B Matchmaking Platform - Packaging & Printing Industry

---

## 1️⃣ DEALER PROFILE FINALIZATION

### Endpoint
`POST /api/v1/dealer/profile/complete`

### Method
POST

### Request Payload
```json
{
  "materials_dealt_in": [1, 2, 3],
  "machines_available": [1, 2, 3],
  "grades": ["A", "B", "C"],
  "capacity_daily": 1000.50,
  "capacity_monthly": 30000.00,
  "capacity_unit": "kg",
  "locations": [
    {
      "type": "factory",
      "address": "123 Industrial Street",
      "latitude": 28.6139,
      "longitude": 77.2090,
      "city": "Delhi",
      "state": "Delhi"
    },
    {
      "type": "warehouse",
      "address": "456 Warehouse Road",
      "latitude": 28.7041,
      "longitude": 77.1025,
      "city": "Noida",
      "state": "Uttar Pradesh"
    }
  ]
}
```

### Response Payload
```json
{
  "success": true,
  "message": "dealer.profile_complete",
  "data": {
    "id": 1,
    "user_id": 1,
    "status": "ACTIVE",
    "profile_complete": true,
    "grades": ["A", "B", "C"],
    "capacity_daily": "1000.50",
    "capacity_monthly": "30000.00",
    "capacity_unit": "kg",
    "materials": [...],
    "machines": [...],
    "locations": [...]
  }
}
```

### Business Logic
- Validates all required fields
- Creates or updates dealer record
- Marks profile as COMPLETE
- Sets dealer status to ACTIVE
- Syncs materials and machines (many-to-many)
- Creates/updates factory/warehouse locations
- Dealer becomes eligible for matching after completion

### DB Tables Involved
- `dealers`
- `dealer_materials` (pivot)
- `dealer_machines` (pivot)
- `dealer_locations`

---

## 2️⃣ DEALER DASHBOARD API

### Endpoint
`GET /api/v1/dealer/dashboard`

### Method
GET

### Request Payload
None (authenticated via Bearer token)

### Response Payload
```json
{
  "success": true,
  "message": "dealer.dashboard",
  "data": {
    "profile_completion_percentage": 100,
    "active_opportunities_count": 5,
    "locked_sessions_count": 2,
    "expired_sessions_count": 1,
    "unread_notifications_count": 3
  }
}
```

### Business Logic
- Calculates profile completion based on required fields
- Counts active opportunities (sessions with status SESSION_LOCKED)
- Counts locked sessions where dealer is participant
- Counts expired sessions
- Counts unread notifications

### DB Tables Involved
- `dealers`
- `dealer_acceptances`
- `inquiries`
- `matching_sessions`
- `notifications`

---

## 3️⃣ DEALER OPPORTUNITY FEED (MOST IMPORTANT)

### Endpoint
`GET /api/v1/dealer/opportunities`

### Method
GET

### Request Payload
None (authenticated via Bearer token)

### Response Payload
```json
{
  "success": true,
  "message": "opportunity.list",
  "data": [
    {
      "inquiry_id": 1,
      "title": "Bulk Paper Order",
      "quantity": 5000,
      "quantity_unit": "kg",
      "urgency": "urgent",
      "location": "Mumbai, Maharashtra",
      "time_left_to_accept": 86400,
      "match_score": 95
    }
  ]
}
```

### Business Logic
- Shows inquiries ONLY IF:
  - `inquiry.status = MATCHING`
  - `inquiry.materials ⊆ dealer.materials` (all inquiry materials match dealer materials)
  - `inquiry.required_machines ⊆ dealer.machines` (all inquiry machines match dealer machines)
  - `inquiry.quantity ≤ dealer.capacity_monthly`
  - Dealer has at least one location
- Calculates match_score (0-100) based on:
  - Material match (30 points)
  - Machine match (30 points)
  - Capacity match (20 points)
  - Geography match (20 points)
- Returns opportunities sorted by match_score (descending)

### DB Tables Involved
- `dealers`
- `inquiries`
- `materials`
- `machines`
- `dealer_materials` (pivot)
- `dealer_machines` (pivot)
- `inquiry_materials` (pivot)
- `inquiry_machines` (pivot)

---

## 4️⃣ OPPORTUNITY DETAILS

### Endpoint
`GET /api/v1/dealer/opportunity/{inquiry_id}`

### Method
GET

### Request Payload
None (inquiry_id in URL)

### Response Payload
```json
{
  "success": true,
  "message": "opportunity.details",
  "data": {
    "inquiry_id": 1,
    "title": "Bulk Paper Order",
    "description": "Requiring high-quality paper for packaging",
    "quantity": 5000,
    "quantity_unit": "kg",
    "urgency": "urgent",
    "location": "Mumbai, Maharashtra",
    "specs": {
      "grade": "A",
      "finish": "glossy"
    },
    "attachments": [
      "https://example.com/attachments/file1.pdf"
    ],
    "timeline": {
      "deadline": "2026-01-15T00:00:00Z"
    },
    "brand_name": null,
    "materials": ["Paper", "Cardboard"],
    "machines": ["Printing Machine", "Cutting Machine"]
  }
}
```

### Business Logic
- Returns full inquiry details
- Brand name is hidden (null) before session lock
- Includes materials, machines, specs, attachments
- Provides timeline/deadline information

### DB Tables Involved
- `inquiries`
- `brands`
- `materials`
- `machines`
- `inquiry_materials` (pivot)
- `inquiry_machines` (pivot)

---

## 5️⃣ ACCEPT / DECLINE OPPORTUNITY

### Accept API
**Endpoint:** `POST /api/v1/dealer/opportunity/{id}/accept`

**Method:** POST

**Request Payload:**
```json
{}
```

**Response Payload:**
```json
{
  "success": true,
  "message": "opportunity.accepted",
  "data": {
    "message": "Opportunity accepted successfully",
    "accepted_count": 7
  }
}
```

**Business Logic:**
- Validates inquiry status is MATCHING
- Checks if dealer already accepted/declined (idempotent check)
- Counts current acceptances
- If accepted_count < 10 → allows acceptance
- If accepted_count >= 10 → throws error (auto reject)
- Creates DealerAcceptance record
- If new count = 10 → automatically locks session (triggers lockSession)

**DB Tables Involved:**
- `dealer_acceptances`
- `inquiries`
- `dealers`
- `matching_sessions` (created if count reaches 10)

---

### Decline API
**Endpoint:** `POST /api/v1/dealer/opportunity/{id}/decline`

**Method:** POST

**Request Payload:**
```json
{
  "reason": "Capacity constraints"
}
```

**Response Payload:**
```json
{
  "success": true,
  "message": "opportunity.declined",
  "data": {
    "message": "Opportunity declined successfully"
  }
}
```

**Business Logic:**
- Validates inquiry status is MATCHING
- Creates/updates DealerAcceptance with status DECLINED
- Stores optional decline reason
- Idempotent operation

**DB Tables Involved:**
- `dealer_acceptances`
- `inquiries`

---

## 6️⃣ MATCHING SESSION LOCK

### Endpoint (Internal/Service)
`POST /session/lock` (Internal - triggered automatically)

### Method
POST (Internal Service Method)

### Business Logic
- Triggered automatically when accepted_count = 10
- Updates inquiry.status = SESSION_LOCKED
- Creates MatchingSession record
- Sets locked_at = now()
- Sets expires_at = now() + 30 minutes
- Notifies all 10 accepted dealers via NotificationService
- All dealers can now see brand details

### DB Tables Involved
- `inquiries`
- `matching_sessions`
- `dealer_acceptances`
- `notifications`

---

## 7️⃣ DEALER SESSION VIEW

### Endpoint
`GET /api/v1/dealer/session/{session_id}`

### Method
GET

### Request Payload
None (session_id in URL)

### Response Payload
```json
{
  "success": true,
  "message": "session.details",
  "data": {
    "session_id": 1,
    "expires_in": 1800,
    "brand_details": {
      "name": "ABC Packaging Ltd",
      "company_name": "ABC Packaging Ltd"
    },
    "inquiry": {
      "id": 1,
      "title": "Bulk Paper Order",
      "description": "Requiring high-quality paper",
      "quantity": 5000,
      "quantity_unit": "kg"
    },
    "chat_enabled": true,
    "status": "ACTIVE"
  }
}
```

### Business Logic
- Verifies dealer is participant in session
- Returns session details with brand name (now visible)
- Calculates expires_in (seconds remaining)
- Returns inquiry details
- Chat is always enabled for active sessions

### DB Tables Involved
- `matching_sessions`
- `inquiries`
- `brands`
- `dealer_acceptances`
- `dealers`

---

## 8️⃣ CHAT APIs (SESSION BASED)

### Get Messages
**Endpoint:** `GET /api/v1/dealer/chat/{session_id}`

**Method:** GET

**Request Payload:** None

**Response Payload:**
```json
{
  "success": true,
  "message": "chat.messages",
  "data": [
    {
      "id": 1,
      "sender_id": 1,
      "sender_type": "DEALER",
      "sender_name": "John Doe",
      "message": "Hello, I can deliver within 15 days",
      "attachment_path": null,
      "attachment_url": null,
      "status": "SENT",
      "created_at": "2026-01-03T10:00:00Z",
      "is_own_message": true
    }
  ]
}
```

**Business Logic:**
- Verifies dealer is participant
- Returns all messages for session ordered by created_at
- Includes sender information
- Supports file attachments (attachment_url)

**DB Tables Involved:**
- `chat_messages`
- `matching_sessions`
- `users`
- `dealer_acceptances`

---

### Send Message
**Endpoint:** `POST /api/v1/dealer/chat/{session_id}/message`

**Method:** POST

**Request Payload:**
```json
{
  "message": "I can deliver within 15 days",
  "attachment": null
}
```
OR (for file upload):
```multipart/form-data
message: "Please see attached quote"
attachment: <file>
```

**Response Payload:**
```json
{
  "success": true,
  "message": "chat.message_sent",
  "data": {
    "id": 1,
    "session_id": 1,
    "sender_id": 1,
    "sender_type": "DEALER",
    "message": "I can deliver within 15 days",
    "attachment_path": null,
    "status": "SENT",
    "created_at": "2026-01-03T10:00:00Z"
  }
}
```

**Business Logic:**
- Verifies dealer is participant
- Validates message or attachment present
- Uploads attachment if provided (max 10MB)
- Creates ChatMessage record
- Sets sender_type = DEALER
- Sets status = SENT

**DB Tables Involved:**
- `chat_messages`
- `matching_sessions`
- `dealer_acceptances`

---

## 9️⃣ QUOTATION & DEAL ACTIONS

### Submit Quote
**Endpoint:** `POST /api/v1/dealer/quote/submit/{inquiry_id}`

**Method:** POST

**Request Payload:**
```json
{
  "quoted_price": 500000.00,
  "currency": "INR",
  "delivery_days": 15,
  "notes": "Can provide additional discounts for bulk orders"
}
```

**Response Payload:**
```json
{
  "success": true,
  "message": "quotation.submitted",
  "data": {
    "id": 1,
    "dealer_id": 1,
    "inquiry_id": 1,
    "session_id": 1,
    "quoted_price": "500000.00",
    "currency": "INR",
    "delivery_days": 15,
    "notes": "Can provide additional discounts",
    "deal_status": "PENDING"
  }
}
```

**Business Logic:**
- Verifies dealer is participant in session
- Creates/updates Quotation record
- Links to session_id
- Sets deal_status = PENDING
- Stores quoted price, delivery timeline, notes
- Idempotent (updateOrCreate)

**DB Tables Involved:**
- `quotations`
- `matching_sessions`
- `inquiries`
- `dealer_acceptances`
- `dealers`

---

### Accept Deal (Brand Action - Included for Completeness)
**Endpoint:** `POST /api/v1/dealer/deal/accept` (Typically brand endpoint)

**Method:** POST

**Business Logic:**
- Updates quotation.deal_status = ACCEPTED
- Updates matching_session.status = DEAL_WON
- Updates matching_session.winning_dealer_id
- Updates inquiry.status = DEAL_WON
- Notifies winning dealer

**DB Tables Involved:**
- `quotations`
- `matching_sessions`
- `inquiries`
- `notifications`

---

### Reject Deal (Brand Action - Included for Completeness)
**Endpoint:** `POST /api/v1/dealer/deal/reject` (Typically brand endpoint)

**Method:** POST

**Business Logic:**
- Updates quotation.deal_status = REJECTED
- Dealer remains in session but deal rejected

**DB Tables Involved:**
- `quotations`

---

## 🔟 SESSION END STATES

### Get History
**Endpoint:** `GET /api/v1/dealer/history`

**Method:** GET

**Request Payload:** None

**Response Payload:**
```json
{
  "success": true,
  "message": "session.history",
  "data": [
    {
      "session_id": 1,
      "inquiry_id": 1,
      "inquiry_title": "Bulk Paper Order",
      "brand_name": "ABC Packaging Ltd",
      "status": "DEAL_WON",
      "result": "DEAL_WON",
      "locked_at": "2026-01-03T09:00:00Z",
      "expires_at": "2026-01-03T09:30:00Z"
    }
  ]
}
```

**Business Logic:**
- Returns all sessions where dealer is participant
- Determines result based on:
  - DEAL_WON: session status = DEAL_WON AND winning_dealer_id = dealer.id
  - DEAL_LOST: session status = DEAL_WON AND winning_dealer_id != dealer.id OR status = DEAL_LOST
  - SESSION_EXPIRED: session status = EXPIRED
  - BRAND_CANCELLED: session status = CANCELLED
  - PENDING: session status = ACTIVE
- Ordered by created_at (descending)

**DB Tables Involved:**
- `matching_sessions`
- `inquiries`
- `brands`
- `dealer_acceptances`
- `dealers`

---

## 1️⃣1️⃣ DEALER NOTIFICATIONS

### Get Notifications
**Endpoint:** `GET /api/v1/dealer/notifications?unread_only=false`

**Method:** GET

**Query Parameters:**
- `unread_only` (boolean, optional): Filter unread only

**Request Payload:** None

**Response Payload:**
```json
{
  "success": true,
  "message": "notification.list",
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "type": "SESSION_LOCKED",
      "title": "Session Locked",
      "message": "Session for inquiry #1 has been locked. You can now view brand details and start chatting.",
      "notifiable_type": "App\\Models\\MatchingSession",
      "notifiable_id": 1,
      "read": false,
      "read_at": null,
      "created_at": "2026-01-03T09:00:00Z"
    }
  ]
}
```

**Business Logic:**
- Returns notifications for authenticated user
- Filters by unread_only if specified
- Ordered by created_at (descending)
- Notification types:
  - NEW_OPPORTUNITY: New matching inquiry
  - SESSION_LOCKED: Session locked (10 acceptances)
  - NEW_MESSAGE: New chat message
  - DEAL_RESULT: Deal won/lost
  - SESSION_EXPIRED: Session expired

**DB Tables Involved:**
- `notifications`

---

### Mark as Read
**Endpoint:** `POST /api/v1/dealer/notification/{id}/read`

**Method:** POST

**Request Payload:** None

**Response Payload:**
```json
{
  "success": true,
  "message": "notification.marked_read"
}
```

**Business Logic:**
- Marks specific notification as read
- Sets read = true, read_at = now()

**DB Tables Involved:**
- `notifications`

---

### Mark All as Read
**Endpoint:** `POST /api/v1/dealer/notifications/read-all`

**Method:** POST

**Request Payload:** None

**Response Payload:**
```json
{
  "success": true,
  "message": "notification.all_marked_read",
  "data": {
    "count": 5
  }
}
```

**Business Logic:**
- Marks all unread notifications as read for user
- Returns count of marked notifications

**DB Tables Involved:**
- `notifications`

---

## 1️⃣2️⃣ ROLE SWITCH (IF SECONDARY ROLE EXISTS)

### Endpoint
`POST /api/v1/user/switch-role`

### Method
POST

### Request Payload
```json
{
  "role": "BRAND"
}
```

### Response Payload
```json
{
  "success": true,
  "message": "role.switched",
  "data": {
    "role": "BRAND",
    "dashboard_type": "brand"
  }
}
```

### Business Logic
- Validates user has access to requested role (primary_role OR secondary_role)
- Swaps roles if needed (if requested role is secondary, swap with primary)
- Returns role-specific dashboard config
- Allows switching between approved roles

### DB Tables Involved
- `users`

---

## TECHNICAL NOTES

### Status Enums
- **InquiryStatus:** DRAFT, MATCHING, SESSION_LOCKED, DEAL_WON, DEAL_LOST, SESSION_EXPIRED, BRAND_CANCELLED
- **SessionStatus:** ACTIVE, DEAL_WON, DEAL_LOST, EXPIRED, CANCELLED
- **DealStatus:** PENDING, ACCEPTED, REJECTED
- **DealerStatus:** PENDING, ACTIVE, INACTIVE
- **AcceptanceStatus:** ACCEPTED, DECLINED
- **NotificationType:** NEW_OPPORTUNITY, SESSION_LOCKED, NEW_MESSAGE, DEAL_RESULT, SESSION_EXPIRED

### Transactions
- All accept/lock logic uses database transactions for data consistency
- Accept operation is idempotent (prevents duplicate acceptances)

### HTTP Status Codes
- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error

### Authentication
- All endpoints require Bearer token authentication
- Middleware: `token.exists`, `auth:sanctum`

### Queues & Cron Jobs (Ready for Implementation)
- Session expiration can be handled via cron job
- Notifications can be queued for async processing
- Opportunity matching can be optimized with queue workers

---

## DATABASE SCHEMA SUMMARY

### Core Tables
1. **dealers** - Dealer profiles and status
2. **inquiries** - Brand requirements/inquiries
3. **matching_sessions** - Locked sessions (10 dealers)
4. **dealer_acceptances** - Dealer accept/decline records
5. **quotations** - Price quotes from dealers
6. **chat_messages** - Session-based chat
7. **notifications** - User notifications
8. **dealer_locations** - Factory/warehouse locations

### Pivot Tables
- **dealer_materials** - Many-to-many: dealers ↔ materials
- **dealer_machines** - Many-to-many: dealers ↔ machines
- **inquiry_materials** - Many-to-many: inquiries ↔ materials
- **inquiry_machines** - Many-to-many: inquiries ↔ machines

### Supporting Tables
- **machines** - Available machines/types
- **materials** - Available materials (existing)
- **brands** - Brand information (existing)
- **users** - User accounts (existing)





