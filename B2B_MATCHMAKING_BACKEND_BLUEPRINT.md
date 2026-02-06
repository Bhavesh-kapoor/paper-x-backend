# B2B Matchmaking Platform - Complete Backend Blueprint

## Overview
This document provides a complete backend architecture for a B2B matchmaking platform with strict visibility controls. The platform connects Brands/Converters (buyers) with Dealers (sellers) through controlled matchmaking and session-based interactions.

## Core Principles

### Visibility Rules
1. **Brand/Converter CANNOT see dealers** until dealers respond
2. **Dealer CANNOT see brand identity** until session is locked
3. **Dealers NEVER see other dealers**
4. **Brand sees ONLY responding dealers**
5. **Once a session is locked**, inquiry disappears from all other dealers
6. **Chat opens ONLY after session lock**
7. **After deal failure or expiry**, visibility is revoked

## Database Schema

### Core Tables

#### `inquiries`
- Stores requirement postings from brands/converters
- Visibility fields: `is_visible_to_dealers`, `hide_brand_identity`, `hide_exact_location`
- State tracking: `posted_at`, `locked_at`, `expires_at`, `cooldown_until`
- Status enum: DRAFT, POSTED, MATCHING, RESPONSES_RECEIVED, LOCKED, CHAT_ACTIVE, DEAL_SUCCESS, DEAL_FAILED, EXPIRED, REPUBLISHED

#### `inquiry_items`
- Stores individual items within an inquiry (for multi-item requirements)
- Contains material specs, thickness, finish, quantity
- Tolerance settings for matching

#### `matchmaking_logs`
- Tracks which dealers were matched to which inquiries
- Stores match scores and criteria
- Visibility tracking: `is_visible`, `visible_to_dealer_at`, `hidden_from_dealer_at`
- Selection tracking: `is_selected`, `selected_at`

#### `matching_sessions`
- Manages the lifecycle of a matchmaking session
- Links inquiry to responses and participants
- Visibility flags: `is_visible_to_dealers`, `chat_enabled`, `full_specs_visible`, `brand_identity_visible`
- Status enum: DRAFT, POSTED, MATCHING, RESPONSES_RECEIVED, LOCKED, CHAT_ACTIVE, DEAL_SUCCESS, DEAL_FAILED, EXPIRED, REPUBLISHED

#### `session_participants`
- Tracks who can see and interact with a session
- Roles: `poster` (brand/converter) or `responder` (dealer)
- Permissions: `can_see_full_specs`, `can_see_exact_location`, `can_see_brand_identity`, `can_chat`
- Status: `invited`, `active`, `declined`, `removed`

#### `responses`
- Dealer responses to inquiries
- Linked to both inquiry and session
- Status tracking

#### `chat_threads`
- One-to-one or one-to-few chat threads
- Linked to session
- Read-only flag after deal failure/expiry

## API Endpoints

### Inquiry Management

#### `POST /api/v1/inquiries`
**Purpose**: Create a new inquiry (DRAFT status)
**Auth**: Brand/Converter only
**Request Body**:
```json
{
  "title": "Corrugated Boxes Required",
  "description": "Need 50k units",
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
  "timeline": "3-5 Days"
}
```
**Response**: Inquiry object with DRAFT status

#### `POST /api/v1/inquiries/{id}/post`
**Purpose**: Post inquiry (change from DRAFT to POSTED, trigger matchmaking)
**Auth**: Brand/Converter (poster only)
**Response**: Inquiry object with POSTED status, matched dealers count

#### `GET /api/v1/inquiries/{id}`
**Purpose**: Get inquiry details
**Auth**: Brand/Converter (own inquiries) or Dealer (matched inquiries)
**Visibility**:
- Brand sees full details
- Dealer sees sanitized view (no brand identity, approximate location)

