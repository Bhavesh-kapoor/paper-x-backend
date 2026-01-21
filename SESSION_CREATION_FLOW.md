# Session Creation Flow Documentation

## Overview
Sessions (`MatchingSession`) are **automatically created** when an inquiry is posted. There is **NO separate API endpoint** to create a session manually. Sessions are created as part of the inquiry posting process.

---

## When Sessions Are Created

### 1. **When Posting an Inquiry** (Automatic)
**Endpoint:** `POST /api/v1/inquiries/{inquiry_id}/post`

**What Happens:**
1. User posts an inquiry (changes status from `DRAFT` to `MATCHING`)
2. System **automatically creates** a `MatchingSession` record
3. Session is created with status `ACTIVE`
4. Matchmaking starts immediately

**Code Location:** `app/Http/Controllers/api/InquiryController.php` (lines 192-200)

```php
// Create or update session (only use fields that exist in database)
$session = $inquiry->session()->firstOrCreate([
    'inquiry_id' => $inquiry->id,
], [
    'status' => 'ACTIVE',
    'locked_at' => now(),
    'expires_at' => now()->addHours(24),
    'discovery_start' => now(),
    'active_session_start' => now(),
]);
```

**Response includes session:**
```json
{
    "success": true,
    "message": "Inquiry posted successfully",
    "data": {
        "inquiry": {
            "id": 1,
            "status": "MATCHING",
            "session": {
                "id": 1,
                "status": "ACTIVE",
                "expires_at": "2026-01-22T17:00:00Z"
            }
        },
        "matched_dealers_count": 10
    }
}
```

---

### 2. **When Posting Brand Requirement** (Automatic)
**Endpoint:** `POST /api/v1/brand/requirement/post`

**What Happens:**
1. Brand posts a requirement
2. System finds matching converters
3. System **automatically creates** a `MatchingSession` record
4. Session is created with status `ACTIVE`

**Code Location:** `app/Services/BrandService.php` (lines 215-222)

```php
// Create matching session
$session = MatchingSession::create([
    'inquiry_id' => $inquiry->id,
    'status' => SessionStatus::ACTIVE,
    'locked_at' => now(),
    'expires_at' => now()->addHours(24), // 24 hours for brand-converter sessions
    'discovery_start' => now(),
    'active_session_start' => now(),
]);
```

---

## Session Lifecycle

### Session States:

1. **ACTIVE** (Initial State)
   - Created automatically when inquiry is posted
   - Status: `ACTIVE`
   - Dealers can respond
   - Matchmaking is ongoing

2. **MATCHING** 
   - System is finding matching dealers/converters
   - Responses are being collected

3. **RESPONSES_RECEIVED**
   - Responses have been received
   - Poster can review responses

4. **LOCKED**
   - Poster has selected dealers/converters
   - Chat is enabled
   - Session is locked for negotiations

5. **CHAT_ACTIVE**
   - Active chat/negotiation phase
   - Quotations can be submitted

6. **DEAL_SUCCESS** / **DEAL_FAILED** / **EXPIRED**
   - Final states
   - Session is completed

---

## Lock Session API

**Endpoint:** `POST /api/v1/sessions/{session_id}/lock`

**Purpose:** This API does **NOT create** a session. It **locks an existing session** and selects dealers/converters.

**What It Does:**
1. Updates session status to `LOCKED`
2. Selects specific dealers/converters
3. Creates `SessionParticipant` records for selected participants
4. Enables chat
5. Makes full specs and brand identity visible

**Code Location:** `app/Http/Controllers/api/SessionController.php` (lines 180-260)

**Request:**
```json
{
    "selected_dealer_ids": [1, 2, 3]
}
```

**Response:**
```json
{
    "success": true,
    "message": "Session locked successfully",
    "data": {
        "id": 1,
        "status": "LOCKED",
        "inquiry": {...},
        "participants": [...],
        "chatThread": {...}
    }
}
```

---

## Important Points

### ✅ Sessions Are Created Automatically
- **No manual session creation needed**
- Sessions are created when you post an inquiry
- One session per inquiry (1:1 relationship)

### ✅ Lock Session API
- **Does NOT create sessions**
- Only locks existing sessions
- Used to select dealers/converters for negotiation

### ✅ Session Status Flow
```
POST Inquiry → Session Created (ACTIVE)
    ↓
Dealers Respond → Status: MATCHING
    ↓
Lock Session API → Status: LOCKED
    ↓
Chat Enabled → Status: CHAT_ACTIVE
    ↓
Deal Finalized → Status: DEAL_SUCCESS/DEAL_FAILED/EXPIRED
```

---

## API Endpoints Summary

| Endpoint | Purpose | Creates Session? |
|----------|---------|------------------|
| `POST /api/v1/inquiries/{id}/post` | Post an inquiry | ✅ **YES** (Automatic) |
| `POST /api/v1/brand/requirement/post` | Post brand requirement | ✅ **YES** (Automatic) |
| `POST /api/v1/sessions/{id}/lock` | Lock session & select dealers | ❌ **NO** (Locks existing) |
| `GET /api/v1/sessions/active` | Get active sessions | ❌ **NO** (Read only) |
| `GET /api/v1/sessions/{id}` | Get session details | ❌ **NO** (Read only) |

---

## Database Relationship

```
inquiries (1) ──── (1) matching_sessions
    │                      │
    │                      └─── (many) session_participants
    │
    └─── (many) responses
```

- **One inquiry = One session** (unique constraint)
- Session is created automatically when inquiry is posted
- Session cannot exist without an inquiry

---

## Example Flow

### Step 1: Create Inquiry (DRAFT)
```http
POST /api/v1/inquiries
```
- Inquiry created with status `DRAFT`
- **No session created yet**

### Step 2: Post Inquiry (Creates Session)
```http
POST /api/v1/inquiries/1/post
```
- Inquiry status changes to `MATCHING`
- **Session automatically created** with status `ACTIVE`
- Matchmaking starts
- Dealers can see and respond to inquiry

### Step 3: Lock Session (Select Dealers)
```http
POST /api/v1/sessions/1/lock
Body: { "selected_dealer_ids": [1, 2, 3] }
```
- Session status changes to `LOCKED`
- Selected dealers become participants
- Chat is enabled
- **Session already exists** - this just locks it

---

## Troubleshooting

### Q: Why can't I find a session for my inquiry?
**A:** Sessions are only created when you **post** the inquiry. If your inquiry is still in `DRAFT` status, no session exists yet.

### Q: Can I create a session manually?
**A:** No, sessions are created automatically. Use `POST /api/v1/inquiries/{id}/post` to create a session.

### Q: What if I want to create a session without posting?
**A:** This is not supported. Sessions are tied to posted inquiries only.

### Q: Can I have multiple sessions for one inquiry?
**A:** No, the relationship is 1:1. One inquiry = One session.

---

## Code References

- **Session Creation (Inquiry Post):** `app/Http/Controllers/api/InquiryController.php:192-200`
- **Session Creation (Brand Requirement):** `app/Services/BrandService.php:215-222`
- **Session Lock:** `app/Http/Controllers/api/SessionController.php:180-260`
- **Session Model:** `app/Models/MatchingSession.php`
- **Session Migration:** `database/migrations/2026_01_03_072635_create_matching_sessions_table.php`

