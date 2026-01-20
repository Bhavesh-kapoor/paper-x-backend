# Brand Requirement Posting & Converter Response - Complete Flow Documentation

> **📚 [View All Documentation](./DOCS.md)** - Complete documentation index with all links

## Related Documentation

- **[BRAND_REQUIREMENT_API_DOCUMENTATION.md](./BRAND_REQUIREMENT_API_DOCUMENTATION.md)** - Detailed API endpoints and request/response examples
- **[DEALER_API_COMPLETE_DOCUMENTATION.md](./DEALER_API_COMPLETE_DOCUMENTATION.md)** - Complete Dealer API documentation
- **[COMPLETE_API_DOCUMENTATION.md](./COMPLETE_API_DOCUMENTATION.md)** - Complete API reference for all endpoints
- **[COMPLETE_FLOW_DOCUMENTATION.md](./COMPLETE_FLOW_DOCUMENTATION.md)** - Complete flow documentation for all user types
- **[POSTMAN_BRAND_DEALER_IMPORT_INSTRUCTIONS.md](./POSTMAN_BRAND_DEALER_IMPORT_INSTRUCTIONS.md)** - Postman collection import and setup guide

---

## Table of Contents
1. [Overview](#overview)
2. [Brand Registration Flow](#brand-registration-flow)
3. [Brand Requirement Posting Flow](#brand-requirement-posting-flow)
4. [Converter Response Flow](#converter-response-flow)
5. [Dealer Flow (Existing)](#dealer-flow-existing)
6. [Complete Use Cases](#complete-use-cases)
7. [API Routes Reference](#api-routes-reference)
8. [Database Schema](#database-schema)
9. [Error Handling](#error-handling)

---

## Overview

This system enables brands to post packaging/printing requirements, which are then matched with converters based on city and capacity. Converters can view and respond to these requirements, leading to communication and deal closure.

### Key Features:
- **Brand Registration**: Simple registration with company details and brand types
- **Requirement Posting**: 7-step flow to post requirements (Packaging, Printing, etc.)
- **Matchmaking Engine**: Automatically finds 10 best converters based on city and capacity
- **Session Locking**: Immediate session creation when requirement is posted
- **Converter Response**: Converters can view and respond to brand requirements
- **Payment Integration**: Wallet-based payment system for posting fees

---

## Brand Registration Flow

### Step 1: User Authentication
**Endpoint:** `POST /api/v1/auth/otp/request`

**Full URL:** `POST http://localhost:8000/api/v1/auth/otp/request`
```json
{
  "mobile": "9876543210"
}
```

**Response:**
```json
{
  "success": true,
  "message": "OTP sent successfully",
  "data": {
    "type": "otp_sent",
    "message": "OTP sent successfully"
  }
}
```

**Endpoint:** `POST /api/v1/auth/otp/verify`

**Full URL:** `POST http://localhost:8000/api/v1/auth/otp/verify`
```json
{
  "mobile": "9876543210",
  "otp": "123456"
}
```

**Response:**
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
      "primary_role": null,
      "has_secondary_role": false,
      "secondary_role": null
    }
  }
}
```

### Step 2: Complete Brand Profile
**Endpoint:** `POST /api/v1/brand/profile/complete`

**Full URL:** `POST http://localhost:8000/api/v1/brand/profile/complete`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "company_name": "ABC Packaging Brand",
  "brand_name": "ABC Brand",
  "contact_person_name": "John Doe",
  "mobile": "9876543210",
  "email": "john@example.com",
  "gst": "29ABCDE1234F1Z5",
  "city": "Mumbai",
  "location": "Andheri",
  "latitude": 19.1136,
  "longitude": 72.8697,
  "brand_type_ids": [1, 2, 3]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Brand profile completed successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "company_name": "ABC Packaging Brand",
    "brand_name": "ABC Brand",
    "contact_person_name": "John Doe",
    "mobile": "9876543210",
    "email": "john@example.com",
    "gst": "29ABCDE1234F1Z5",
    "city": "Mumbai",
    "location": "Andheri",
    "status": "ACTIVE",
    "profile_complete": true,
    "brand_types": [
      {
        "id": 1,
        "name": "Food & Beverage Brand",
        "category": "fmcg"
      }
    ]
  }
}
```

**Note:** Get brand types from `GET /api/v1/brand-types` before registration.

**Full URL:** `GET http://localhost:8000/api/v1/brand-types`

---

## Brand Requirement Posting Flow

### Overview
Brands post requirements through a 7-step flow. Each requirement costs 50 credits (deducted from wallet).

### Step-by-Step Flow

#### Step 1: What do you need?
**Options:**
- Packaging
- Printing
- Packaging + Printing
- Corporate Gifting / Stationery

#### Step 2: Packaging Type (Conditional)
**Shown only if:** `requirement_type` includes "Packaging"
**Example values:** "Boxes", "Pouches", "Bags", etc.

#### Step 3: Quantity Range
**Format:** Range in pieces
**Examples:** 
- "1000-5000"
- "5000-10000"
- "10000-50000"

#### Step 4: Timeline
**Options:**
- Emergency (Urgent)
- 3–5 Days
- Flexible

#### Step 5: Special Needs
**Type:** Optional text box
**Max length:** 2000 characters
**Example:** "Need eco-friendly packaging with custom printing"

#### Step 6: Upload Design Files (Optional)
**Type:** Array of file paths
**Formats:** Images (jpg, png), Videos, PDFs
**Example:**
```json
[
  "storage/designs/box-design-1.jpg",
  "storage/designs/box-design-2.pdf"
]
```

#### Step 7: Pay Charges
**Amount:** 50 credits (default, configurable)
**Process:** Automatically deducted from wallet
**Validation:** Checks wallet balance before posting

### API Endpoint

**Endpoint:** `POST /api/v1/brand/requirement/post`

**Full URL:** `POST http://localhost:8000/api/v1/brand/requirement/post`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "requirement_type": "Packaging",
  "packaging_type": "Boxes",
  "quantity_range": "1000-5000",
  "timeline": "3–5 Days",
  "special_needs": "Need eco-friendly packaging with custom printing",
  "design_attachments": [
    "storage/designs/box-design-1.jpg",
    "storage/designs/box-design-2.pdf"
  ],
  "title": "Need custom packaging boxes",
  "description": "Looking for custom printed boxes for product launch",
  "urgency": "normal",
  "location": "Mumbai",
  "city": "Mumbai",
  "latitude": 19.0760,
  "longitude": 72.8777
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Requirement posted successfully",
  "data": {
    "inquiry_id": 123,
    "session_id": 456,
    "matched_converters_count": 10,
    "message": "Requirement posted successfully"
  }
}
```

**Response (Insufficient Balance):**
```json
{
  "success": false,
  "message": "Insufficient wallet balance. Please purchase credits first.",
  "errors": null
}
```

### What Happens After Posting?

1. **Wallet Check**: System checks if brand has sufficient credits (50 credits)
2. **Credit Deduction**: Credits are deducted from wallet
3. **Transaction Record**: Wallet transaction is created with:
   - `transaction_id`: Auto-generated (TXN-00001 format)
   - `type`: DEDUCTED
   - `amount`: -50
   - `balance_after`: New wallet balance
   - `transaction_type`: REQUIREMENT_POSTED
   - `reference_id`: Inquiry ID (updated after inquiry creation)
   - `reference_type`: "inquiry"

4. **Inquiry Creation**: Inquiry record is created with status `MATCHING`
5. **Matchmaking**: System finds 10 best converters based on:
   - City match (40 points)
   - Capacity match (30 points)
   - Converter type match (20 points)
   - Profile completeness (10 points)

6. **Session Creation**: Matching session is created and locked immediately
   - `status`: ACTIVE
   - `locked_at`: Current timestamp
   - `expires_at`: 24 hours from now

7. **Notifications**: All matched converters receive notifications

---

## Converter Response Flow

### Step 1: View Brand Requirements
**Endpoint:** `GET /api/v1/converter/requirements`

**Full URL:** `GET http://localhost:8000/api/v1/converter/requirements`

**Query Parameters:**
- `city` (optional): Filter by city
- `requirement_type` (optional): Filter by requirement type
- `urgency` (optional): Filter by urgency (normal, urgent)
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number (default: 1)

**Response:**
```json
{
  "success": true,
  "message": "Requirements retrieved successfully",
  "data": {
    "requirements": [
      {
        "id": 123,
        "title": "Need custom packaging boxes",
        "description": "Looking for custom printed boxes",
        "requirement_type": "Packaging",
        "packaging_type": "Boxes",
        "quantity_range": "1000-5000",
        "timeline": "3–5 Days",
        "special_needs": "Need eco-friendly packaging",
        "urgency": "normal",
        "location": "Mumbai",
        "design_attachments": ["path/to/file1.jpg"],
        "has_active_session": true,
        "session_id": 456,
        "created_at": "2026-01-17T10:00:00Z",
        "brand_name": null,
        "brand_company_name": null
      }
    ],
    "pagination": {
      "current_page": 1,
      "total": 10,
      "per_page": 15,
      "last_page": 1
    }
  }
}
```

**Note:** Brand details are hidden until session is locked (privacy feature).

### Step 2: Respond to Requirement
**Endpoint:** `POST /api/v1/converter/requirement/{inquiry_id}/respond`

**Full URL:** `POST http://localhost:8000/api/v1/converter/requirement/{inquiry_id}/respond`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "quantity_offered": 5000,
  "quantity_unit": "pieces",
  "quoted_price": 50000,
  "price_unit": "per_piece",
  "price_status": "Fixed",
  "additional_details": "Can deliver within 3 days with eco-friendly packaging"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Response submitted successfully",
  "data": {
    "response_id": 789,
    "message": "Response submitted successfully"
  }
}
```

**Validation:**
- Converter can only respond once per requirement
- Requirement must be in `MATCHING` status
- Requirement must be posted by a brand (`poster_type = 'brand'`)

---

## Brand Dashboard & Inquiry Management

### Get Dashboard
**Endpoint:** `GET /api/v1/brand/dashboard`

**Full URL:** `GET http://localhost:8000/api/v1/brand/dashboard`

**Response:**
```json
{
  "success": true,
  "message": "Dashboard data retrieved successfully",
  "data": {
    "profile_completion_percentage": 100,
    "my_inquiries_count": 5,
    "active_sessions_count": 3,
    "unread_messages_count": 12,
    "unread_notifications_count": 8
  }
}
```

### Get My Inquiries
**Endpoint:** `GET /api/v1/brand/inquiries`

**Full URL:** `GET http://localhost:8000/api/v1/brand/inquiries`

**Query Parameters:**
- `status` (optional): Filter by status (DRAFT, MATCHING, SESSION_LOCKED, etc.)
- `urgency` (optional): Filter by urgency (normal, urgent)
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number (default: 1)

**Response:**
```json
{
  "success": true,
  "message": "Inquiries retrieved successfully",
  "data": {
    "inquiries": [
      {
        "id": 123,
        "title": "Need custom packaging boxes",
        "description": "Looking for custom printed boxes",
        "requirement_type": "Packaging",
        "packaging_type": "Boxes",
        "quantity_range": "1000-5000",
        "timeline": "3–5 Days",
        "special_needs": "Need eco-friendly packaging",
        "status": "MATCHING",
        "urgency": "normal",
        "location": "Mumbai",
        "design_attachments": ["path/to/file1.jpg"],
        "responses_count": 5,
        "has_active_session": true,
        "session_id": 456,
        "created_at": "2026-01-17T10:00:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total": 10,
      "per_page": 15,
      "last_page": 1
    }
  }
}
```

### Get Messages (for Active Inquiries)
**Endpoint:** `GET /api/v1/brand/messages/{session_id}`

**Full URL:** `GET http://localhost:8000/api/v1/brand/messages/{session_id}`

**Response:**
Returns messages for the active session (uses ChatService)

---

## Dealer Flow (Existing)

### Complete Dealer Profile
**Endpoint:** `POST /api/v1/dealer/profile/complete`

**Full URL:** `POST http://localhost:8000/api/v1/dealer/profile/complete`

**Request Body:**
```json
{
  "materials_dealt_in": [1, 2, 3],
  "machines_available": [1, 2],
  "grades": ["A", "B"],
  "capacity_daily": 1000.50,
  "capacity_monthly": 30000.00,
  "capacity_unit": "kg",
  "has_warehouse": true,
  "locations": [
    {
      "type": "factory",
      "address": "123 Industrial Street",
      "latitude": 28.6139,
      "longitude": 77.2090,
      "city": "Delhi",
      "state": "Delhi"
    }
  ],
  "materials": [
    {
      "material_id": 1,
      "mill_id": 1,
      "agent_type": "direct",
      "finish_ids": [1, 2],
      "thickness_ranges": [
        {
          "unit": "GSM",
          "min": 100,
          "max": 300
        }
      ]
    }
  ]
}
```

### Get Opportunities
**Endpoint:** `GET /api/v1/dealer/opportunities`

**Full URL:** `GET http://localhost:8000/api/v1/dealer/opportunities`

**Response:**
Returns matched opportunities based on dealer profile (materials, machines, capacity)

### Accept Opportunity
**Endpoint:** `POST /api/v1/dealer/opportunity/{inquiry_id}/accept`

**Full URL:** `POST http://localhost:8000/api/v1/dealer/opportunity/{inquiry_id}/accept`

**Flow:**
1. Dealer views opportunities
2. Dealer accepts opportunity
3. When 10 dealers accept, session locks automatically
4. Brand details become visible
5. Communication starts

---

## Complete Use Cases

### Use Case 1: Brand Posts Packaging Requirement

**Actor:** Brand User

**Preconditions:**
- User is authenticated
- Brand profile is complete
- Wallet has at least 50 credits

**Flow:**
1. Brand logs in via OTP
2. Brand navigates to "Post New Requirement"
3. Brand fills 7-step form:
   - Selects "Packaging"
   - Selects "Boxes" as packaging type
   - Enters quantity range "1000-5000"
   - Selects timeline "3–5 Days"
   - Adds special needs: "Eco-friendly packaging"
   - Uploads design files (optional)
4. System validates wallet balance
5. System deducts 50 credits
6. System creates inquiry with status `MATCHING`
7. System finds 10 best converters (city + capacity match)
8. System creates matching session (locked immediately)
9. System notifies all matched converters
10. Brand sees inquiry in "My Posted Inquiries"

**Postconditions:**
- Inquiry is created and visible to matched converters
- Session is active and locked
- Brand can view responses and communicate

---

### Use Case 2: Converter Views and Responds to Requirement

**Actor:** Converter User

**Preconditions:**
- User is authenticated
- Converter profile is complete
- Converter is in same city as brand (or matching engine finds them)

**Flow:**
1. Converter logs in via OTP
2. Converter navigates to "Requirements" (brand requirements)
3. Converter sees list of requirements matching their city
4. Converter clicks on a requirement to view details
5. Converter reviews:
   - Requirement type
   - Quantity range
   - Timeline
   - Special needs
   - Design attachments (if any)
6. Converter clicks "Respond"
7. Converter fills response form:
   - Quantity offered
   - Quoted price
   - Additional details
8. Converter submits response
9. System creates response record
10. Brand receives notification
11. Brand can view response and start communication

**Postconditions:**
- Response is created
- Brand is notified
- Communication can begin via chat

---

### Use Case 3: Brand Views Responses and Communicates

**Actor:** Brand User

**Preconditions:**
- Brand has posted a requirement
- At least one converter has responded

**Flow:**
1. Brand navigates to "My Posted Inquiries"
2. Brand sees inquiry with responses count
3. Brand clicks on inquiry
4. Brand views all converter responses
5. Brand can:
   - View converter details (after session lock)
   - Send messages to converters
   - Compare quotes
   - Select winning converter

**Postconditions:**
- Brand can communicate with converters
- Brand can make informed decision

---

## API Routes Reference

> **Base URL:** `http://localhost:8000/api/v1` (Development)  
> **Production URL:** `https://your-domain.com/api/v1`

### Authentication Routes

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/auth/otp/request` | Request OTP |
| `POST` | `/api/v1/auth/otp/verify` | Verify OTP & Login |

**Full URLs:**
- `POST http://localhost:8000/api/v1/auth/otp/request`
- `POST http://localhost:8000/api/v1/auth/otp/verify`

### Brand Routes

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/brand/profile/complete` | Complete brand profile |
| `GET` | `/api/v1/brand/dashboard` | Get brand dashboard |
| `POST` | `/api/v1/brand/requirement/post` | Post new requirement |
| `GET` | `/api/v1/brand/inquiries` | Get my inquiries |
| `GET` | `/api/v1/brand/messages/{session_id}` | Get messages for session |

**Full URLs:**
- `POST http://localhost:8000/api/v1/brand/profile/complete`
- `GET http://localhost:8000/api/v1/brand/dashboard`
- `POST http://localhost:8000/api/v1/brand/requirement/post`
- `GET http://localhost:8000/api/v1/brand/inquiries`
- `GET http://localhost:8000/api/v1/brand/messages/{session_id}`

### Converter Routes

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/converter/profile/complete` | Complete converter profile |
| `GET` | `/api/v1/converter/dashboard` | Get converter dashboard |
| `GET` | `/api/v1/converter/requirements` | Get brand requirements |
| `POST` | `/api/v1/converter/requirement/{inquiry_id}/respond` | Respond to requirement |

**Full URLs:**
- `POST http://localhost:8000/api/v1/converter/profile/complete`
- `GET http://localhost:8000/api/v1/converter/dashboard`
- `GET http://localhost:8000/api/v1/converter/requirements`
- `POST http://localhost:8000/api/v1/converter/requirement/{inquiry_id}/respond`

### Dealer Routes (Existing)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/dealer/profile/complete` | Complete dealer profile |
| `GET` | `/api/v1/dealer/dashboard` | Get dealer dashboard |
| `POST` | `/api/v1/dealer/requirement/post` | Post requirement |
| `GET` | `/api/v1/dealer/requirements` | Get requirements |
| `GET` | `/api/v1/dealer/opportunities` | Get matched opportunities |
| `GET` | `/api/v1/dealer/opportunity/{inquiry_id}` | Get opportunity details |
| `POST` | `/api/v1/dealer/opportunity/{id}/accept` | Accept opportunity |
| `POST` | `/api/v1/dealer/opportunity/{id}/decline` | Decline opportunity |
| `GET` | `/api/v1/dealer/session/{session_id}` | Get session details |
| `GET` | `/api/v1/dealer/history` | Get session history |
| `GET` | `/api/v1/dealer/chat/{session_id}` | Get chat messages |
| `POST` | `/api/v1/dealer/chat/{session_id}/message` | Send message |
| `POST` | `/api/v1/dealer/quote/submit/{inquiry_id}` | Submit quotation |
| `GET` | `/api/v1/dealer/notifications` | Get notifications |

**Full URLs:**
- `POST http://localhost:8000/api/v1/dealer/profile/complete`
- `GET http://localhost:8000/api/v1/dealer/dashboard`
- `POST http://localhost:8000/api/v1/dealer/requirement/post`
- `GET http://localhost:8000/api/v1/dealer/requirements`
- `GET http://localhost:8000/api/v1/dealer/opportunities`
- `GET http://localhost:8000/api/v1/dealer/opportunity/{inquiry_id}`
- `POST http://localhost:8000/api/v1/dealer/opportunity/{id}/accept`
- `POST http://localhost:8000/api/v1/dealer/opportunity/{id}/decline`
- `GET http://localhost:8000/api/v1/dealer/session/{session_id}`
- `GET http://localhost:8000/api/v1/dealer/history`
- `GET http://localhost:8000/api/v1/dealer/chat/{session_id}`
- `POST http://localhost:8000/api/v1/dealer/chat/{session_id}/message`
- `POST http://localhost:8000/api/v1/dealer/quote/submit/{inquiry_id}`
- `GET http://localhost:8000/api/v1/dealer/notifications`

### Reference Data Routes

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/brand-types` | Get all brand types |
| `GET` | `/api/v1/materials` | Get all materials |
| `GET` | `/api/v1/machines` | Get all machines |
| `GET` | `/api/v1/material-finishes` | Get material finishes |
| `GET` | `/api/v1/material-mills` | Get material mills |
| `GET` | `/api/v1/material-thickness-types` | Get material thickness types |

**Full URLs:**
- `GET http://localhost:8000/api/v1/brand-types`
- `GET http://localhost:8000/api/v1/materials`
- `GET http://localhost:8000/api/v1/machines`
- `GET http://localhost:8000/api/v1/material-finishes`
- `GET http://localhost:8000/api/v1/material-mills`
- `GET http://localhost:8000/api/v1/material-thickness-types`

### Wallet Routes

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/wallet` | Get wallet balance |
| `GET` | `/api/v1/wallet/credit-packs` | Get credit packs |
| `POST` | `/api/v1/wallet/calculate` | Calculate custom credits |
| `POST` | `/api/v1/wallet/purchase` | Purchase credits |
| `POST` | `/api/v1/wallet/add` | Add credits (admin/system) |
| `GET` | `/api/v1/wallet/transactions` | Get transaction history |
| `POST` | `/api/v1/wallet/deduct` | Deduct credits |

**Full URLs:**
- `GET http://localhost:8000/api/v1/wallet`
- `GET http://localhost:8000/api/v1/wallet/credit-packs`
- `POST http://localhost:8000/api/v1/wallet/calculate`
- `POST http://localhost:8000/api/v1/wallet/purchase`
- `POST http://localhost:8000/api/v1/wallet/add`
- `GET http://localhost:8000/api/v1/wallet/transactions`
- `POST http://localhost:8000/api/v1/wallet/deduct`

### Common Routes

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/dashboard` | Unified dashboard for all roles |
| `GET` | `/api/v1/user/profile` | Get user profile |
| `POST` | `/api/v1/user/profile` | Update user profile |
| `POST` | `/api/v1/user/switch-role` | Switch user role |

---

## Database Schema

### Inquiries Table (Enhanced)
```sql
- id
- brand_id (nullable, for backward compatibility)
- poster_id (brand/dealer/converter/machine_dealer ID)
- poster_type (brand, dealer, converter, machine_dealer)
- title
- description
- status (DRAFT, MATCHING, SESSION_LOCKED, etc.)
- urgency (normal, urgent)
- inquiry_type (material, machine, job)
- intent (buy, sell)
- requirement_type (Packaging, Printing, Packaging + Printing, Corporate Gifting / Stationery)
- packaging_type (conditional)
- quantity
- quantity_unit
- quantity_range (e.g., "1000-5000")
- timeline (Emergency (Urgent), 3–5 Days, Flexible)
- special_needs (text)
- design_attachments (JSON array)
- location
- latitude
- longitude
- posting_fee_paid (boolean)
- posting_fee_amount (decimal)
- created_at
- updated_at
```

### Matching Sessions Table
```sql
- id
- inquiry_id (unique)
- status (ACTIVE, DEAL_WON, DEAL_LOST, EXPIRED, CANCELLED)
- locked_at
- expires_at
- winning_dealer_id (nullable)
- discovery_start
- discovery_end
- active_session_start
- republish_count
- created_at
- updated_at
```

### Responses Table
```sql
- id
- inquiry_id
- responder_id (user_id)
- responder_type (dealer, converter, machine_dealer)
- quantity_offered
- quantity_unit
- quoted_price
- price_unit
- price_status
- additional_details
- status (PENDING, SHORTLISTED, SELECTED, REJECTED, WITHDRAWN)
- session_id (nullable)
- created_at
- updated_at
```

### Wallet Transactions Table
```sql
- id
- wallet_id
- transaction_id (unique, TXN-00001 format)
- type (ADDED, DEDUCTED)
- amount (decimal, negative for deducted)
- balance_after (decimal)
- description
- transaction_type (PURCHASE, REQUIREMENT_POSTED, etc.)
- reference_id (nullable, inquiry_id, session_id, etc.)
- reference_type (nullable, inquiry, session, etc.)
- metadata (JSON)
- created_at
- updated_at
```

---

## Error Handling

### Common Errors and Solutions

#### 1. Insufficient Wallet Balance
**Error:**
```json
{
  "success": false,
  "message": "Insufficient wallet balance. Please purchase credits first."
}
```
**Solution:** Purchase credits via `POST /api/v1/wallet/purchase`

#### 2. Profile Not Complete
**Error:**
```json
{
  "success": false,
  "message": "Brand profile must be complete and active to post requirements"
}
```
**Solution:** Complete brand profile via `POST /api/v1/brand/profile/complete`

#### 3. Already Responded
**Error:**
```json
{
  "success": false,
  "message": "You have already responded to this requirement"
}
```
**Solution:** Converter can only respond once per requirement

#### 4. Requirement Not Available
**Error:**
```json
{
  "success": false,
  "message": "Inquiry is not available for response"
}
```
**Solution:** Requirement status must be `MATCHING`

#### 5. Invalid Requirement Type
**Error:**
```json
{
  "success": false,
  "message": "This inquiry is not a brand requirement"
}
```
**Solution:** Only brand requirements can be responded to by converters

---

## Matchmaking Algorithm

### Scoring System (100 points total)

1. **City Match (40 points)**
   - Exact city match: 40 points
   - Same state: 20 points
   - Different state: 0 points

2. **Capacity Match (30 points)**
   - Converter capacity >= inquiry max quantity: 30 points
   - Converter capacity >= 50% of inquiry quantity: 15 points
   - Converter capacity < 50%: 0 points

3. **Converter Type Match (20 points)**
   - Based on requirement type and converter capabilities
   - Default: 20 points (can be enhanced)

4. **Profile Completeness (10 points)**
   - Profile complete: 10 points
   - Profile incomplete: 0 points

### Selection Process
1. Get all active converters with complete profiles
2. Filter by city (if brand city is available)
3. Score each converter
4. Sort by score (descending)
5. Select top 10 converters
6. If less than 10 in same city, expand search to other cities

---

## Session Management

### Session Lifecycle

1. **Creation**: Session created immediately when requirement is posted
2. **Status**: ACTIVE
3. **Duration**: 24 hours (configurable)
4. **Lock**: Session is locked immediately (unlike dealer flow)
5. **Expiration**: Session expires after 24 hours
6. **Communication**: Brand and converters can chat during active session

### Session States

- **ACTIVE**: Session is active, communication enabled
- **DEAL_WON**: Brand selected a converter
- **DEAL_LOST**: Brand selected another converter
- **EXPIRED**: Session expired (24 hours passed)
- **CANCELLED**: Brand cancelled the requirement

---

## Payment Flow

### Posting Fee Structure

- **Default Fee**: 50 credits per requirement
- **Configurable**: Can be changed in code/config
- **Validation**: System checks balance before posting
- **Transaction**: Auto-generated transaction_id (TXN-00001 format)

### Wallet Transaction Details

When a requirement is posted:
```json
{
  "transaction_id": "TXN-00001",
  "type": "DEDUCTED",
  "amount": -50,
  "balance_after": 450,
  "description": "Post requirement fee",
  "transaction_type": "REQUIREMENT_POSTED",
  "reference_id": 123,
  "reference_type": "inquiry"
}
```

---

## Brand Types Reference

### FMCG & Consumer Goods
- Food & Beverage Brand
- Packaged Food Brand
- Snacks & Confectionery Brand
- Dairy Brand
- Frozen Food Brand
- Beverage Brand (Juices, Water, Energy Drinks)
- Alcoholic Beverage Brand
- Tobacco / Nicotine Brand

### Pharma, Health & Wellness
- Pharmaceutical Brand
- OTC / Nutraceutical Brand
- Ayurvedic / Herbal Brand
- Medical Device Brand
- Diagnostic Kit Brand
- Health Supplement Brand

### Beauty, Personal Care & Luxury
- Cosmetics Brand
- Skincare Brand
- Haircare Brand
- Perfume & Fragrance Brand
- Luxury Beauty Brand
- Grooming Brand

### Apparel, Fashion & Accessories
- Apparel / Clothing Brand
- Garment Export Brand
- Footwear Brand
- Fashion Accessories Brand
- Jewellery Brand
- Watch Brand
- Eyewear Brand

### Electronics & Durables
- Consumer Electronics Brand
- Mobile & Accessories Brand
- Electrical Appliances Brand
- Home Appliances Brand
- IT Hardware Brand
- Industrial Electronics Brand

### E-commerce & D2C Brands
- D2C Consumer Brand
- E-commerce Seller Brand
- Subscription Box Brand
- Marketplace Private Label
- Quick Commerce Brand

### Food Service & Hospitality
- Bakery Brand
- Patisserie / Cake Brand
- QSR / Fast Food Brand
- Restaurant Chain
- Cloud Kitchen
- Café Brand
- Catering Brand

### Corporate, B2B & Institutional
- Corporate Gifting Brand
- Promotional Merchandise Brand
- Office Supplies Brand
- Industrial Goods Brand
- Chemical Brand
- Paints & Coatings Brand

### Education, Publishing & Stationery
- School / Education Brand
- Publishing House
- Book Publisher
- EdTech Brand
- Stationery Brand
- Notebook / Diary Brand

### Agriculture & Rural Products
- Agro Products Brand
- Seeds Brand
- Fertilizer Brand
- Organic / Natural Products Brand
- Tea / Coffee Brand
- Spices Brand

### Home, Lifestyle & Decor
- Home Furnishing Brand
- Furniture Brand
- Home Décor Brand
- Kitchenware Brand
- Lighting Brand

### Events, Gifts & Promotions
- Event Management Brand
- Festive Gifting Brand
- Wedding Gifting Brand
- Luxury Hampers Brand
- Promotional Campaign Brand

---

## Testing Checklist

### Brand Flow
- [ ] User can register and complete brand profile
- [ ] Brand can view dashboard
- [ ] Brand can post requirement (with sufficient balance)
- [ ] System deducts credits correctly
- [ ] System creates inquiry with correct status
- [ ] System finds and matches converters
- [ ] System creates session
- [ ] Brand can view posted inquiries
- [ ] Brand can view responses
- [ ] Brand can send messages

### Converter Flow
- [ ] Converter can view brand requirements
- [ ] Requirements are filtered by city
- [ ] Converter can respond to requirement
- [ ] Converter cannot respond twice
- [ ] Brand details are hidden until session lock

### Payment Flow
- [ ] System checks wallet balance before posting
- [ ] Credits are deducted correctly
- [ ] Transaction is created with all required fields
- [ ] Transaction ID is auto-generated
- [ ] Balance is updated correctly

### Error Handling
- [ ] Insufficient balance error is shown
- [ ] Profile incomplete error is shown
- [ ] Already responded error is shown
- [ ] Invalid requirement type error is shown

---

## Postman Collection

A complete Postman collection is available:
**File:** `Paper_X_Brand_Dealer_API_Collection.postman_collection.json`

### Import Instructions

For detailed Postman import and setup instructions, see: **[POSTMAN_BRAND_DEALER_IMPORT_INSTRUCTIONS.md](./POSTMAN_BRAND_DEALER_IMPORT_INSTRUCTIONS.md)**

Quick steps:
1. Open Postman
2. Click **Import**
3. Select `Paper_X_Brand_Dealer_API_Collection.postman_collection.json`
4. Set `base_url` variable (default: `http://localhost:8000`)
5. Use "Verify OTP & Login" to get token (auto-saved)

---

## Support & Contact

> **📚 [View All Documentation](./DOCS.md)** - Complete documentation index with all links

For API support or questions, refer to:
- **[BRAND_REQUIREMENT_API_DOCUMENTATION.md](./BRAND_REQUIREMENT_API_DOCUMENTATION.md)** - Detailed API docs
- **[DEALER_API_COMPLETE_DOCUMENTATION.md](./DEALER_API_COMPLETE_DOCUMENTATION.md)** - Dealer API docs
- **[POSTMAN_BRAND_DEALER_IMPORT_INSTRUCTIONS.md](./POSTMAN_BRAND_DEALER_IMPORT_INSTRUCTIONS.md)** - Postman setup
- **[COMPLETE_API_DOCUMENTATION.md](./COMPLETE_API_DOCUMENTATION.md)** - Complete API reference
- **[COMPLETE_FLOW_DOCUMENTATION.md](./COMPLETE_FLOW_DOCUMENTATION.md)** - Complete flow documentation

---

**Last Updated:** January 17, 2026  
**API Version:** v1

