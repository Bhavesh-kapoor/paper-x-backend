# Matchmaking System Documentation

> **📚 [View All Documentation](./DOCS.md)** - Complete documentation index with all links

## Table of Contents

1. [Overview](#overview)
2. [Matchmaking Algorithms](#matchmaking-algorithms)
3. [Flow: From Inquiry Posting to Local Session](#flow-from-inquiry-posting-to-local-session)
4. [UI Implementation Guide](#ui-implementation-guide)
5. [Web Routes Documentation](#web-routes-documentation)
6. [API Endpoints for Matchmaking](#api-endpoints-for-matchmaking)
7. [Scoring System Details](#scoring-system-details)
8. [Implementation Examples](#implementation-examples)

---

## Overview

The Paper-X platform uses **rule-based matchmaking algorithms** (not AI/ML) to match inquiries with dealers and converters. The system uses scoring mechanisms based on multiple criteria to find the best matches.

### Key Components

- **MatchmakingService**: Handles dealer matching for material/machine inquiries
- **BrandService**: Handles converter matching for brand requirements
- **OpportunityService**: Handles dealer opportunity matching
- **Scoring System**: Multi-factor scoring algorithm (0-100 points)

### Matchmaking Types

1. **Dealer Matching** (Material/Machine Inquiries)
   - Matches dealers based on materials, machines, capacity, location
   - Used for dealer-to-dealer transactions

2. **Converter Matching** (Brand Requirements)
   - Matches converters based on city, capacity, converter type
   - Used for brand-to-converter transactions

---

## Matchmaking Algorithms

### 1. Dealer Matching Algorithm

**Service**: `App\Services\MatchmakingService`

**Method**: `findMatchingDealers(Inquiry $inquiry, int $maxDealers = 10)`

#### Scoring Breakdown (Total: 100 points)

| Factor | Points | Description |
|--------|--------|-------------|
| Material Match | 30 | Dealer must have matching materials |
| Finish/Coating Match | 15 | Matching finishes/coatings |
| Thickness Match | 25 | Thickness within tolerance range |
| Location Match | 30 | Distance-based scoring (closer = higher) |
| Priority Bonus | 10 | Authorized agents, response history, success rate |

#### Matching Criteria

```php
// Material Matching
- Inquiry materials must intersect with dealer materials
- Category-based fallback if exact match not found

// Thickness Matching (with tolerance)
- Normal inquiry: GSM ±5%, MM ±0.2mm
- Urgent inquiry: GSM ±10%, MM ±0.3mm

// Location Matching
- Distance calculated using Haversine formula
- Dealers within 100km prioritized
- Score decreases with distance (max 30 points)

// Priority Bonuses
- Authorized mill agents get bonus points
- Faster response history (future enhancement)
- Higher deal success rate (future enhancement)
```

#### Algorithm Flow

```
1. Get all active dealers with complete profiles
2. For each dealer:
   a. Calculate material match score (0-30)
   b. Calculate finish match score (0-15)
   c. Calculate thickness match score (0-25)
   d. Calculate location match score (0-30)
   e. Add priority bonuses (0-10)
   f. Calculate total score
3. Filter dealers with score > 0
4. Sort by total score (descending)
5. Select top N dealers (default: 10)
6. Create matchmaking logs
7. Update inquiry visibility
```

---

### 2. Converter Matching Algorithm

**Service**: `App\Services\BrandService`

**Method**: `findBestConverters(Inquiry $inquiry, int $limit = 10)`

#### Scoring Breakdown (Total: 100 points)

| Factor | Points | Description |
|--------|--------|-------------|
| City Match | 40 | Exact city match |
| Capacity Match | 30 | Converter capacity >= inquiry quantity |
| Converter Type Match | 20 | Based on requirement type |
| Profile Completeness | 10 | Complete profile bonus |

#### Matching Criteria

```php
// City Matching
- Exact city match: 40 points
- Same state: 20 points
- Different state: 0 points

// Capacity Matching
- Converter capacity >= inquiry max quantity: 30 points
- Converter capacity >= 50% of inquiry quantity: 15 points
- Converter capacity < 50%: 0 points

// Converter Type Match
- Based on requirement type (Packaging, Printing, etc.)
- Default: 20 points (can be enhanced)

// Profile Completeness
- Profile complete: 10 points
- Profile incomplete: 0 points
```

#### Algorithm Flow

```
1. Get brand city from inquiry
2. Get all active converters with complete profiles
3. Filter by city (if brand city available)
4. If less than 10 converters in same city:
   a. Expand search to other cities
5. For each converter:
   a. Calculate city match score (0-40)
   b. Calculate capacity match score (0-30)
   c. Calculate converter type match score (0-20)
   d. Calculate profile completeness score (0-10)
   e. Calculate total score
6. Sort by total score (descending)
7. Select top N converters (default: 10)
```

---

## Flow: From Inquiry Posting to Local Session

### Complete Flow Diagram

```
┌─────────────────┐
│  User Posts     │
│   Inquiry       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Validate       │
│  Wallet Balance │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Deduct Credits │
│  (50 credits)   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Create Inquiry │
│  Status: MATCHING│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Trigger        │
│  Matchmaking    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Find Matching  │
│  Dealers/       │
│  Converters     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Create         │
│  Matchmaking    │
│  Logs           │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Create         │
│  Matching       │
│  Session        │
│  Status: ACTIVE │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Notify         │
│  Matched        │
│  Dealers/       │
│  Converters     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Session        │
│  Locked         │
│  (Immediate)     │
└─────────────────┘
```

### Step-by-Step Flow

#### Step 1: Inquiry Posting

**Endpoint**: `POST /api/v1/inquiries` (DRAFT) or `POST /api/v1/inquiries/{id}/post` (POSTED)

**What Happens**:
1. User creates inquiry (status: `DRAFT`)
2. User posts inquiry (status: `MATCHING`)
3. System validates wallet balance (50 credits required)
4. System deducts credits from wallet
5. System creates wallet transaction record

#### Step 2: Matchmaking Trigger

**Service**: `MatchmakingService::findMatchingDealers()` or `BrandService::findBestConverters()`

**What Happens**:
1. System identifies inquiry type (material/machine/job or brand requirement)
2. System selects appropriate matching algorithm
3. System queries eligible dealers/converters
4. System calculates match scores for each candidate
5. System selects top 10 matches

#### Step 3: Matchmaking Logs Creation

**Model**: `MatchmakingLog`

**What Happens**:
1. System creates log entry for each matched dealer/converter
2. Logs include:
   - Material match score
   - Finish match score
   - Thickness match score
   - Location match score
   - Priority score
   - Total score breakdown
   - Visibility flags

#### Step 4: Session Creation

**Model**: `MatchingSession`

**What Happens**:
1. System creates matching session
2. Session status: `ACTIVE`
3. Session locked immediately (`locked_at` = now)
4. Session expires in 24 hours (`expires_at` = now + 24 hours)
5. Session linked to inquiry

#### Step 5: Notification

**Service**: `NotificationService`

**What Happens**:
1. System creates notification for each matched dealer/converter
2. Notification type: `NEW_OPPORTUNITY` or `SESSION_LOCKED`
3. Notification includes inquiry/session details

#### Step 6: Session Visibility

**What Happens**:
1. Inquiry becomes visible to matched dealers/converters
2. `is_visible_to_dealers` flag set to `true`
3. Dealers/converters can view inquiry details
4. Dealers/converters can respond to inquiry

---

## UI Implementation Guide

### Frontend Components Needed

#### 1. Inquiry Posting Form

**Fields Required**:
- Requirement type (Packaging, Printing, etc.)
- Packaging type (conditional)
- Quantity range
- Timeline
- Special needs (optional)
- Design attachments (optional)
- Location (city, latitude, longitude)

**API Call**:
```javascript
POST /api/v1/inquiries
{
  "requirement_type": "Packaging",
  "packaging_type": "Boxes",
  "quantity_range": "1000-5000",
  "timeline": "3–5 Days",
  "special_needs": "Eco-friendly packaging",
  "location": "Mumbai",
  "latitude": 19.0760,
  "longitude": 72.8777
}
```

#### 2. Matchmaking Status Display

**Show**:
- Inquiry status (MATCHING, SESSION_LOCKED, etc.)
- Number of matched dealers/converters
- Session ID
- Session expiration time
- Match score (if available)

**API Call**:
```javascript
GET /api/v1/inquiries/{id}
```

#### 3. Matched Dealers/Converters List

**Show**:
- List of matched dealers/converters
- Match scores
- Response status
- Contact information (after session lock)

**API Call**:
```javascript
GET /api/v1/inquiries/{id}/responses
```

#### 4. Session Details View

**Show**:
- Session status
- Locked at timestamp
- Expires at timestamp
- Participants list
- Chat messages
- Quotations

**API Call**:
```javascript
GET /api/v1/sessions/{id}
```

### UI States

#### State 1: Inquiry Draft
- User can edit inquiry
- No matchmaking triggered
- No session created

#### State 2: Inquiry Posted (MATCHING)
- Matchmaking in progress
- Matched dealers/converters visible
- Session created and locked
- Notifications sent

#### State 3: Session Active
- Dealers/converters can respond
- Chat enabled
- Quotations can be submitted
- Brand details visible (after lock)

#### State 4: Session Expired
- Session expired (24 hours)
- No new responses allowed
- Can republish inquiry

---

## Web Routes Documentation

### Documentation Routes

All documentation routes are accessible via `http://127.0.0.1:8000/docs`

| Route | Description | File |
|-------|-------------|------|
| `/docs` | Documentation index | `routes/web.php` |
| `/docs/all-docs` | Complete documentation index | `DOCS.md` |
| `/docs/b2b-matchmaking-blueprint` | Backend architecture blueprint | `B2B_MATCHMAKING_BACKEND_BLUEPRINT.md` |
| `/docs/b2b-matchmaking-api` | API documentation | `B2B_MATCHMAKING_API_DOCUMENTATION.md` |
| `/docs/brand-dealer-flow` | Brand & Dealer flows | `BRAND_DEALER_COMPLETE_FLOW_DOCUMENTATION.md` |
| `/docs/brand-requirement` | Brand requirement APIs | `BRAND_REQUIREMENT_API_DOCUMENTATION.md` |
| `/docs/dealer-complete` | Dealer API documentation | `DEALER_API_COMPLETE_DOCUMENTATION.md` |
| `/docs/api-routes` | Beautiful API routes list | `routes/web.php` |
| `/docs/request-formats` | Request/response formats | `API_REQUEST_FORMATS.md` |

### Adding New Documentation Route

**Step 1**: Create markdown file in project root

**Step 2**: Add route in `routes/web.php`:

```php
Route::get('/docs/your-doc-name', function () {
    $file = base_path('YOUR_DOC_FILE.md');
    if (!File::exists($file)) {
        return response('Documentation file not found', 404);
    }
    return renderMarkdown($file, 'Your Documentation Title');
})->name('docs.your-doc-name');
```

**Step 3**: Add link to documentation index (`DOCS.md`)

---

## API Endpoints for Matchmaking

### Inquiry Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/inquiries` | Create inquiry (DRAFT) |
| `POST` | `/api/v1/inquiries/{id}/post` | Post inquiry (trigger matchmaking) |
| `GET` | `/api/v1/inquiries/{id}` | Get inquiry details |
| `GET` | `/api/v1/inquiries/{id}/responses` | Get responses (brand/converter only) |
| `POST` | `/api/v1/inquiries/{id}/republish` | Republish inquiry |

### Session Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/sessions/active` | Get active sessions |
| `GET` | `/api/v1/sessions/{id}` | Get session details |
| `POST` | `/api/v1/sessions/{id}/lock` | Lock session (select dealers) |
| `GET` | `/api/v1/sessions/history` | Get session history |
| `POST` | `/api/v1/sessions/{id}/republish` | Republish session |
| `POST` | `/api/v1/sessions/{id}/deal-failed` | Mark deal as failed |

### Dealer Opportunity Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/dealer/inquiries` | Get matched inquiries |
| `POST` | `/api/v1/dealer/inquiries/{id}/respond` | Respond to inquiry |

---

## Scoring System Details

### Dealer Matching Score Calculation

```php
Total Score = Material Score + Finish Score + Thickness Score + Location Score + Priority Bonus

Material Score (0-30):
- Exact material match: 30 points
- Category match: 25 points
- No match: 0 points

Finish Score (0-15):
- Matching finishes: 15 points
- No match: 0 points

Thickness Score (0-25):
- Within tolerance (normal): 25 points
- Within tolerance (urgent): 20 points
- Outside tolerance: 0 points

Location Score (0-30):
- Distance <= 10km: 30 points
- Distance <= 50km: 25 points
- Distance <= 100km: 20 points
- Distance > 100km: 0 points

Priority Bonus (0-10):
- Authorized agent: +5 points
- Fast response history: +3 points
- High success rate: +2 points
```

### Converter Matching Score Calculation

```php
Total Score = City Score + Capacity Score + Type Score + Profile Score

City Score (0-40):
- Exact city match: 40 points
- Same state: 20 points
- Different state: 0 points

Capacity Score (0-30):
- Capacity >= inquiry max: 30 points
- Capacity >= 50% of inquiry: 15 points
- Capacity < 50%: 0 points

Type Score (0-20):
- Converter type matches requirement: 20 points
- Partial match: 10 points
- No match: 0 points

Profile Score (0-10):
- Complete profile: 10 points
- Incomplete profile: 0 points
```

---

## Implementation Examples

### Example 1: Post Inquiry and Trigger Matchmaking

```php
// Create inquiry (DRAFT)
$inquiry = Inquiry::create([
    'poster_id' => $brand->id,
    'poster_type' => 'brand',
    'title' => 'Need custom packaging boxes',
    'requirement_type' => 'Packaging',
    'quantity_range' => '1000-5000',
    'status' => InquiryStatus::DRAFT,
]);

// Post inquiry (triggers matchmaking)
$brandService = app(BrandService::class);
$result = $brandService->postRequirement($inquiry->id, $userId);

// Result contains:
// - inquiry_id
// - session_id
// - matched_converters_count
```

### Example 2: Get Matchmaking Results

```php
// Get matchmaking logs
$logs = MatchmakingLog::where('inquiry_id', $inquiryId)
    ->where('is_visible', true)
    ->orderBy('priority_score', 'desc')
    ->get();

// Get matched dealers/converters
$matchedDealers = $logs->map(function ($log) {
    return [
        'dealer_id' => $log->dealer_id,
        'score' => $log->priority_score,
        'breakdown' => $log->score_breakdown,
    ];
});
```

### Example 3: Calculate Distance (Haversine Formula)

```php
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c; // Distance in km
}
```

### Example 4: Check Thickness Tolerance

```php
function checkThicknessMatch($inquiryThickness, $dealerThickness, $unit, $urgency) {
    $tolerancePercent = $urgency === 'urgent' ? 10.0 : 5.0;
    $toleranceAbsolute = $urgency === 'urgent' ? 0.3 : 0.2;
    
    if ($unit === 'GSM') {
        $tolerance = $inquiryThickness * ($tolerancePercent / 100);
        $min = $inquiryThickness - $tolerance;
        $max = $inquiryThickness + $tolerance;
        return $dealerThickness >= $min && $dealerThickness <= $max;
    } elseif ($unit === 'MM') {
        $min = $inquiryThickness - $toleranceAbsolute;
        $max = $inquiryThickness + $toleranceAbsolute;
        return $dealerThickness >= $min && $dealerThickness <= $max;
    }
    
    return false;
}
```

---

## Key Files Reference

### Services
- `app/Services/MatchmakingService.php` - Dealer matching algorithm
- `app/Services/BrandService.php` - Converter matching algorithm
- `app/Services/OpportunityService.php` - Dealer opportunity matching

### Models
- `app/Models/Inquiry.php` - Inquiry model
- `app/Models/MatchingSession.php` - Session model
- `app/Models/MatchmakingLog.php` - Matchmaking log model

### Controllers
- `app/Http/Controllers/api/InquiryController.php` - Inquiry endpoints
- `app/Http/Controllers/api/SessionController.php` - Session endpoints

### Routes
- `routes/api.php` - API routes
- `routes/web.php` - Web documentation routes

---

## Future Enhancements

### Planned AI/ML Features

1. **Machine Learning Scoring**
   - Train model on historical deal success data
   - Predict deal success probability
   - Adjust match scores based on predictions

2. **Recommendation Engine**
   - Suggest similar inquiries to dealers
   - Recommend dealers to brands based on past success
   - Personalized matching based on user behavior

3. **Natural Language Processing**
   - Extract requirements from free-text descriptions
   - Auto-categorize inquiries
   - Match based on semantic similarity

4. **Predictive Analytics**
   - Predict inquiry response rates
   - Forecast deal closure probability
   - Optimize matching thresholds

---

## Support & Contact

> **📚 [View All Documentation](./DOCS.md)** - Complete documentation index with all links

For questions about matchmaking algorithms or implementation:
- Review the code in `app/Services/MatchmakingService.php`
- Check API documentation: `/docs/b2b-matchmaking-api`
- Review flow documentation: `/docs/brand-dealer-flow`

---

**Last Updated**: January 2026  
**Version**: 1.0


