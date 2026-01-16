# API Routes Documentation

## Base URL
```
http://your-domain.com/api/v1
```

## Authentication
Most endpoints require authentication via Bearer token:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## 📋 Table of Contents

1. [Authentication Routes](#1-authentication-routes)
2. [User Profile Routes](#2-user-profile-routes)
3. [Reference Data Routes](#3-reference-data-routes)
4. [Dealer Routes](#4-dealer-routes)
5. [Machine Dealer Routes](#5-machine-dealer-routes)
6. [Converter Routes](#6-converter-routes)
7. [Brand Routes](#7-brand-routes)
8. [Common Routes](#8-common-routes)

---

## 1. Authentication Routes

### 1.1 Request OTP
- **Endpoint:** `POST /api/v1/auth/otp/request`
- **Method:** POST
- **Auth:** Not Required
- **Description:** Request OTP for login
- **Route Name:** `auth.otp.request`

### 1.2 Verify OTP
- **Endpoint:** `POST /api/v1/auth/otp/verify`
- **Method:** POST
- **Auth:** Not Required
- **Description:** Verify OTP and get authentication token
- **Route Name:** `auth.otp.verify`

---

## 2. User Profile Routes

### 2.1 Get User Profile
- **Endpoint:** `GET /api/v1/user/profile`
- **Method:** GET
- **Auth:** Required
- **Description:** Get authenticated user's profile
- **Route Name:** `user.profile.get`

### 2.2 Update User Profile
- **Endpoint:** `POST /api/v1/user/profile`
- **Method:** POST
- **Auth:** Required
- **Description:** Update user profile information
- **Route Name:** `user.profile.update`

### 2.3 Switch Role
- **Endpoint:** `POST /api/v1/user/switch-role`
- **Method:** POST
- **Auth:** Required
- **Description:** Switch between user roles (dealer, converter, brand, machine-dealer)
- **Route Name:** `user.switch-role`

---

## 3. Reference Data Routes

### 3.1 Get Materials
- **Endpoint:** `GET /api/v1/materials`
- **Method:** GET
- **Auth:** Not Required
- **Description:** Get list of all materials
- **Query Parameters:**
  - `category` (optional): Filter by category
  - `page` (optional): Page number
  - `per_page` (optional): Items per page
- **Route Name:** `materials.list`

### 3.2 Get Machines
- **Endpoint:** `GET /api/v1/machines`
- **Method:** GET
- **Auth:** Not Required
- **Description:** Get list of all machines
- **Query Parameters:**
  - `type` (optional): Filter by machine type
  - `category` (optional): Filter by category
  - `per_page` (optional): Items per page (default: 50)
- **Route Name:** `reference.machines`

### 3.3 Get Material Finishes
- **Endpoint:** `GET /api/v1/material-finishes`
- **Method:** GET
- **Auth:** Not Required
- **Description:** Get list of material finishes/grades/coatings
- **Query Parameters:**
  - `material_id` (optional): Filter by material ID
  - `type` (optional): Filter by finish type
  - `per_page` (optional): Items per page (default: 50)
- **Route Name:** `reference.material-finishes`

### 3.4 Get Material Mills
- **Endpoint:** `GET /api/v1/material-mills`
- **Method:** GET
- **Auth:** Not Required
- **Description:** Get list of mills/brands for a specific material
- **Query Parameters:**
  - `material_id` (required): Material ID
  - `per_page` (optional): Items per page (default: 50)
- **Route Name:** `reference.material-mills`

### 3.5 Get Material Thickness Types
- **Endpoint:** `GET /api/v1/material-thickness-types`
- **Method:** GET
- **Auth:** Not Required
- **Description:** Get thickness types/units for a specific material
- **Query Parameters:**
  - `material_id` (required): Material ID
  - `per_page` (optional): Items per page (default: 50)
- **Route Name:** `reference.material-thickness-types`

### 3.6 Get Brands (Mill Brands)
- **Endpoint:** `GET /api/v1/brands`
- **Method:** GET
- **Auth:** Not Required
- **Description:** Get list of mill brands (not user brand profiles)
- **Query Parameters:**
  - `per_page` (optional): Items per page (default: 50)
  - `page` (optional): Page number
- **Route Name:** `reference.brands`

### 3.7 Get Material Details
- **Endpoint:** `GET /api/v1/materials/{id}/details`
- **Method:** GET
- **Auth:** Not Required
- **Description:** Get complete material details with mills, finishes, and thickness types
- **URL Parameters:**
  - `id` (required): Material ID
- **Route Name:** `reference.material-details`

### 3.8 Add Mill/Brand Manually
- **Endpoint:** `POST /api/v1/dealer/mill/add`
- **Method:** POST
- **Auth:** Required
- **Description:** Manually add a mill/brand for dealer registration
- **Request Body:**
  ```json
  {
    "mill_brand_id": 1,                    // Optional: Use existing brand
    "mill_brand_name": "ABC Mills",        // Required if mill_brand_id not provided
    "prefer_not_to_disclose": false,
    "relationship": "authorized-agent",    // or "independent-dealer"
    "material_id": 45                      // Optional: Associate with material
  }
  ```
- **Route Name:** `dealer.mill.add`

### 3.9 Add Finish Manually
- **Endpoint:** `POST /api/v1/dealer/finish/add`
- **Method:** POST
- **Auth:** Required
- **Description:** Manually add a finish if not available in the list
- **Request Body:**
  ```json
  {
    "name": "Custom Gloss Finish",
    "material_id": 45,                     // Optional
    "type": "finish"                       // Optional: finish, coating, grade, etc.
  }
  ```
- **Route Name:** `dealer.finish.add`

---

## 4. Dealer Routes

### 4.1 Complete Dealer Profile
- **Endpoint:** `POST /api/v1/dealer/profile/complete`
- **Method:** POST
- **Auth:** Required
- **Description:** Complete dealer profile with materials, mills, finishes, and locations
- **Request Body:** See [API_REQUEST_FORMATS.md](./API_REQUEST_FORMATS.md) for detailed format
- **Route Name:** `dealer.profile.complete`

### 4.2 Get Dealer Dashboard
- **Endpoint:** `GET /api/v1/dealer/dashboard`
- **Method:** GET
- **Auth:** Required
- **Description:** Get dealer dashboard statistics
- **Route Name:** `dealer.dashboard`

### 4.3 Post Requirement (Buy/Sell)
- **Endpoint:** `POST /api/v1/dealer/requirement/post`
- **Method:** POST
- **Auth:** Required
- **Description:** Post a requirement for buying or selling materials/machines/jobs
- **Request Format:** See [API_REQUEST_FORMATS.md](./API_REQUEST_FORMATS.md)
- **Route Name:** `dealer.requirement.post`

### 4.4 Get Requirements (With Filters)
- **Endpoint:** `GET /api/v1/dealer/requirements`
- **Method:** GET
- **Auth:** Required
- **Description:** Get dealer's posted requirements with filters and pagination
- **Query Parameters:**
  - `inquiry_type` (optional): "material" | "machine" | "job"
  - `intent` (optional): "buy" | "sell"
  - `status` (optional): "MATCHING" | "SESSION_LOCKED" | "COMPLETED" | "CANCELLED"
  - `urgency` (optional): "normal" | "urgent"
  - `material_id` (optional): Filter by material ID
  - `machine_id` (optional): Filter by machine ID
  - `sort_by` (optional): "created_at" | "updated_at" (default: "created_at")
  - `sort_order` (optional): "asc" | "desc" (default: "desc")
  - `per_page` (optional): Items per page (default: 15)
  - `page` (optional): Page number (default: 1)
- **Route Name:** `dealer.requirements`

### 4.5 Get Opportunities
- **Endpoint:** `GET /api/v1/dealer/opportunities`
- **Method:** GET
- **Auth:** Required
- **Description:** Get list of available opportunities (inquiries)
- **Query Parameters:**
  - `page` (optional): Page number
  - `per_page` (optional): Items per page
- **Route Name:** `dealer.opportunities`

### 4.6 Get Opportunity Details
- **Endpoint:** `GET /api/v1/dealer/opportunity/{inquiry_id}`
- **Method:** GET
- **Auth:** Required
- **Description:** Get detailed information about a specific opportunity
- **URL Parameters:**
  - `inquiry_id` (required): Inquiry ID
- **Route Name:** `dealer.opportunity.details`

### 4.7 Accept Opportunity
- **Endpoint:** `POST /api/v1/dealer/opportunity/{id}/accept`
- **Method:** POST
- **Auth:** Required
- **Description:** Accept an opportunity/inquiry
- **URL Parameters:**
  - `id` (required): Inquiry ID
- **Route Name:** `dealer.opportunity.accept`

### 4.8 Decline Opportunity
- **Endpoint:** `POST /api/v1/dealer/opportunity/{id}/decline`
- **Method:** POST
- **Auth:** Required
- **Description:** Decline an opportunity/inquiry
- **URL Parameters:**
  - `id` (required): Inquiry ID
- **Request Body:**
  ```json
  {
    "reason": "Not available in required quantity"
  }
  ```
- **Route Name:** `dealer.opportunity.decline`

### 4.9 Get Session Details
- **Endpoint:** `GET /api/v1/dealer/session/{session_id}`
- **Method:** GET
- **Auth:** Required
- **Description:** Get details of a matching session
- **URL Parameters:**
  - `session_id` (required): Session ID
- **Route Name:** `dealer.session.details`

### 4.10 Get Session History
- **Endpoint:** `GET /api/v1/dealer/history`
- **Method:** GET
- **Auth:** Required
- **Description:** Get dealer's session history
- **Query Parameters:**
  - `page` (optional): Page number
  - `per_page` (optional): Items per page
- **Route Name:** `dealer.history`

### 4.11 Get Chat Messages
- **Endpoint:** `GET /api/v1/dealer/chat/{session_id}`
- **Method:** GET
- **Auth:** Required
- **Description:** Get chat messages for a session
- **URL Parameters:**
  - `session_id` (required): Session ID
- **Query Parameters:**
  - `page` (optional): Page number
  - `per_page` (optional): Items per page
- **Route Name:** `dealer.chat.messages`

### 4.12 Send Chat Message
- **Endpoint:** `POST /api/v1/dealer/chat/{session_id}/message`
- **Method:** POST
- **Auth:** Required
- **Description:** Send a message in a chat session
- **URL Parameters:**
  - `session_id` (required): Session ID
- **Request Body:**
  ```json
  {
    "message": "Hello, I can provide this material",
    "attachments": []  // Optional
  }
  ```
- **Route Name:** `dealer.chat.send`

### 4.13 Submit Quotation
- **Endpoint:** `POST /api/v1/dealer/quote/submit/{inquiry_id}`
- **Method:** POST
- **Auth:** Required
- **Description:** Submit a quotation for an inquiry
- **URL Parameters:**
  - `inquiry_id` (required): Inquiry ID
- **Request Body:**
  ```json
  {
    "price": 50000.00,
    "currency": "INR",
    "quantity": 1000,
    "unit": "kg",
    "validity_days": 7,
    "notes": "Additional notes"
  }
  ```
- **Route Name:** `dealer.quote.submit`

### 4.12 Get Notifications
- **Endpoint:** `GET /api/v1/dealer/notifications`
- **Method:** GET
- **Auth:** Required
- **Description:** Get dealer notifications
- **Query Parameters:**
  - `page` (optional): Page number
  - `per_page` (optional): Items per page
  - `read` (optional): Filter by read status (true/false)
- **Route Name:** `dealer.notifications`

### 4.13 Mark Notification as Read
- **Endpoint:** `POST /api/v1/dealer/notification/{id}/read`
- **Method:** POST
- **Auth:** Required
- **Description:** Mark a specific notification as read
- **URL Parameters:**
  - `id` (required): Notification ID
- **Route Name:** `dealer.notification.read`

### 4.14 Mark All Notifications as Read
- **Endpoint:** `POST /api/v1/dealer/notifications/read-all`
- **Method:** POST
- **Auth:** Required
- **Description:** Mark all notifications as read
- **Route Name:** `dealer.notifications.read-all`

---

## 5. Machine Dealer Routes

### 5.1 Complete Machine Dealer Profile
- **Endpoint:** `POST /api/v1/machine-dealer/profile/complete`
- **Method:** POST
- **Auth:** Required
- **Description:** Complete machine dealer profile
- **Request Body:**
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
- **Route Name:** `machine-dealer.profile.complete`

### 5.2 Get Machine Dealer Dashboard
- **Endpoint:** `GET /api/v1/machine-dealer/dashboard`
- **Method:** GET
- **Auth:** Required
- **Description:** Get machine dealer dashboard statistics
- **Route Name:** `machine-dealer.dashboard`

### 5.3 Post Machine
- **Endpoint:** `POST /api/v1/machine-dealer/machine/post`
- **Method:** POST
- **Auth:** Required
- **Description:** Post a machine for sale/buy
- **Request Body:**
  ```json
  {
    "machine_id": 1,
    "machine_brand_id": 1,
    "machine_type": "Printing",
    "condition": "NEW",
    "intent": "SELL",
    "urgency": "NORMAL",
    "description": "High quality printing machine",
    "price": 500000,
    "currency": "INR",
    "location": "Mumbai",
    "latitude": 19.1136,
    "longitude": 72.8697
  }
  ```
- **Route Name:** `machine-dealer.machine.post`

### 5.4 Get Active Listings
- **Endpoint:** `GET /api/v1/machine-dealer/listings`
- **Method:** GET
- **Auth:** Required
- **Description:** Get active machine listings
- **Query Parameters:**
  - `per_page` (optional): Items per page (default: 15)
- **Route Name:** `machine-dealer.listings`

### 5.5 Get Active Requirements
- **Endpoint:** `GET /api/v1/machine-dealer/requirements`
- **Method:** GET
- **Auth:** Required
- **Description:** Get active machine requirements
- **Query Parameters:**
  - `intent` (optional): Filter by intent (BUY/SELL)
  - `urgency` (optional): Filter by urgency
  - `machine_id` (optional): Filter by machine ID
  - `per_page` (optional): Items per page (default: 15)
- **Route Name:** `machine-dealer.requirements`

---

## 6. Converter Routes

### 6.1 Complete Converter Profile
- **Endpoint:** `POST /api/v1/converter/profile/complete`
- **Method:** POST
- **Auth:** Required
- **Description:** Complete converter profile
- **Request Body:**
  ```json
  {
    "converter_type": "PACKAGING",
    "finished_products": [1, 2, 3],
    "machines": [1, 2],
    "scrap_materials": [1, 2],
    "capacity_daily": 1000,
    "capacity_monthly": 30000,
    "capacity_unit": "kg",
    "raw_materials": [1, 2, 3],
    "factory_address": "123 Factory Street",
    "factory_latitude": 19.1136,
    "factory_longitude": 72.8697,
    "factory_city": "Mumbai",
    "factory_state": "Maharashtra"
  }
  ```
- **Route Name:** `converter.profile.complete`

### 6.2 Get Converter Dashboard
- **Endpoint:** `GET /api/v1/converter/dashboard`
- **Method:** GET
- **Auth:** Required
- **Description:** Get converter dashboard statistics
- **Route Name:** `converter.dashboard`

---

## 7. Brand Routes

### 7.1 Complete Brand Profile
- **Endpoint:** `POST /api/v1/brand/profile/complete`
- **Method:** POST
- **Auth:** Required
- **Description:** Complete brand profile
- **Request Body:**
  ```json
  {
    "company_name": "ABC Paper Mills Ltd",
    "brand_name": "Premium Papers",
    "contact_person_name": "John Doe",
    "mobile": "9876543210",
    "email": "john@abcmills.com",
    "gst": "29ABCDE1234F1Z5",
    "city": "Mumbai",
    "location": "Andheri East",
    "latitude": 19.1136,
    "longitude": 72.8697,
    "brand_type_ids": [1, 2, 3]
  }
  ```
- **Route Name:** `brand.profile.complete`

### 7.2 Get Brand Dashboard
- **Endpoint:** `GET /api/v1/brand/dashboard`
- **Method:** GET
- **Auth:** Required
- **Description:** Get brand dashboard statistics
- **Route Name:** `brand.dashboard`

---

## 8. Common Routes

### 8.1 Unified Dashboard
- **Endpoint:** `GET /api/v1/dashboard`
- **Method:** GET
- **Auth:** Required
- **Description:** Get unified dashboard for current user role
- **Route Name:** `dashboard`

---

## Route Summary by Category

### Public Routes (No Authentication)
- `GET /api/v1/materials`
- `GET /api/v1/machines`
- `GET /api/v1/material-finishes`
- `GET /api/v1/material-mills`
- `GET /api/v1/material-thickness-types`
- `GET /api/v1/brands`
- `GET /api/v1/materials/{id}/details`
- `POST /api/v1/auth/otp/request`
- `POST /api/v1/auth/otp/verify`

### Authenticated Routes (Require Bearer Token)
All other routes require authentication via `Authorization: Bearer {token}` header.

---

## Route Naming Convention

All routes follow Laravel naming conventions:
- Resource routes: `resource.action` (e.g., `dealer.profile.complete`)
- Reference data: `reference.resource` (e.g., `reference.machines`)
- Actions: `resource.action` (e.g., `dealer.opportunity.accept`)

---

## Error Responses

All endpoints return consistent error responses:

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Resource not found"
}
```

### 500 Server Error
```json
{
  "success": false,
  "message": "Internal server error"
}
```

---

## Notes

1. All authenticated routes require the `token.exists` and `auth:sanctum` middleware
2. Pagination is available on most list endpoints
3. All timestamps are in ISO 8601 format
4. All monetary values are in the specified currency (default: INR)
5. Coordinates use decimal degrees format (latitude: -90 to 90, longitude: -180 to 180)

---

## Quick Reference

| Endpoint | Method | Auth | Category |
|----------|--------|------|----------|
| `/auth/otp/request` | POST | No | Authentication |
| `/auth/otp/verify` | POST | No | Authentication |
| `/user/profile` | GET/POST | Yes | User |
| `/user/switch-role` | POST | Yes | User |
| `/materials` | GET | No | Reference |
| `/machines` | GET | No | Reference |
| `/material-finishes` | GET | No | Reference |
| `/material-mills` | GET | No | Reference |
| `/brands` | GET | No | Reference |
| `/dealer/mill/add` | POST | Yes | Dealer |
| `/dealer/finish/add` | POST | Yes | Dealer |
| `/dealer/profile/complete` | POST | Yes | Dealer |
| `/dealer/dashboard` | GET | Yes | Dealer |
| `/dealer/opportunities` | GET | Yes | Dealer |
| `/machine-dealer/profile/complete` | POST | Yes | Machine Dealer |
| `/converter/profile/complete` | POST | Yes | Converter |
| `/brand/profile/complete` | POST | Yes | Brand |
| `/dashboard` | GET | Yes | Common |