#### `GET /api/v1/dealer/inquiries`
**Purpose**: Get inquiries visible to dealer (matched only)
**Auth**: Dealer only
**Query Params**: `status`, `material_category`, `location`
**Response**: Array of inquiries (sanitized for dealer view)

#### `GET /api/v1/inquiries/{id}/responses`
**Purpose**: Get responses for an inquiry
**Auth**: Brand/Converter (poster only)
**Response**: Array of responses with dealer info (anonymized until lock)

### Response Management

#### `POST /api/v1/dealer/inquiries/{id}/respond`
**Purpose**: Dealer responds to an inquiry
**Auth**: Dealer only
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

### Session Management

#### `POST /api/v1/sessions/{id}/lock`
**Purpose**: Lock session and select dealers
**Auth**: Brand/Converter (poster only)
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

#### `GET /api/v1/sessions/active`
**Purpose**: Get active sessions for user
**Auth**: Authenticated
**Response**: Array of active sessions

#### `GET /api/v1/sessions/history`
**Purpose**: Get session history
**Auth**: Authenticated
**Query Params**: `status`, `limit`, `offset`
**Response**: Paginated session history

#### `POST /api/v1/sessions/{id}/republish`
**Purpose**: Republish expired/failed session
**Auth**: Brand/Converter (poster only)
**Checks**: Cooldown period expired
**Response**: New inquiry with REPUBLISHED status

#### `POST /api/v1/sessions/{id}/deal-failed`
**Purpose**: Mark deal as failed
**Auth**: Session participants
**Response**: Updated session with DEAL_FAILED status

### Chat Management

#### `GET /api/v1/sessions/{id}/chat`
**Purpose**: Get chat thread for session
**Auth**: Session participants with chat permission
**Response**: Chat thread with messages

#### `POST /api/v1/sessions/{id}/chat/message`
**Purpose**: Send message in chat
**Auth**: Session participants with chat permission
**Request Body**:
```json
{
  "message": "Hello, I have attached the specifications",
  "attachments": ["file1.pdf"]
}
```

## Matchmaking Logic

### Matching Criteria

1. **Material Match** (Category-based)
   - Check if dealer handles the material category
   - Score: 30 points for exact match, 25 for category match

2. **Finish/Coating Match**
   - If specified, check dealer capabilities
   - Score: 15 points

3. **Thickness Tolerance**
   - Normal inquiry: GSM ±5%, MM ±0.2
   - Urgent inquiry: GSM ±10%, MM ±0.3
   - Score: 25 points

4. **Location Priority**
   - Nearby dealers prioritized (within 100km)
   - Score: Up to 30 points (closer = higher)

5. **Priority Bonuses**
   - Authorized mill agent: +10
   - Faster response history: +5
   - Higher deal success rate: +5

### Matching Process

1. Inquiry posted → Status: POSTED
2. MatchmakingService finds top 10 dealers
3. Create MatchmakingLog entries with `is_visible = true`
4. Update inquiry: `is_visible_to_dealers = true`, `matched_dealers_count = 10`
5. Notify matched dealers
6. Status: MATCHING

### Response Process

1. Dealer responds → Create Response
2. Update MatchmakingLog: `responded_at`, `response_id`
3. Update inquiry: `responses_count++`
4. If first response: Status → RESPONSES_RECEIVED, `responses_received_at = now()`

### Lock Process

1. Brand selects dealers → POST /sessions/{id}/lock
2. Update session: Status → LOCKED, `locked_at = now()`, `chat_enabled = true`
3. Update inquiry: Status → LOCKED, `locked_at = now()`
4. Hide from non-selected: Update MatchmakingLog for non-selected dealers (`is_visible = false`)
5. Create SessionParticipant entries for selected dealers with:
   - `can_see_full_specs = true`
   - `can_see_exact_location = true`
   - `can_see_brand_identity = true`
   - `can_chat = true`
6. Create ChatThread for session
7. Notify selected dealers (brand identity revealed)

## Authorization Policies

