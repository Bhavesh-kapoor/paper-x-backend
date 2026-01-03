# COMPLETE FLOW DOCUMENTATION
## Step-by-Step API Flow for All Roles

---

## 📋 TABLE OF CONTENTS

1. [Authentication Flow](#1-authentication-flow)
2. [Dealer Flow](#2-dealer-flow)
3. [Machine Dealer Flow](#3-machine-dealer-flow)
4. [Converter Flow](#4-converter-flow)
5. [Brand Flow](#5-brand-flow)
6. [Inquiry & Response Flow](#6-inquiry--response-flow)
7. [Session Flow](#7-session-flow)
8. [Complete Example Scenarios](#8-complete-example-scenarios)

---

## 1️⃣ AUTHENTICATION FLOW

### Step 1: Request OTP
```http
POST /api/v1/auth/otp/request
Content-Type: application/json

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
    "type": "otp_sent"
  }
}
```

### Step 2: Verify OTP & Login
```http
POST /api/v1/auth/otp/verify
Content-Type: application/json

{
  "mobile": "9876543210",
  "otp": "123456"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "type": "login_success",
    "token": "1|xxxxxxxxxxxx",
    "user": {
      "id": 1,
      "name": "John Doe",
      "mobile": "9876543210",
      "primary_role": null
    }
  }
}
```

**Save the token for all subsequent requests:**
```
Authorization: Bearer 1|xxxxxxxxxxxx
```

---

## 2️⃣ DEALER FLOW

### Step 1: Complete Dealer Profile
```http
POST /api/v1/dealer/profile/complete
Authorization: Bearer {token}
Content-Type: application/json

{
  "materials_dealt_in": [1, 2, 3],
  "machines_available": [1, 2],
  "grades": ["A", "B"],
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
      "state": "Delhi",
      "has_warehouse": true
    }
  ],
  "material_details": [
    {
      "material_id": 1,
      "mill_ids": [1, 2],
      "agent_type": "authorized_agent",
      "finish_ids": [1, 2, 3],
      "thickness_min": 200,
      "thickness_max": 400,
      "thickness_unit": "GSM"
    }
  ]
}
```

### Step 2: Get Dashboard
```http
GET /api/v1/dealer/dashboard
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "profile_completion_percentage": 100,
    "active_opportunities_count": 5,
    "locked_sessions_count": 2,
    "expired_sessions_count": 1,
    "unread_notifications_count": 3
  }
}
```

### Step 3: Browse Opportunities
```http
GET /api/v1/dealer/opportunities?urgency=urgent&page=1
Authorization: Bearer {token}
```

### Step 4: Accept Opportunity
```http
POST /api/v1/dealer/opportunity/1/accept
Authorization: Bearer {token}
```

### Step 5: View Session & Chat
```http
GET /api/v1/dealer/session/1
Authorization: Bearer {token}
```

### Step 6: Submit Quotation
```http
POST /api/v1/dealer/quote/submit/1
Authorization: Bearer {token}
Content-Type: application/json

{
  "quoted_price": 50000,
  "currency": "INR",
  "delivery_days": 5,
  "notes": "Can deliver within 5 days"
}
```

---

## 3️⃣ MACHINE DEALER FLOW

### Step 1: Complete Machine Dealer Profile
```http
POST /api/v1/machine-dealer/profile/complete
Authorization: Bearer {token}
Content-Type: application/json

{
  "company_name": "ABC Machine Dealers",
  "gst": "29ABCDE1234F1Z5",
  "contact_person_name": "John Doe",
  "mobile": "9876543210",
  "email": "john@example.com",
  "city": "Mumbai",
  "location": "Andheri",
  "latitude": 19.1136,
  "longitude": 72.8697
}
```

### Step 2: Get Dashboard
```http
GET /api/v1/machine-dealer/dashboard
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "profile_completion_percentage": 100,
    "active_listings_count": 5,
    "active_requirements_count": 3,
    "responses_received_count": 8
  }
}
```

### Step 3: Post Machine for Sale
```http
POST /api/v1/machine-dealer/machine/post
Authorization: Bearer {token}
Content-Type: application/json

{
  "machine_id": 1,
  "machine_brand_id": 1,
  "machine_type": "Automatic Folder Gluer",
  "condition": "Working Condition",
  "intent": "sell",
  "urgency": "normal",
  "description": "Well maintained machine",
  "price": 500000,
  "currency": "INR",
  "location": "Mumbai",
  "latitude": 19.1136,
  "longitude": 72.8697
}
```

**Response:**
```json
{
  "success": true,
  "message": "Machine listing posted successfully",
  "data": {
    "id": 1,
    "machine_listing_id": 1,
    "inquiry_id": 1,
    "status": "MATCHING"
  }
}
```

### Step 4: View My Active Listings
```http
GET /api/v1/machine-dealer/listings
Authorization: Bearer {token}
```

### Step 5: Browse Machine Requirements
```http
GET /api/v1/machine-dealer/requirements?intent=buy&urgency=urgent
Authorization: Bearer {token}
```

### Step 6: Respond to Requirement
```http
POST /api/v1/machine-dealer/requirement/1/respond
Authorization: Bearer {token}
Content-Type: application/json

{
  "quoted_price": 450000,
  "currency": "INR",
  "additional_details": "Can deliver within 7 days"
}
```

---

## 4️⃣ CONVERTER FLOW

### Step 1: Complete Converter Profile
```http
POST /api/v1/converter/profile/complete
Authorization: Bearer {token}
Content-Type: application/json

{
  "converter_type_ids": [1, 2, 3],
  "finished_product_ids": [1, 2, 3],
  "machine_ids": [1, 2, 3],
  "scrap_type_ids": [1, 2],
  "raw_material_ids": [1, 2, 3],
  "capacity_daily": 1000,
  "capacity_monthly": 30000,
  "capacity_unit": "pieces",
  "factory_address": "123 Factory Street",
  "factory_city": "Mumbai",
  "factory_state": "Maharashtra",
  "factory_latitude": 19.1136,
  "factory_longitude": 72.8697
}
```

### Step 2: Get Dashboard
```http
GET /api/v1/converter/dashboard
Authorization: Bearer {token}
```

### Step 3: Post Material Inquiry (Buy)
```http
POST /api/v1/converter/inquiry/post
Authorization: Bearer {token}
Content-Type: application/json

{
  "inquiry_type": "material",
  "intent": "buy",
  "title": "Need Duplex Board 350 GSM",
  "material_ids": [1],
  "thickness": 350,
  "thickness_unit": "GSM",
  "size": "28x40",
  "quantity": 2000,
  "quantity_unit": "sheets",
  "urgency": "urgent",
  "location": "Mumbai",
  "latitude": 19.1136,
  "longitude": 72.8697,
  "description": "Need urgently for production"
}
```

### Step 4: Post Machine Inquiry (Buy)
```http
POST /api/v1/converter/inquiry/post
Authorization: Bearer {token}
Content-Type: application/json

{
  "inquiry_type": "machine",
  "intent": "buy",
  "title": "Need Automatic Folder Gluer",
  "machine_ids": [1],
  "machine_condition": "Working Condition",
  "urgency": "normal",
  "location": "Mumbai",
  "latitude": 19.1136,
  "longitude": 72.8697
}
```

### Step 5: Post Job Outsourcing
```http
POST /api/v1/converter/inquiry/post
Authorization: Bearer {token}
Content-Type: application/json

{
  "inquiry_type": "job",
  "title": "Need 10,000 Rigid Boxes",
  "job_type": "Rigid Boxes",
  "quantity": 10000,
  "quantity_unit": "pieces",
  "timeline_days": 5,
  "urgency": "normal",
  "location": "Mumbai",
  "latitude": 19.1136,
  "longitude": 72.8697
}
```

### Step 6: Browse Inquiries
```http
GET /api/v1/converter/inquiries?inquiry_type=material&intent=sell
Authorization: Bearer {token}
```

### Step 7: Respond to Inquiry
```http
POST /api/v1/converter/inquiry/1/respond
Authorization: Bearer {token}
Content-Type: application/json

{
  "quantity_offered": 2000,
  "quantity_unit": "sheets",
  "quoted_price": 50,
  "price_unit": "per_sheet",
  "price_status": "agreed",
  "additional_details": "Can deliver within 2 days"
}
```

---

## 5️⃣ BRAND FLOW

### Step 1: Complete Brand Profile
```http
POST /api/v1/brand/profile/complete
Authorization: Bearer {token}
Content-Type: application/json

{
  "company_name": "ABC Brands",
  "brand_name": "ABC",
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

### Step 2: Get Dashboard
```http
GET /api/v1/brand/dashboard
Authorization: Bearer {token}
```

### Step 3: Post Packaging Requirement
```http
POST /api/v1/brand/requirement/post
Authorization: Bearer {token}
Content-Type: application/json

{
  "need_type": "Packaging + Printing",
  "packaging_type": "Rigid Boxes",
  "quantity": 1500,
  "quantity_unit": "pieces",
  "timeline_days": 3,
  "urgency": "urgent",
  "special_needs": "Premium finish required",
  "finished_product_ids": [1]
}
```

### Step 4: View My Inquiries
```http
GET /api/v1/brand/inquiries
Authorization: Bearer {token}
```

### Step 5: View Responses
```http
GET /api/v1/brand/inquiry/1/responses
Authorization: Bearer {token}
```

### Step 6: Shortlist Responder
```http
POST /api/v1/brand/response/1/shortlist
Authorization: Bearer {token}
```

---

## 6️⃣ INQUIRY & RESPONSE FLOW

### Flow Diagram:
```
1. Poster creates inquiry → Status: MATCHING
2. System finds matching responders
3. Responders submit responses → Status: PENDING
4. When 10 responses received → Session LOCKED → Status: SESSION_LOCKED
5. Poster shortlists responders → Status: SHORTLISTED
6. Chat enabled for shortlisted responders
7. Quotations submitted
8. Deal finalized → Status: DEAL_WON or DEAL_LOST
```

### Step-by-Step:

**1. Create Inquiry (Poster)**
```http
POST /api/v1/{role}/inquiry/post
```

**2. System Matching (Automatic)**
- System finds matching users based on:
  - Material/Machine/Job type
  - Location proximity
  - Capacity match
  - Profile completeness

**3. Responders See Inquiry**
```http
GET /api/v1/{role}/inquiries
```

**4. Responder Submits Response**
```http
POST /api/v1/{role}/inquiry/{id}/respond
```

**5. Session Locked (When 10 responses)**
- System automatically locks session
- Creates MatchingSession
- Sets discovery_end and active_session_start

**6. Poster Views Responses**
```http
GET /api/v1/{role}/inquiry/{id}/responses
```

**7. Poster Shortlists**
```http
POST /api/v1/{role}/response/{id}/shortlist
```

**8. Chat Enabled**
```http
GET /api/v1/chat/{session_id}
POST /api/v1/chat/{session_id}/message
```

**9. Submit Quotation**
```http
POST /api/v1/{role}/quote/submit/{inquiry_id}
```

---

## 7️⃣ SESSION FLOW

### Session Timing (Based on Urgency & Type):

#### Urgent Material Inquiry (Dealer/Converter):
- **Discovery Time:** 0-20 minutes
- **Active Session Time:** 24 hours
- **Republish:** Available after 24 hours if no deal

#### Normal Material Inquiry (Dealer/Converter):
- **Discovery Time:** 0-1 hour
- **Active Session Time:** 48-72 hours
- **Republish:** Available before 72 hours expire

#### Urgent Machine Inquiry:
- **Discovery Time:** 0-3 hours
- **Active Session Time:** 72 hours
- **Republish:** 1 time allowed

#### Normal Machine Inquiry:
- **Discovery Time:** 0-12 hours
- **Active Session Time:** 7 days
- **Republish:** 1 time allowed

#### Urgent Job Outsourcing:
- **Discovery Time:** 0-30 minutes
- **Active Session Time:** 12 hours
- **Republish:** 1 time allowed

#### Normal Job Outsourcing:
- **Discovery Time:** 0-6 hours
- **Active Session Time:** 48 hours
- **Republish:** 1 time allowed

#### Urgent Brand Job:
- **Discovery Time:** 0-30 minutes
- **Active Session Time:** 24 hours
- **Responders Shown:** 5 (quality-biased)
- **Republish:** 1 time allowed

#### Normal Brand Job:
- **Discovery Time:** 0-2 hours
- **Active Session Time:** 72 hours
- **Responders Shown:** 5
- **Republish:** 1 time allowed

### Republish Flow:
```http
POST /api/v1/inquiry/{id}/republish
Authorization: Bearer {token}
```

**Conditions:**
- Republish count < 1
- Session time not expired
- Poster has spoken to all current responders

---

## 8️⃣ COMPLETE EXAMPLE SCENARIOS

### Scenario 1: Converter Needs Material (Urgent)

**Step 1:** Converter logs in
```http
POST /api/v1/auth/otp/verify
```

**Step 2:** Converter posts urgent material inquiry
```http
POST /api/v1/converter/inquiry/post
{
  "inquiry_type": "material",
  "intent": "buy",
  "material_ids": [1],
  "quantity": 2000,
  "urgency": "urgent"
}
```

**Step 3:** System matches dealers (0-20 minutes)
- Finds dealers with matching material
- Checks location proximity
- Calculates match score

**Step 4:** Dealers see opportunity
```http
GET /api/v1/dealer/opportunities?urgency=urgent
```

**Step 5:** Dealers accept (up to 10)
```http
POST /api/v1/dealer/opportunity/1/accept
```

**Step 6:** Session locks (when 10th dealer accepts)
- Status: SESSION_LOCKED
- Discovery ends
- Active session starts (24 hours)

**Step 7:** Converter views session
```http
GET /api/v1/converter/session/1
```

**Step 8:** Chat & Quotations
```http
POST /api/v1/dealer/quote/submit/1
{
  "quoted_price": 50000,
  "delivery_days": 2
}
```

**Step 9:** Converter selects dealer
- Deal status: DEAL_WON
- Other dealers: DEAL_LOST

---

### Scenario 2: Machine Dealer Sells Machine

**Step 1:** Machine Dealer posts machine
```http
POST /api/v1/machine-dealer/machine/post
{
  "machine_id": 1,
  "intent": "sell",
  "condition": "Working Condition",
  "price": 500000
}
```

**Step 2:** Converters & Machine Dealers see listing
```http
GET /api/v1/machine-dealer/requirements?intent=buy
```

**Step 3:** Responders submit responses
```http
POST /api/v1/machine-dealer/requirement/1/respond
{
  "quoted_price": 450000,
  "additional_details": "Interested"
}
```

**Step 4:** Session locks & chat begins

---

### Scenario 3: Brand Posts Packaging Job

**Step 1:** Brand posts requirement
```http
POST /api/v1/brand/requirement/post
{
  "need_type": "Packaging + Printing",
  "quantity": 1500,
  "urgency": "urgent"
}
```

**Step 2:** System finds 5 best converters
- Based on finished products
- Capacity match
- Location

**Step 3:** Converters respond
```http
POST /api/v1/converter/inquiry/1/respond
```

**Step 4:** Brand shortlists 2-3
```http
POST /api/v1/brand/response/1/shortlist
```

**Step 5:** Chat & finalize

---

## 🔔 NOTIFICATION FLOW

Notifications are triggered for:
1. New opportunity matching your profile
2. Session locked (you're in the session)
3. New response to your inquiry
4. New chat message
5. Deal result (won/lost)
6. Session expiring soon

**Get Notifications:**
```http
GET /api/v1/notifications?unread_only=true
```

**Mark as Read:**
```http
POST /api/v1/notification/1/read
```

---

## 📱 NIGHT MODE HANDLING

**User Availability Setup:**
- During registration, users select availability hours
- Options: Business Hours, Late Evening, Night/Early Morning

**When Urgent Inquiry Posted at Night:**
1. System shows message: "Urgent matching will fully start at 8:30 AM"
2. Limited matching with available users only
3. Full matching starts at 8:30 AM
4. Session time doesn't count until 8:30 AM

---

## ✅ COMPLETE CHECKLIST

### For Each Role:
- [x] Authentication (OTP)
- [x] Profile Completion
- [x] Dashboard
- [x] Post Inquiries
- [x] Browse Inquiries
- [x] Respond to Inquiries
- [x] View Sessions
- [x] Chat
- [x] Quotations
- [x] Notifications

### System Features:
- [x] Matching Engine
- [x] Session Timing
- [x] Republish Logic
- [x] Night Mode
- [x] Response Management
- [x] Chat System
- [x] Notification System

---

**All APIs are ready for testing!** 🚀

