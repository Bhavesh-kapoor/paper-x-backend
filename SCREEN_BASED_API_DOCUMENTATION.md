# Screen-Based API Documentation

This document maps all API endpoints to the mobile app screens shown in the design.

## Screen Flow Overview

1. **Post Requirement Screen** (Step 2 of 9) → Save inquiry step
2. **Payment Confirmation Screen** → Calculate fee → Post inquiry
3. **Posting Success Screen** → Get posting status
4. **Active Sessions (Sourcing Hub)** → Get active sessions with filters
5. **Matchmaking Responses** → Get responses with filters → Shortlist/Reject
6. **Session Locked** → Lock session → Get session details
7. **Partner Chat** → Chat endpoints (existing)
8. **Session History** → Get history with filters → Republish

---

## 1. Post Requirement Screen (Step 2 of 9)

### Save Inquiry Step
```http
POST /api/v1/inquiries/save-step
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "step": 2,
  "inquiry_id": null, // null for new inquiry, ID for existing
  "material_category": "Corrugated",
  "packaging_type": "Boxes",
  "thickness_gsm": 150,
  "thickness_unit": "gsm",
  "size": "10x12 inches",
  "quantity": 5000,
  "quantity_unit": "units",
  "urgency": "normal",
  "location": "Mumbai, Maharashtra",
  "latitude": 19.0760,
  "longitude": 72.8777
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "inquiry_id": 1,
    "step": 2,
    "inquiry": {...}
  }
}
```

---

## 2. Payment Confirmation Screen

### Calculate Posting Fee
```http
POST /api/v1/inquiries/calculate-fee
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "inquiry_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "standard_fee": 50,
    "urgency_addon": 20,
    "total_fee": 70,
    "wallet_balance": 150,
    "sufficient_credits": true,
    "inquiry": {
      "id": 1,
      "title": "Corrugated Boxes Required",
      "urgency": "urgent"
    }
  }
}
```

### Post Inquiry (Pay & Post)
```http
POST /api/v1/inquiries/{id}/post
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "inquiry": {...},
    "matched_dealers_count": 10
  }
}
```

---

## 3. Posting Success Screen

### Get Posting Status
```http
GET /api/v1/inquiries/{id}/posting-status
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "inquiry": {
      "id": 1,
      "title": "Corrugated Boxes",
      "status": "MATCHING",
      "posted_at": "2026-01-20T10:00:00Z",
      "items": [
        {
          "material_category": "Corrugated",
          "quantity": 5000,
          "quantity_unit": "units"
        }
      ]
    },
    "matchmaking": {
      "status": "MATCHING",
      "matched_dealers_count": 5,
      "total_dealers_scanned": 100,
      "progress_percent": 5.0,
      "is_finding_matches": true
    },
    "session_id": 1
  }
}
```

---

## 4. Active Sessions (Sourcing Hub)

### Get Active Sessions
```http
GET /api/v1/sessions/active?filter=active&per_page=15
Authorization: Bearer {token}
```