### InquiryPolicy
- `view`: Brand sees own, Dealer sees matched and visible
- `create`: Brand/Converter only
- `post`: Poster only, DRAFT status
- `viewResponses`: Poster only
- `republish`: Poster only, cooldown expired

### SessionPolicy
- `view`: Participants only
- `lock`: Poster only, RESPONSES_RECEIVED status
- `chat`: Participants with chat permission, LOCKED/CHAT_ACTIVE status
- `republish`: Poster only, cooldown expired

### DealerResponsePolicy
- `respond`: Dealer only, matched and visible, not already responded
- `view`: Poster or own response
- `update`: Own response, not locked

## State Transitions

### Inquiry States
```
DRAFT → POSTED → MATCHING → RESPONSES_RECEIVED → LOCKED → CHAT_ACTIVE → DEAL_SUCCESS/DEAL_FAILED/EXPIRED
                                                                              ↓
                                                                        REPUBLISHED (after cooldown)
```

### Session States
```
DRAFT → POSTED → MATCHING → RESPONSES_RECEIVED → LOCKED → CHAT_ACTIVE → DEAL_SUCCESS/DEAL_FAILED/EXPIRED
                                                                              ↓
                                                                        REPUBLISHED (after cooldown)
```

## Real-time Triggers

### When Inquiry Posted
- Trigger matchmaking
- Create MatchmakingLog entries
- Notify matched dealers

### When Dealer Responds
- Update inquiry response count
- If first response: Change status to RESPONSES_RECEIVED
- Notify brand

### When Session Locked
- Hide inquiry from non-selected dealers
- Reveal brand identity to selected dealers
- Enable chat
- Create chat thread

### When Deal Fails/Expires
- Archive session
- Make chat read-only
- Set cooldown period (e.g., 7 days)
- Allow republish after cooldown

## Example API Responses

### Dealer Inquiry Feed
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Corrugated Boxes Required",
      "material_category": "Corrugated",
      "quantity": 50000,
      "quantity_unit": "units",
      "location": "Mumbai", // Approximate only
      "urgency": "normal",
      "timeline": "3-5 Days",
      "posted_at": "2026-01-20T10:00:00Z",
      "responses_count": 3,
      "deadline": "2026-01-25T10:00:00Z"
      // No brand_id, no exact coordinates
    }
  ]
}
```

### Brand Response List
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

### Locked Session Payload
```json
{
  "success": true,
  "data": {
    "id": 1,
    "inquiry": {
      "id": 1,
      "title": "Corrugated Boxes Required",
      "full_specs": {...},
      "exact_location": {...}
    },
    "selected_dealers": [
      {
        "id": 5,
        "company_name": "Global Packaging Solutions",
        "contact_details": {...}
      }
    ],
    "chat_enabled": true,
    "chat_thread_id": 1,
    "locked_at": "2026-01-20T12:00:00Z"
  }
}
```

## Security Considerations

1. **No Global Search**: Dealers cannot browse all inquiries
2. **No Public Profiles**: Profiles only visible after selection
3. **Strict Visibility**: Database-level visibility flags
4. **Policy Enforcement**: All endpoints protected by policies
5. **Audit Trail**: MatchmakingLog tracks all visibility changes

## Performance Considerations

1. **Indexes**: On `is_visible_to_dealers`, `expires_at`, `cooldown_until`
2. **Caching**: Matchmaking results cached for 1 hour
3. **Pagination**: All list endpoints paginated
4. **Eager Loading**: Relationships loaded efficiently

## Testing Checklist

- [ ] Inquiry creation (DRAFT)
- [ ] Inquiry posting triggers matchmaking
- [ ] Dealer sees only matched inquiries
- [ ] Dealer cannot see brand identity before lock
- [ ] Response creation
- [ ] Session locking hides from non-selected
- [ ] Chat opens only after lock
- [ ] Republish after cooldown
- [ ] Visibility scopes work correctly
- [ ] Policies enforce access control



