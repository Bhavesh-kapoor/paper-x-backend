# API Routes Documentation

## Base URL
```
https://your-domain.com/api/v1
```

## Authentication
Most endpoints require Bearer token authentication:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## 📋 Table of Contents

1. [Authentication](#1-authentication)
2. [User Profile](#2-user-profile)
3. [Reference Data](#3-reference-data)
4. [Dealer APIs](#4-dealer-apis)
5. [Machine Dealer APIs](#5-machine-dealer-apis)
6. [Converter APIs](#6-converter-apis)
7. [Brand APIs](#7-brand-apis)
8. [Common APIs](#8-common-apis)

---

## 1. Authentication

### Request OTP
```
POST /api/v1/auth/otp/request
```
**Auth:** Not Required  
**Description:** Request OTP for login

### Verify OTP
```
POST /api/v1/auth/otp/verify
```
**Auth:** Not Required  
**Description:** Verify OTP and get authentication token

---

## 2. User Profile

### Get User Profile
```
GET /api/v1/user/profile
```
**Auth:** Required  
**Description:** Get authenticated user's profile

### Update User Profile
```
POST /api/v1/user/profile
```
**Auth:** Required  
**Description:** Update user profile information

### Switch Role
```
POST /api/v1/user/switch-role
```
**Auth:** Required  
**Description:** Switch between user roles (dealer, converter, brand, machine-dealer)

---

## 3. Reference Data

### Get Materials
```
GET /api/v1/materials
```
**Auth:** Not Required  
**Query Parameters:**
- `category` (optional): Filter by category
- `page` (optional): Page number
- `per_page` (optional): Items per page

### Get Machines
```
GET /api/v1/machines
```
**Auth:** Not Required  
**Query Parameters:**
- `type` (optional): Filter by machine type
- `category` (optional): Filter by category
- `per_page` (optional): Items per page (default: 50)

### Get Material Finishes
```
GET /api/v1/material-finishes
```
**Auth:** Not Required  
**Query Parameters:**
- `material_id` (optional): Filter by material ID
- `type` (optional): Filter by finish type
- `per_page` (optional): Items per page (default: 50)

### Get Material Mills
```
GET /api/v1/material-mills
```
**Auth:** Not Required  
**Query Parameters:**
- `material_id` (required): Material ID
- `per_page` (optional): Items per page (default: 50)

### Get Material Thickness Types
```
GET /api/v1/material-thickness-types
```
**Auth:** Not Required  
**Query Parameters:**
- `material_id` (required): Material ID
- `per_page` (optional): Items per page (default: 50)

### Get Brands (Mill Brands)
```
GET /api/v1/brands
```
**Auth:** Not Required  
**Query Parameters:**
- `per_page` (optional): Items per page (default: 50)
- `page` (optional): Page number

### Get Material Details
```
GET /api/v1/materials/{id}/details
```
**Auth:** Not Required  
**Description:** Get complete material details with mills, finishes, and thickness types

---

## 4. Dealer APIs

### Complete Dealer Profile
```
POST /api/v1/dealer/profile/complete
```
**Auth:** Required  
**Description:** Complete dealer profile with materials, mills, finishes, and locations  
**Request Format:** See [API_REQUEST_FORMATS.md](./API_REQUEST_FORMATS.md)

### Add Mill/Brand Manually
```
POST /api/v1/dealer/mill/add
```
**Auth:** Required  
**Description:** Manually add a mill/brand for dealer registration  
**Request Format:** See [API_REQUEST_FORMATS.md](./API_REQUEST_FORMATS.md)

### Add Finish Manually
```
POST /api/v1/dealer/finish/add
```
**Auth:** Required  
**Description:** Manually add a finish if not available in the list  
**Request Format:** See [API_REQUEST_FORMATS.md](./API_REQUEST_FORMATS.md)

### Get Dealer Dashboard
```
GET /api/v1/dealer/dashboard
```
**Auth:** Required  
**Description:** Get dealer dashboard statistics

### Get Opportunities
```
GET /api/v1/dealer/opportunities
```
**Auth:** Required  
**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page

### Get Opportunity Details
```
GET /api/v1/dealer/opportunity/{inquiry_id}
```
**Auth:** Required  
**Description:** Get detailed information about a specific opportunity

### Accept Opportunity
```
POST /api/v1/dealer/opportunity/{id}/accept
```
**Auth:** Required  
**Description:** Accept an opportunity/inquiry

### Decline Opportunity
```
POST /api/v1/dealer/opportunity/{id}/decline
```
**Auth:** Required  
**Description:** Decline an opportunity/inquiry

### Get Session Details
```
GET /api/v1/dealer/session/{session_id}
```
**Auth:** Required  
**Description:** Get details of a matching session

### Get Session History
```
GET /api/v1/dealer/history
```
**Auth:** Required  
**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page

### Get Chat Messages
```
GET /api/v1/dealer/chat/{session_id}
```
**Auth:** Required  
**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page

### Send Chat Message
```
POST /api/v1/dealer/chat/{session_id}/message
```
**Auth:** Required  
**Description:** Send a message in a chat session

### Submit Quotation
```
POST /api/v1/dealer/quote/submit/{inquiry_id}
```
**Auth:** Required  
**Description:** Submit a quotation for an inquiry

### Get Notifications
```
GET /api/v1/dealer/notifications
```
**Auth:** Required  
**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page
- `read` (optional): Filter by read status (true/false)

### Mark Notification as Read
```
POST /api/v1/dealer/notification/{id}/read
```
**Auth:** Required  
**Description:** Mark a specific notification as read

### Mark All Notifications as Read
```
POST /api/v1/dealer/notifications/read-all
```
**Auth:** Required  
**Description:** Mark all notifications as read

---

## 5. Machine Dealer APIs

### Complete Machine Dealer Profile
```
POST /api/v1/machine-dealer/profile/complete
```
**Auth:** Required  
**Description:** Complete machine dealer profile  
**Request Format:** See [API_REQUEST_FORMATS.md](./API_REQUEST_FORMATS.md)

### Get Machine Dealer Dashboard
```
GET /api/v1/machine-dealer/dashboard
```
**Auth:** Required  
**Description:** Get machine dealer dashboard statistics

### Post Machine
```
POST /api/v1/machine-dealer/machine/post
```
**Auth:** Required  
**Description:** Post a machine for sale/buy

### Get Active Listings
```
GET /api/v1/machine-dealer/listings
```
**Auth:** Required  
**Query Parameters:**
- `per_page` (optional): Items per page (default: 15)

### Get Active Requirements
```
GET /api/v1/machine-dealer/requirements
```
**Auth:** Required  
**Query Parameters:**
- `intent` (optional): Filter by intent (BUY/SELL)
- `urgency` (optional): Filter by urgency
- `machine_id` (optional): Filter by machine ID
- `per_page` (optional): Items per page (default: 15)

---

## 6. Converter APIs

### Complete Converter Profile
```
POST /api/v1/converter/profile/complete
```
**Auth:** Required  
**Description:** Complete converter profile  
**Request Format:** See [API_REQUEST_FORMATS.md](./API_REQUEST_FORMATS.md)

### Get Converter Dashboard
```
GET /api/v1/converter/dashboard
```
**Auth:** Required  
**Description:** Get converter dashboard statistics

---

## 7. Brand APIs

### Complete Brand Profile
```
POST /api/v1/brand/profile/complete
```
**Auth:** Required  
**Description:** Complete brand profile  
**Request Format:** See [API_REQUEST_FORMATS.md](./API_REQUEST_FORMATS.md)

### Get Brand Dashboard
```
GET /api/v1/brand/dashboard
```
**Auth:** Required  
**Description:** Get brand dashboard statistics

---

## 8. Common APIs

### Unified Dashboard
```
GET /api/v1/dashboard
```
**Auth:** Required  
**Description:** Get unified dashboard for current user role

---

## Quick Reference Table

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/auth/otp/request` | No | Request OTP |
| POST | `/auth/otp/verify` | No | Verify OTP |
| GET | `/user/profile` | Yes | Get user profile |
| POST | `/user/profile` | Yes | Update user profile |
| POST | `/user/switch-role` | Yes | Switch role |
| GET | `/materials` | No | Get materials |
| GET | `/machines` | No | Get machines |
| GET | `/material-finishes` | No | Get finishes |
| GET | `/material-mills` | No | Get mills |
| GET | `/brands` | No | Get mill brands |
| GET | `/materials/{id}/details` | No | Get material details |
| POST | `/dealer/mill/add` | Yes | Add mill manually |
| POST | `/dealer/finish/add` | Yes | Add finish manually |
| POST | `/dealer/profile/complete` | Yes | Complete dealer profile |
| GET | `/dealer/dashboard` | Yes | Dealer dashboard |
| GET | `/dealer/opportunities` | Yes | Get opportunities |
| GET | `/dealer/opportunity/{id}` | Yes | Get opportunity details |
| POST | `/dealer/opportunity/{id}/accept` | Yes | Accept opportunity |
| POST | `/dealer/opportunity/{id}/decline` | Yes | Decline opportunity |
| GET | `/dealer/session/{id}` | Yes | Get session details |
| GET | `/dealer/history` | Yes | Get session history |
| GET | `/dealer/chat/{id}` | Yes | Get chat messages |
| POST | `/dealer/chat/{id}/message` | Yes | Send message |
| POST | `/dealer/quote/submit/{id}` | Yes | Submit quotation |
| GET | `/dealer/notifications` | Yes | Get notifications |
| POST | `/dealer/notification/{id}/read` | Yes | Mark notification read |
| POST | `/dealer/notifications/read-all` | Yes | Mark all read |
| POST | `/machine-dealer/profile/complete` | Yes | Complete machine dealer profile |
| GET | `/machine-dealer/dashboard` | Yes | Machine dealer dashboard |
| POST | `/machine-dealer/machine/post` | Yes | Post machine |
| GET | `/machine-dealer/listings` | Yes | Get listings |
| GET | `/machine-dealer/requirements` | Yes | Get requirements |
| POST | `/converter/profile/complete` | Yes | Complete converter profile |
| GET | `/converter/dashboard` | Yes | Converter dashboard |
| POST | `/brand/profile/complete` | Yes | Complete brand profile |
| GET | `/brand/dashboard` | Yes | Brand dashboard |
| GET | `/dashboard` | Yes | Unified dashboard |

---

## Request/Response Formats

For detailed request and response formats, please refer to:
- **[API_REQUEST_FORMATS.md](./API_REQUEST_FORMATS.md)** - Complete request/response examples
- **[API_ROUTES_DOCUMENTATION.md](./API_ROUTES_DOCUMENTATION.md)** - Detailed route documentation

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

1. **Authentication**: All routes marked as "Auth: Required" need Bearer token in header
2. **Base URL**: Replace `your-domain.com` with your actual domain
3. **Pagination**: Most list endpoints support pagination with `page` and `per_page` parameters
4. **Timestamps**: All timestamps are in ISO 8601 format
5. **Currency**: Default currency is INR
6. **Coordinates**: Use decimal degrees format (latitude: -90 to 90, longitude: -180 to 180)

---

## Important Endpoints for Profile Completion

### Dealer Profile Completion Flow:
1. `GET /materials` - Get materials list
2. `GET /material-mills?material_id={id}` - Get mills for material
3. `POST /dealer/mill/add` - Add mill if not found (optional)
4. `GET /material-finishes?material_id={id}` - Get finishes for material
5. `POST /dealer/finish/add` - Add finish if not found (optional)
6. `POST /dealer/profile/complete` - Complete profile

### Machine Dealer Profile Completion:
1. `POST /machine-dealer/profile/complete` - Complete profile

### Converter Profile Completion:
1. `POST /converter/profile/complete` - Complete profile

### Brand Profile Completion:
1. `POST /brand/profile/complete` - Complete profile

---

## Support

For detailed API documentation and examples, refer to:
- `API_REQUEST_FORMATS.md` - Request/response examples
- `API_ROUTES_DOCUMENTATION.md` - Complete route documentation