**Query Parameters:**
- `filter`: `all`, `finding_matches`, `active`, `locked`
- `per_page`: Items per page (default: 15)
- `page`: Page number

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "inquiry_id": 1,
        "title": "50k Units Sustainable Cardboard",
        "status": "RESPONSES_RECEIVED",
        "status_label": "ACTIVE",
        "urgency": "urgent",
        "created_at": "2026-01-24T10:00:00Z",
        "items": [...],
        "countdown": {
          "days": 2,
          "hours": 4,
          "minutes": 15,
          "seconds": 30,
          "formatted": "02 DAYS 04 HOURS 15 MINS 30 SECS"
        },
        "responses_received": 8,
        "matched_dealers_count": 10,
        "matching_progress": null
      },
      {
        "id": 2,
        "title": "Aluminum Foil Roll - 200m",
        "status": "MATCHING",
        "status_label": "FINDING",
        "matching_progress": {
          "matched": 2,
          "total": 15,
          "status": "Scanning suppliers..."
        }
      }
    ],
    "pagination": {...}
  }
}
```

---

## 5. Matchmaking Responses Screen

### Get Matchmaking Responses
```http
GET /api/v1/inquiries/{id}/matchmaking-responses?filter=all
Authorization: Bearer {token}
```

**Query Parameters:**
- `filter`: `all`, `exact_match`, `slight_variation`, `nearest`

**Response:**
```json
{
  "success": true,
  "data": {
    "inquiry": {
      "id": 1,
      "title": "Recycled PET Pellets - 50 Tons",
      "items": [...]
    },
    "countdown": {
      "hours": 4,
      "minutes": 12,
      "seconds": 30
    },
    "responses_count": 8,
    "responses": [
      {
        "id": 1,
        "match_type": "exact_match",
        "distance_km": 45,
        "dealer": {
          "id": null, // Anonymized until lock
          "company_name": null,
          "location": "Warsaw, Poland"
        },
        "quantity_offered": 50000,
        "quoted_price": 250000,
        "price_status": "firm",
        "additional_details": "We have 50 tons of Grade A recycled PET ready for immediate shipment.",
        "responded_at": "2026-01-20T11:00:00Z",
        "is_shortlisted": false
      },
      {
        "id": 2,
        "match_type": "slight_variation",
        "distance_km": 112,
        "dealer": {
          "id": null,
          "company_name": null,
          "location": "Berlin, Germany"
        },
        "quantity_offered": 42000,
        "quoted_price": 240000,
        "price_status": "negotiable",
        "additional_details": "Available stock: 42 tons. Can provide remaining 8 tons within 10 days.",
        "responded_at": "2026-01-20T11:15:00Z",
        "is_shortlisted": false
      }
    ],
    "filter": "all"
  }
}
```

### Shortlist Response
```http
POST /api/v1/inquiries/responses/{response_id}/shortlist
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "action": "shortlist" // or "reject"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "response_id": 1,
    "action": "shortlist",
    "is_shortlisted": true
  }
}
```

---

## 6. Session Locked Screen

### Lock Session (Select Dealers)
```http
POST /api/v1/sessions/{id}/lock
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "selected_dealer_ids": [1, 2]
}
```

**Response:**
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

### Get Session Details (Locked)
```http
GET /api/v1/sessions/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "project_id": "PRJ-0001",
    "status": "LOCKED",
    "inquiry": {
      "id": 1,
      "title": "Recycled PET Pellets - 50 Tons",
      "items": [...]
    },
    "selected_partners_count": 2,
    "selected_partners": [
      {
        "id": 5,
        "company_name": "EcoPack Solutions",
        "location": "Mumbai, Maharashtra"
      },
      {
        "id": 8,
        "company_name": "Global Fiber Co.",
        "location": "Delhi, NCR"
      }
    ],
    "chat_enabled": true,
    "chat_thread_id": 1,
    "locked_at": "2026-01-20T12:00:00Z"
  }
}
```

---

## 7. Partner Chat Screen

### Get Chat Messages
```http
GET /api/v1/sessions/{id}/chat
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "thread_id": 1,
    "messages": [
      {
        "id": 1,
        "sender": {
          "id": 5,
          "name": "EcoPack Solutions"
        },
        "message": "Hello, I have attached the material specifications...",
        "attachments": [
          {
            "name": "Material_Specs.pdf",
            "size": 2048000,
            "type": "pdf"
          }
        ],
        "created_at": "2026-01-20T10:45:00Z"
      }
    ]
  }
}
```

### Send Message
```http
POST /api/v1/sessions/{id}/chat/message
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "message": "Received, thank you. Checking the tensile strength requirements now.",
  "attachments": []
}
```

---

## 8. Session History Screen

### Get Session History
```http
GET /api/v1/sessions/history?filter=all&search=corrugated&per_page=15
Authorization: Bearer {token}
```

**Query Parameters:**
- `filter`: `all`, `completed`, `expired`
- `search`: Search materials or sessions
- `per_page`: Items per page
- `page`: Page number

**Response:**
```json
{
  "success": true,
  "data": {
    "sessions": [
      {
        "month": "October 2023",
        "sessions": [
          {
            "id": 1,
            "inquiry_id": 10,
            "title": "Corrugated Box Batch #402",
            "status": "DEAL_SUCCESS",
            "status_label": "COMPLETED",
            "partners_matched": 5,
            "quantity": 10000,
            "quantity_unit": "units",
            "created_at": "2023-10-12T10:00:00Z",
            "can_republish": true
          },
          {
            "id": 2,
            "title": "Eco-friendly Shrink Wrap",
            "status": "DEAL_SUCCESS",
            "status_label": "COMPLETED",
            "partners_matched": 3,
            "quantity": 5000,
            "quantity_unit": "units",
            "created_at": "2023-10-08T10:00:00Z",
            "can_republish": true
          }
        ]
      },
      {
        "month": "September 2023",
        "sessions": [
          {
            "id": 3,
            "title": "Bio-Plastic Pellet Sourcing",
            "status": "EXPIRED",
            "status_label": "EXPIRED",
            "partners_matched": 6,
            "quantity": 50,
            "quantity_unit": "tons",
            "created_at": "2023-09-25T10:00:00Z",
            "can_republish": true
          }
        ]
      }
    ],
    "pagination": {
      "current_page": 1,
      "total": 20,
      "per_page": 15,
      "last_page": 2
    }
  }
}
```

### Republish Session
```http
POST /api/v1/sessions/{id}/republish
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 11,
    "status": "REPUBLISHED",
    "inquiry": {...}
  }
}
```

---

## Complete API Route List

### Inquiry Endpoints
- `POST /api/v1/inquiries` - Create inquiry
- `POST /api/v1/inquiries/save-step` - Save inquiry step (multi-step)
- `POST /api/v1/inquiries/calculate-fee` - Calculate posting fee
- `POST /api/v1/inquiries/{id}/post` - Post inquiry
- `GET /api/v1/inquiries/{id}/posting-status` - Get posting/matchmaking status
- `GET /api/v1/inquiries/{id}` - Get inquiry details
- `GET /api/v1/inquiries/{id}/responses` - Get responses
- `GET /api/v1/inquiries/{id}/matchmaking-responses` - Get matchmaking responses with filters
- `POST /api/v1/inquiries/responses/{id}/shortlist` - Shortlist/Reject response
- `POST /api/v1/inquiries/{id}/republish` - Republish inquiry

### Session Endpoints
- `GET /api/v1/sessions/active` - Get active sessions (Sourcing Hub)
- `GET /api/v1/sessions/{id}` - Get session details
- `POST /api/v1/sessions/{id}/lock` - Lock session
- `GET /api/v1/sessions/history` - Get session history
- `POST /api/v1/sessions/{id}/republish` - Republish session
- `POST /api/v1/sessions/{id}/deal-failed` - Mark deal as failed

### Chat Endpoints
- `GET /api/v1/sessions/{id}/chat` - Get chat messages
- `POST /api/v1/sessions/{id}/chat/message` - Send message

---

## Postman Collection

Import `B2B_MATCHMAKING_POSTMAN_COLLECTION.json` for complete API testing.

---

## Related Documentation

- [B2B Matchmaking Backend Blueprint](./B2B_MATCHMAKING_BACKEND_BLUEPRINT.md)
- [B2B Matchmaking API Documentation](./B2B_MATCHMAKING_API_DOCUMENTATION.md)
- [Main Documentation Index](./DOCS.md)



