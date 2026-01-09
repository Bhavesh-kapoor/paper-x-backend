# COMPLETE API DOCUMENTATION
## B2B Matchmaking Platform - All Roles

**Base URL:** `{{BASE_URL}}/api/v1`

**Authentication:** Bearer Token (required for all protected endpoints)

---

## 📋 TABLE OF CONTENTS

1. [Authentication APIs](#1-authentication-apis)
2. [Common APIs](#2-common-apis)
3. [Dealer APIs](#3-dealer-apis)
4. [Machine Dealer APIs](#4-machine-dealer-apis)
5. [Converter APIs](#5-converter-apis)
6. [Brand APIs](#6-brand-apis)
7. [Inquiry APIs (Enhanced)](#7-inquiry-apis-enhanced)
8. [Response APIs](#8-response-apis)
9. [Session APIs (Enhanced)](#9-session-apis-enhanced)
10. [Chat APIs](#10-chat-apis)
11. [Notification APIs](#11-notification-apis)

---

## 1️⃣ AUTHENTICATION APIs

### 1.1 Request OTP
**Endpoint:** `POST /auth/otp/request`  
**Method:** POST  
**Auth:** Not Required

**Request:**
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
    "type": "otp_sent"
  }
}
```

---

### 1.2 Verify OTP & Login
**Endpoint:** `POST /auth/otp/verify`  
**Method:** POST  
**Auth:** Not Required

**Request:**
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
  "message": "Login successful",
  "data": {
    "type": "login_success",
    "token": "1|xxxxxxxxxxxx",
    "user": {
      "id": 1,
      "name": "John Doe",
      "mobile": "9876543210",
      "email": "john@example.com",
      "primary_role": "dealer"
    }
  }
}
```

---

## 2️⃣ COMMON APIs

### 2.1 Get User Profile
**Endpoint:** `GET /user/profile`  
**Method:** GET  
**Auth:** Required

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "mobile": "9876543210",
    "email": "john@example.com",
    "primary_role": "dealer",
    "secondary_role": "converter",
    "company_name": "ABC Corp",
    "gst_in": "29ABCDE1234F1Z5",
    "city": "Mumbai",
    "state": "Maharashtra"
  }
}
```

---

### 2.2 Update User Profile
**Endpoint:** `POST /user/profile`  
**Method:** POST  
**Auth:** Required

**Request:**
```json
{
  "name": "John Doe Updated",
  "email": "john.updated@example.com",
  "company_name": "ABC Corp Updated"
}
```

---

### 2.3 Switch Role
**Endpoint:** `POST /user/switch-role`  
**Method:** POST  
**Auth:** Required

**Request:**
```json
{
  "role": "converter"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Role switched successfully",
  "data": {
    "current_role": "converter",
    "available_roles": ["dealer", "converter", "brand"]
  }
}
```

---

### 2.4 Get Materials (Reference Data)
**Endpoint:** `GET /materials`  
**Method:** GET  
**Auth:** Not Required

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Kraft Paper",
      "category": "PACKAGING PAPERS"
    }
  ]
}
```

---

## 3️⃣ DEALER APIs

### 3.1 Complete Dealer Profile
**Endpoint:** `POST /dealer/profile/complete`  
**Method:** POST  
**Auth:** Required

**Request:**
```json
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

---

### 3.2 Get Dealer Dashboard
**Endpoint:** `GET /dealer/dashboard`  
**Method:** GET  
**Auth:** Required

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

---

### 3.3 Get Opportunities (Material Inquiries)
**Endpoint:** `GET /dealer/opportunities`  
**Method:** GET  
**Auth:** Required

**Query Parameters:**
- `urgency` (optional): `normal` | `urgent`
- `status` (optional): `MATCHING` | `SESSION_LOCKED`
- `page` (optional): default 1
- `per_page` (optional): default 15

**Response:**
```json
{
  "success": true,
  "data": {
    "opportunities": [
      {
        "id": 1,
        "title": "Need Duplex Board 350 GSM",
        "material": "Duplex Board",
        "quantity": 2000,
        "quantity_unit": "sheets",
        "location": "Mumbai",
        "urgency": "urgent",
        "match_score": 95,
        "status": "MATCHING"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total": 10,
      "per_page": 15
    }
  }
}
```

---

### 3.4 Accept Opportunity
**Endpoint:** `POST /dealer/opportunity/{id}/accept`  
**Method:** POST  
**Auth:** Required

**Response:**
```json
{
  "success": true,
  "message": "Opportunity accepted successfully"
}
```

---

### 3.5 Decline Opportunity
**Endpoint:** `POST /dealer/opportunity/{id}/decline`  
**Method:** POST  
**Auth:** Required

**Request:**
```json
{
  "reason": "Not available in required quantity"
}
```

---

## 4️⃣ MACHINE DEALER APIs

### 4.1 Complete Machine Dealer Profile
**Endpoint:** `POST /machine-dealer/profile/complete`  
**Method:** POST  
**Auth:** Required

**Request:**
```json
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

**Response:**
```json
{
  "success": true,
  "message": "Machine dealer profile completed successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "company_name": "ABC Machine Dealers",
    "status": "ACTIVE",
    "profile_complete": true
  }
}
```

---

### 4.2 Get Machine Dealer Dashboard
**Endpoint:** `GET /machine-dealer/dashboard`  
**Method:** GET  
**Auth:** Required

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

---

### 4.3 Post Machine for Sale/Buy
**Endpoint:** `POST /machine-dealer/machine/post`  
**Method:** POST  
**Auth:** Required

**Request:**
```json
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
  "longitude": 72.8697,
  "attachments": ["path/to/image1.jpg"]
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

---

### 4.4 Browse Active Machine Requirements
**Endpoint:** `GET /machine-dealer/requirements`  
**Method:** GET  
**Auth:** Required

**Query Parameters:**
- `intent` (optional): `buy` | `sell`
- `urgency` (optional): `normal` | `urgent`
- `machine_id` (optional): Filter by machine type
- `page` (optional): default 1

**Response:**
```json
{
  "success": true,
  "data": {
    "requirements": [
      {
        "id": 1,
        "inquiry_id": 1,
        "machine": "Automatic Folder Gluer",
        "condition": "Working Condition",
        "intent": "buy",
        "urgency": "urgent",
        "location": "Mumbai",
        "poster_type": "converter",
        "status": "MATCHING"
      }
    ],
    "pagination": {...}
  }
}
```

---

### 4.5 My Active Listings
**Endpoint:** `GET /machine-dealer/listings`  
**Method:** GET  
**Auth:** Required

**Response:**
```json
{
  "success": true,
  "data": {
    "listings": [
      {
        "id": 1,
        "machine": "Automatic Folder Gluer",
        "condition": "Working Condition",
        "intent": "sell",
        "status": "ACTIVE",
        "responses_count": 3,
        "created_at": "2024-01-01T10:00:00Z"
      }
    ]
  }
}
```

---

### 4.6 Respond to Machine Requirement
**Endpoint:** `POST /machine-dealer/requirement/{inquiry_id}/respond`  
**Method:** POST  
**Auth:** Required

**Request:**
```json
{
  "quoted_price": 450000,
  "currency": "INR",
  "additional_details": "Can deliver within 7 days"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Response submitted successfully",
  "data": {
    "response_id": 1,
    "status": "PENDING"
  }
}
```

---

## 5️⃣ CONVERTER APIs

### 5.1 Complete Converter Profile
**Endpoint:** `POST /converter/profile/complete`  
**Method:** POST  
**Auth:** Required

**Request:**
```json
{
  "converter_type_ids": [1, 2, 3],
  "converter_type_custom": null,
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

---

### 5.2 Get Converter Dashboard
**Endpoint:** `GET /converter/dashboard`  
**Method:** GET  
**Auth:** Required

**Response:**
```json
{
  "success": true,
  "data": {
    "profile_completion_percentage": 100,
    "active_sessions_count": 3,
    "my_inquiries_count": 5,
    "responses_received_count": 12,
    "unread_notifications_count": 4
  }
}
```

---

### 5.3 Post Material Inquiry (Buy/Sell)
**Endpoint:** `POST /converter/inquiry/post`  
**Method:** POST  
**Auth:** Required

**Request:**
```json
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
  "price": 50,
  "price_unit": "per_sheet",
  "price_negotiable": true,
  "urgency": "urgent",
  "location": "Mumbai",
  "latitude": 19.1136,
  "longitude": 72.8697,
  "description": "Need urgently for production"
}
```

---

### 5.4 Post Machine Inquiry (Buy/Sell)
**Endpoint:** `POST /converter/inquiry/post`  
**Method:** POST  
**Auth:** Required

**Request:**
```json
{
  "inquiry_type": "machine",
  "intent": "buy",
  "title": "Need Automatic Folder Gluer",
  "machine_ids": [1],
  "machine_condition": "Working Condition",
  "urgency": "normal",
  "location": "Mumbai",
  "latitude": 19.1136,
  "longitude": 72.8697,
  "description": "Looking for good condition machine"
}
```

---

### 5.5 Post Job Outsourcing Inquiry
**Endpoint:** `POST /converter/inquiry/post`  
**Method:** POST  
**Auth:** Required

**Request:**
```json
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
  "longitude": 72.8697,
  "description": "Need quality work"
}
```

---

### 5.6 Browse Inquiries
**Endpoint:** `GET /converter/inquiries`  
**Method:** GET  
**Auth:** Required

**Query Parameters:**
- `inquiry_type` (optional): `material` | `machine` | `job`
- `intent` (optional): `buy` | `sell`
- `urgency` (optional): `normal` | `urgent`
- `page` (optional): default 1

---

### 5.7 Respond to Inquiry
**Endpoint:** `POST /converter/inquiry/{id}/respond`  
**Method:** POST  
**Auth:** Required

**Request:**
```json
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

### 5.8 Get Active Sessions
**Endpoint:** `GET /converter/sessions`  
**Method:** GET  
**Auth:** Required

**Response:**
```json
{
  "success": true,
  "data": {
    "sessions": [
      {
        "id": 1,
        "inquiry_id": 1,
        "inquiry_title": "Need Duplex Board",
        "status": "ACTIVE",
        "expires_at": "2024-01-02T10:00:00Z",
        "responses_count": 5
      }
    ]
  }
}
```

---

### 5.9 Get Session History
**Endpoint:** `GET /converter/history`  
**Method:** GET  
**Auth:** Required

---

## 6️⃣ BRAND APIs

### 6.1 Complete Brand Profile
**Endpoint:** `POST /brand/profile/complete`  
**Method:** POST  
**Auth:** Required

**Request:**
```json
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

---

### 6.2 Get Brand Dashboard
**Endpoint:** `GET /brand/dashboard`  
**Method:** GET  
**Auth:** Required

**Response:**
```json
{
  "success": true,
  "data": {
    "profile_completion_percentage": 100,
    "my_inquiries_count": 5,
    "active_sessions_count": 3,
    "unread_messages_count": 8,
    "unread_notifications_count": 2
  }
}
```

---

### 6.3 Post Packaging/Printing Requirement
**Endpoint:** `POST /brand/requirement/post`  
**Method:** POST  
**Auth:** Required

**Request:**
```json
{
  "need_type": "Packaging + Printing",
  "packaging_type": "Rigid Boxes",
  "quantity": 1500,
  "quantity_unit": "pieces",
  "timeline_days": 3,
  "urgency": "urgent",
  "special_needs": "Premium finish required",
  "attachment_paths": ["path/to/design1.jpg"],
  "finished_product_ids": [1]
}
```

---

### 6.4 Get My Posted Inquiries
**Endpoint:** `GET /brand/inquiries`  
**Method:** GET  
**Auth:** Required

**Response:**
```json
{
  "success": true,
  "data": {
    "inquiries": [
      {
        "id": 1,
        "title": "Need 1500 Cake Boxes",
        "quantity": 1500,
        "status": "SESSION_LOCKED",
        "responses_count": 5,
        "created_at": "2024-01-01T10:00:00Z"
      }
    ]
  }
}
```

---

### 6.5 View Responses to Inquiry
**Endpoint:** `GET /brand/inquiry/{id}/responses`  
**Method:** GET  
**Auth:** Required

**Response:**
```json
{
  "success": true,
  "data": {
    "inquiry": {
      "id": 1,
      "title": "Need 1500 Cake Boxes"
    },
    "responses": [
      {
        "id": 1,
        "responder_type": "converter",
        "responder_name": "XYZ Converters",
        "quantity_offered": 1500,
        "quoted_price": 75000,
        "price_status": "agreed",
        "status": "PENDING",
        "created_at": "2024-01-01T11:00:00Z"
      }
    ]
  }
}
```

---

### 6.6 Shortlist Responder
**Endpoint:** `POST /brand/response/{id}/shortlist`  
**Method:** POST  
**Auth:** Required

**Response:**
```json
{
  "success": true,
  "message": "Responder shortlisted successfully",
  "data": {
    "response_id": 1,
    "status": "SHORTLISTED",
    "session_id": 1
  }
}
```

---

## 7️⃣ INQUIRY APIs (Enhanced)

### 7.1 Get Inquiry Details
**Endpoint:** `GET /inquiry/{id}`  
**Method:** GET  
**Auth:** Required

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "poster_type": "converter",
    "inquiry_type": "material",
    "intent": "buy",
    "title": "Need Duplex Board 350 GSM",
    "materials": [...],
    "quantity": 2000,
    "quantity_unit": "sheets",
    "urgency": "urgent",
    "status": "MATCHING",
    "location": "Mumbai",
    "session": {
      "id": 1,
      "status": "ACTIVE",
      "expires_at": "2024-01-02T10:00:00Z"
    }
  }
}
```

---

### 7.2 Republish Inquiry
**Endpoint:** `POST /inquiry/{id}/republish`  
**Method:** POST  
**Auth:** Required

**Response:**
```json
{
  "success": true,
  "message": "Inquiry republished successfully",
  "data": {
    "inquiry_id": 1,
    "republish_count": 1,
    "status": "MATCHING"
  }
}
```

---

## 8️⃣ RESPONSE APIs

### 8.1 Submit Response
**Endpoint:** `POST /inquiry/{id}/response`  
**Method:** POST  
**Auth:** Required

**Request:**
```json
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

### 8.2 Get Responses to Inquiry
**Endpoint:** `GET /inquiry/{id}/responses`  
**Method:** GET  
**Auth:** Required (Poster only)

---

## 9️⃣ SESSION APIs (Enhanced)

### 9.1 Get Session Details
**Endpoint:** `GET /session/{id}`  
**Method:** GET  
**Auth:** Required

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "inquiry_id": 1,
    "status": "ACTIVE",
    "discovery_start": "2024-01-01T10:00:00Z",
    "discovery_end": "2024-01-01T10:20:00Z",
    "active_session_start": "2024-01-01T10:20:00Z",
    "expires_at": "2024-01-02T10:20:00Z",
    "republish_count": 0,
    "is_night_mode": false,
    "responses": [...],
    "chat_enabled": true
  }
}
```

---

## 🔟 CHAT APIs

### 10.1 Get Chat Messages
**Endpoint:** `GET /chat/{session_id}`  
**Method:** GET  
**Auth:** Required

**Query Parameters:**
- `page` (optional): default 1
- `per_page` (optional): default 50

---

### 10.2 Send Message
**Endpoint:** `POST /chat/{session_id}/message`  
**Method:** POST  
**Auth:** Required

**Request:**
```json
{
  "message": "Hello, I can deliver within 2 days"
}
```

---

### 10.3 Send Attachment
**Endpoint:** `POST /chat/{session_id}/attachment`  
**Method:** POST  
**Auth:** Required  
**Content-Type:** multipart/form-data

**Form Data:**
- `file`: (file)
- `message`: (optional) "Please find attached"

---

## 1️⃣1️⃣ NOTIFICATION APIs

### 11.1 Get Notifications
**Endpoint:** `GET /notifications`  
**Method:** GET  
**Auth:** Required

**Query Parameters:**
- `type` (optional): Filter by notification type
- `unread_only` (optional): `true` | `false`
- `page` (optional): default 1

---

### 11.2 Mark Notification as Read
**Endpoint:** `POST /notification/{id}/read`  
**Method:** POST  
**Auth:** Required

---

### 11.3 Mark All Notifications as Read
**Endpoint:** `POST /notifications/read-all`  
**Method:** POST  
**Auth:** Required

---

## 📊 ERROR RESPONSES

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field_name": ["Error detail"]
  }
}
```

**HTTP Status Codes:**
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthenticated
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## 🔐 AUTHENTICATION

All protected endpoints require:
- **Header:** `Authorization: Bearer {token}`
- Token obtained from `/auth/otp/verify`

---

## 📝 NOTES

1. All timestamps are in ISO 8601 format (UTC)
2. All monetary values are in base currency (INR)
3. Pagination follows Laravel standard format
4. File uploads use multipart/form-data
5. All IDs are integers
6. All decimal values support 2 decimal places




