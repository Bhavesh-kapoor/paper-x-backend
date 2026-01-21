# B2B Matchmaking Platform - Complete API Documentation

## Overview
This document provides complete API documentation for the B2B matchmaking platform with strict visibility controls. The platform connects Brands/Converters (buyers) with Dealers (sellers) through controlled matchmaking and session-based interactions.

**Base URL**: `http://127.0.0.1:8000/api/v1`

## Authentication
All endpoints (except authentication) require Bearer token authentication:
```
Authorization: Bearer {token}
```

## Core Visibility Principles

1. **Brand/Converter CANNOT see dealers** until dealers respond
2. **Dealer CANNOT see brand identity** until session is locked
3. **Dealers NEVER see other dealers**
4. **Brand sees ONLY responding dealers**
5. **Once a session is locked**, inquiry disappears from all other dealers
6. **Chat opens ONLY after session lock**
7. **After deal failure or expiry**, visibility is revoked

---

## API Endpoints

### Authentication

#### Request OTP
```http
POST /auth/otp/request
```

**Request Body**:
```json
{
  "mobile": "9876543210"
}
```

**Response**:
```json
{
  "success": true,
  "message": "OTP sent successfully"
}
```

#### Verify OTP
```http
POST /auth/otp/verify
```

**Request Body**:
```json
{
  "mobile": "9876543210",
  "otp": "123456"
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "token": "1|abc123...",
    "user": {...}
  }
}
```

---

### Inquiry Management

#### Create Inquiry (DRAFT)
```http
POST /inquiries
```

**Auth**: Brand/Converter only

**Request Body**:
```json
{
  "title": "Corrugated Boxes Required",
  "description": "Need 50k units of corrugated boxes",
  "items": [
    {
      "material_id": 1,
      "material_category": "Corrugated",
      "finish_coating": "None",
      "thickness_gsm": 150,
      "thickness_unit": "gsm",
      "quantity": 50000,
      "quantity_unit": "units"
    }
  ],
  "urgency": "normal",
  "location": "Mumbai, Maharashtra",
  "latitude": 19.0760,
  "longitude": 72.8777,
  "timeline": "3-5 Days",
  "deadline": "2026-01-25T10:00:00Z"
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Corrugated Boxes Required",
    "status": "DRAFT",
    "items": [...]
  }
}
```

#### Post Inquiry (Trigger Matchmaking)
```http
POST /inquiries/{id}/post
```

**Auth**: Brand/Converter (poster only)

**Actions**:
- Deducts posting fee from wallet (50 credits normal, 70 urgent)
- Changes status from DRAFT to POSTED
- Triggers matchmaking (finds top 10 dealers)
- Creates MatchmakingLog entries
- Notifies matched dealers

**Response**:
```json
{
  "success": true,
  "data": {
    "inquiry": {...},
    "matched_dealers_count": 10
  }
}
```

#### Get Inquiry Details
```http
GET /inquiries/{id}
```

**Auth**: Brand/Converter (own inquiries) or Dealer (matched inquiries)

**Visibility**:
- **Brand/Converter**: Sees full details
- **Dealer**: Sees sanitized view (no brand identity, approximate location only)

**Response (Dealer View)**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Corrugated Boxes Required",
    "material_category": "Corrugated",
    "quantity": 50000,
    "location": "Mumbai", // Approximate only
    "urgency": "normal",
    // No brand_id, no exact coordinates
  }
}
```

#### Get Responses (Brand/Converter Only)
```http
GET /inquiries/{id}/responses
```

**Auth**: Brand/Converter (poster only)

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "responder": {
        "id": null, // Anonymized until lock
        "company_name": null,
        "location": "Mumbai, Maharashtra"
      },
      "quantity_offered": 50000,
      "quoted_price": 250000,
      "price_status": "firm",
      "responded_at": "2026-01-20T11:00:00Z"
    }
  ]
}
```

#### Republish Inquiry
```http
POST /inquiries/{id}/republish
```

**Auth**: Brand/Converter (poster only)

**Requirements**:
- Cooldown period expired (default: 7 days)
- Status must be DEAL_FAILED, EXPIRED, or DEAL_SUCCESS

**Response**: New inquiry with REPUBLISHED status

---

### Dealer Inquiry Management

#### Get Matched Inquiries
```http
GET /dealer/inquiries
```

**Auth**: Dealer only

**Query Parameters**:
- `status`: Filter by status (MATCHING, RESPONSES_RECEIVED, etc.)
- `material_category`: Filter by material category
- `location`: Filter by location
- `per_page`: Items per page (default: 15)
- `page`: Page number

