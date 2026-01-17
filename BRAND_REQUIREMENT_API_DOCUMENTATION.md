# Brand Requirement Posting & Converter Response API Documentation

## Overview
This document describes the API endpoints for brand requirement posting and converter response system.

## Brand Registration Fields
- Company / Brand Name
- Brand type(s) - dropdown select multiple
- Contact Person Name
- Mobile Number / Email
- GST (optional)
- City / Location

## Brand Dashboard
The brand dashboard shows ONLY:
- Post New Requirement
- My Posted Inquiries
- Messages (only for active inquiries)

No feed, no listings, no marketplace browsing.

## Brand Requirement Posting Flow

### Step 1: What do you need?
Options:
- Packaging
- Printing
- Packaging + Printing
- Corporate Gifting / Stationery

### Step 2: Packaging Type (conditional)
Shown only when requirement_type includes "Packaging"

### Step 3: Quantity Range
In pieces (e.g., "1000-5000", "5000-10000")

### Step 4: Timeline
- Emergency (Urgent)
- 3–5 Days
- Flexible

### Step 5: Special Needs
Optional text box for any special requirements

### Step 6: Upload photos/videos/design ideas
All optional - array of file paths

### Step 7: Pay charges to post
Deducts credits from wallet (default: 50 credits)

## API Endpoints

### Brand Endpoints

#### 1. Post Requirement
**POST** `/api/v1/brand/requirement/post`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "requirement_type": "Packaging", // Required: Packaging, Printing, Packaging + Printing, Corporate Gifting / Stationery
  "packaging_type": "Boxes", // Required if requirement_type includes Packaging
  "quantity_range": "1000-5000", // Required: Range in pieces
  "timeline": "3–5 Days", // Required: Emergency (Urgent), 3–5 Days, Flexible
  "special_needs": "Need eco-friendly packaging", // Optional
  "design_attachments": ["path/to/file1.jpg", "path/to/file2.pdf"], // Optional: Array of file paths
  "title": "Need custom packaging boxes", // Required
  "description": "Looking for custom printed boxes", // Optional
  "urgency": "normal", // Required: normal, urgent
  "location": "Mumbai", // Optional
  "city": "Mumbai", // Optional
  "latitude": 19.0760, // Optional
  "longitude": 72.8777 // Optional
}
```

**Response:**
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

#### 2. Get My Inquiries
**GET** `/api/v1/brand/inquiries`

**Query Parameters:**
- `status` (optional): Filter by status
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

#### 3. Get Messages (for active inquiries)
**GET** `/api/v1/brand/messages/{session_id}`

**Response:**
Returns messages for the active session (uses ChatService)

### Converter Endpoints

#### 1. Get Brand Requirements
**GET** `/api/v1/converter/requirements`

**Query Parameters:**
- `city` (optional): Filter by city
- `requirement_type` (optional): Filter by requirement type
- `urgency` (optional): Filter by urgency
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
        "brand_name": null, // Hidden until session lock
        "brand_company_name": null // Hidden until session lock
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

#### 2. Respond to Requirement
**POST** `/api/v1/converter/requirement/{inquiry_id}/respond`

**Request Body:**
```json
{
  "quantity_offered": 5000, // Optional
  "quantity_unit": "pieces", // Optional
  "quoted_price": 50000, // Optional
  "price_unit": "per_piece", // Optional
  "price_status": "Fixed", // Optional
  "additional_details": "Can deliver within 3 days" // Optional
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

## Matchmaking Logic

When a brand posts a requirement:
1. System finds 10 best converters based on:
   - City match (40 points)
   - Capacity match (30 points)
   - Converter type match (20 points)
   - Profile completeness (10 points)

2. Matching session is created and locked immediately
3. All matched converters are notified
4. Converters can view and respond to requirements
5. Brand can see responses and communicate with converters

## Database Changes

### New Fields in `inquiries` Table:
- `requirement_type` - Type of requirement (Packaging, Printing, etc.)
- `packaging_type` - Packaging type (conditional)
- `quantity_range` - Quantity range in pieces
- `timeline` - Timeline preference
- `special_needs` - Special requirements text
- `design_attachments` - JSON array of design file paths

## Notes

1. **Posting Fee**: Default is 50 credits per requirement. Can be made configurable.
2. **Session Lock**: Sessions are locked immediately when requirement is posted (unlike dealer flow where it locks after 10 acceptances).
3. **Brand Details**: Brand name and company name are hidden from converters until session is locked.
4. **City Matching**: Converters are matched primarily by city. If not enough matches, search expands to other cities.

## Postman Collection

A complete Postman collection is available for testing all Brand and Dealer APIs:

**File:** `Paper_X_Brand_Dealer_API_Collection.postman_collection.json`

### Import Instructions

1. Open Postman
2. Click **Import** button (top left)
3. Select **File** tab
4. Choose `Paper_X_Brand_Dealer_API_Collection.postman_collection.json`
5. Click **Import**

### Collection Structure

The collection includes:

#### 1. Authentication
- Request OTP
- Verify OTP & Login (automatically saves token)

#### 2. Brand APIs
- Complete Brand Profile
- Get Brand Dashboard
- Post Requirement (7-step flow)
- Get My Inquiries
- Get Messages

#### 3. Dealer APIs
- Complete Dealer Profile
- Get Dealer Dashboard
- Post Requirement
- Get Requirements
- Get Opportunities
- Get Opportunity Details
- Accept Opportunity
- Decline Opportunity
- Get Session Details
- Get Session History
- Get Chat Messages
- Send Chat Message
- Submit Quotation
- Get Notifications

#### 4. Converter APIs
- Get Brand Requirements
- Respond to Requirement

#### 5. Reference Data
- Get Brand Types
- Get Materials
- Get Machines

### Collection Variables

The collection uses the following variables:
- `base_url`: API base URL (default: `http://localhost:8000`)
- `auth_token`: Authentication token (auto-saved after login)
- `session_id`: Session ID for testing
- `inquiry_id`: Inquiry ID for testing

### Usage

1. **Set Base URL**: Update `base_url` variable with your server URL
2. **Login**: Use "Verify OTP & Login" request - token will be auto-saved
3. **Test APIs**: All subsequent requests will use the saved token automatically

### Example Flow

1. Request OTP → Enter mobile number
2. Verify OTP & Login → Enter OTP, token saved automatically
3. Complete Brand Profile → Fill brand details
4. Post Requirement → Post a new requirement (deducts 50 credits)
5. Get My Inquiries → View posted inquiries
6. Get Messages → View messages for active sessions

For Dealers:
1. Complete Dealer Profile → Fill dealer details
2. Get Opportunities → View matched opportunities
3. Accept Opportunity → Accept an opportunity
4. Get Session Details → View session after lock
5. Send Chat Message → Communicate with brand
6. Submit Quotation → Submit quote