**Response**: Paginated list of inquiries (sanitized for dealer view)

#### Respond to Inquiry
```http
POST /dealer/inquiries/{id}/respond
```

**Auth**: Dealer only

**Requirements**:
- Dealer must be matched (visible in MatchmakingLog)
- Inquiry must be visible to dealers
- Dealer must not have already responded

**Request Body**:
```json
{
  "quantity_offered": 50000,
  "quantity_unit": "units",
  "quoted_price": 250000,
  "price_unit": "INR",
  "price_status": "firm",
  "additional_details": "Ready for immediate dispatch"
}
```

**Response**: Response object

---

### Session Management

#### Get Active Sessions
```http
GET /sessions/active
```

**Auth**: Authenticated

**Response**: Paginated list of active sessions (LOCKED, CHAT_ACTIVE, MATCHING, RESPONSES_RECEIVED)

#### Get Session Details
```http
GET /sessions/{id}
```

**Auth**: Session participants only

**Response**: Session with inquiry, participants, and chat thread

#### Lock Session (Select Dealers)
```http
POST /sessions/{id}/lock
```

**Auth**: Brand/Converter (poster only)

**Requirements**:
- Status must be RESPONSES_RECEIVED
- Must have at least one response

**Request Body**:
```json
{
  "selected_dealer_ids": [1, 2, 3]
}
```

**Actions**:
- Updates session status to LOCKED
- Hides inquiry from non-selected dealers
- Enables chat for selected participants
- Reveals brand identity to selected dealers
- Creates ChatThread

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "LOCKED",
    "inquiry": {...},
    "selected_dealers": [...],
    "chat_enabled": true,
    "chat_thread_id": 1
  }
}
```

#### Get Session History
```http
GET /sessions/history
```

**Auth**: Authenticated

**Query Parameters**:
- `status`: Filter by status
- `per_page`: Items per page
- `page`: Page number

**Response**: Paginated session history

#### Republish Session
```http
POST /sessions/{id}/republish
```

**Auth**: Brand/Converter (poster only)

**Requirements**: Cooldown period expired

**Response**: New inquiry with REPUBLISHED status

#### Mark Deal as Failed
```http
POST /sessions/{id}/deal-failed
```

**Auth**: Session participants

**Actions**:
- Updates status to DEAL_FAILED
- Sets cooldown period (7 days)
- Makes chat read-only
- Archives session

---

## Matchmaking Logic

### Matching Criteria

1. **Material Match** (Category-based)
   - Score: 30 points (exact match), 25 points (category match)

2. **Finish/Coating Match**
   - Score: 15 points

3. **Thickness Tolerance**
   - Normal: GSM ±5%, MM ±0.2
   - Urgent: GSM ±10%, MM ±0.3
   - Score: 25 points

4. **Location Priority**
   - Within 100km prioritized
   - Score: Up to 30 points (closer = higher)

5. **Priority Bonuses**
   - Authorized mill agent: +10
   - Faster response history: +5
   - Higher deal success rate: +5

### State Transitions

**Inquiry States**:
```
DRAFT → POSTED → MATCHING → RESPONSES_RECEIVED → LOCKED → CHAT_ACTIVE → DEAL_SUCCESS/DEAL_FAILED/EXPIRED
                                                                              ↓
                                                                        REPUBLISHED (after cooldown)
```

**Session States**:
```
DRAFT → POSTED → MATCHING → RESPONSES_RECEIVED → LOCKED → CHAT_ACTIVE → DEAL_SUCCESS/DEAL_FAILED/EXPIRED
                                                                              ↓
                                                                        REPUBLISHED (after cooldown)
```

---

## Error Responses

All errors follow this format:
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["Validation error"]
  }
}
```

**Common HTTP Status Codes**:
- `200`: Success
- `201`: Created
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `422`: Validation Error
- `500`: Internal Server Error

---

## Postman Collection

Import the Postman collection from: `B2B_MATCHMAKING_POSTMAN_COLLECTION.json`

**Environment Variables**:
- `base_url`: `http://127.0.0.1:8000`
- `token`: Bearer token (set after login)

---

## Related Documentation

- [Backend Blueprint](./B2B_MATCHMAKING_BACKEND_BLUEPRINT.md) - Complete backend architecture
- [Main Documentation Index](./DOCS.md) - All documentation links

---

## Support

For issues or questions, contact the development team.


