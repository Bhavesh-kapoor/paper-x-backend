# Paper-X Backend - Complete Documentation Index

Welcome to the Paper-X B2B Platform documentation. This index provides quick access to all documentation resources.

## Quick Start

- **Base URL**: `http://127.0.0.1:8000`
- **API Base URL**: `http://127.0.0.1:8000/api/v1`
- **Documentation Web**: `http://127.0.0.1:8000/docs`

## 📚 Main Documentation

### B2B Matchmaking Platform (New)
- **[B2B Matchmaking Backend Blueprint](./B2B_MATCHMAKING_BACKEND_BLUEPRINT.md)** - Complete backend architecture with strict visibility controls
- **[B2B Matchmaking API Documentation](./B2B_MATCHMAKING_API_DOCUMENTATION.md)** - Complete API reference for matchmaking endpoints
- **[Matchmaking System Documentation](./MATCHMAKING_AI_DOCUMENTATION.md)** - Matchmaking algorithms, flow from posting to session, UI implementation guide
- **[Screen-Based API Documentation](./SCREEN_BASED_API_DOCUMENTATION.md)** - APIs mapped to mobile app screens
- **[Postman Collection](./B2B_MATCHMAKING_POSTMAN_COLLECTION.json)** - Import this collection to test all APIs

### Brand & Dealer Flows
- **[Brand & Dealer Complete Flow](./BRAND_DEALER_COMPLETE_FLOW_DOCUMENTATION.md)** - End-to-end flows with use cases
- **[Brand Requirement API](./BRAND_REQUIREMENT_API_DOCUMENTATION.md)** - Brand requirement posting APIs
- **[Dealer API Complete](./DEALER_API_COMPLETE_DOCUMENTATION.md)** - Complete Dealer API documentation

### Postman Collections
- **[Screen Flow Postman Collection](./B2B_MATCHMAKING_SCREEN_FLOW_POSTMAN_COLLECTION.json)** - Complete collection for screen-based flow
- **[Screen Flow Import Instructions](./POSTMAN_SCREEN_FLOW_IMPORT_INSTRUCTIONS.md)** - How to import and use screen flow collection
- **[Postman Import Instructions](./POSTMAN_BRAND_DEALER_IMPORT_INSTRUCTIONS.md)** - How to import and use Postman collections

## 🔗 Web Documentation Routes

Access these via `http://127.0.0.1:8000/docs`:

- `/docs` - Main documentation index
- `/docs/b2b-matchmaking-blueprint` - Backend architecture blueprint
- `/docs/b2b-matchmaking-api` - API documentation
- `/docs/matchmaking-ai` - Matchmaking algorithms and flow documentation
- `/docs/brand-dealer-flow` - Brand & Dealer flows
- `/docs/brand-requirement` - Brand requirement APIs
- `/docs/dealer-complete` - Dealer API documentation
- `/docs/all-docs` - This documentation index

## 🎯 API Endpoints by Category

### Authentication
- `POST /api/v1/auth/otp/request` - Request OTP
- `POST /api/v1/auth/otp/verify` - Verify OTP and get token

### Inquiries (New Matchmaking System)
- `POST /api/v1/inquiries` - Create inquiry (DRAFT)
- `POST /api/v1/inquiries/{id}/post` - Post inquiry (trigger matchmaking)
- `GET /api/v1/inquiries/{id}` - Get inquiry details
- `GET /api/v1/inquiries/{id}/responses` - Get responses (brand/converter only)
- `POST /api/v1/inquiries/{id}/republish` - Republish inquiry

### Dealer Inquiries
- `GET /api/v1/dealer/inquiries` - Get matched inquiries
- `POST /api/v1/dealer/inquiries/{id}/respond` - Respond to inquiry

### Sessions (New Matchmaking System)
- `GET /api/v1/sessions/active` - Get active sessions
- `GET /api/v1/sessions/{id}` - Get session details
- `POST /api/v1/sessions/{id}/lock` - Lock session (select dealers)
- `GET /api/v1/sessions/history` - Get session history
- `POST /api/v1/sessions/{id}/republish` - Republish session
- `POST /api/v1/sessions/{id}/deal-failed` - Mark deal as failed

### Brand APIs
- `POST /api/v1/brand/profile/complete` - Complete brand profile
- `GET /api/v1/brand/dashboard` - Get brand dashboard
- `POST /api/v1/brand/requirement/post` - Post requirement (legacy)
- `GET /api/v1/brand/inquiries` - Get my inquiries (legacy)

### Dealer APIs
- `POST /api/v1/dealer/profile/complete` - Complete dealer profile
- `GET /api/v1/dealer/dashboard` - Get dealer dashboard
- `POST /api/v1/dealer/requirement/post` - Post requirement (buy/sell)
- `GET /api/v1/dealer/requirements` - Get requirements with filters

### Converter APIs
- `POST /api/v1/converter/profile/complete` - Complete converter profile
- `GET /api/v1/converter/dashboard` - Get converter dashboard
- `GET /api/v1/converter/requirements` - Get brand requirements
- `POST /api/v1/converter/requirement/{id}/respond` - Respond to requirement

### Wallet & Payments
- `GET /api/v1/wallet` - Get wallet balance
- `GET /api/v1/wallet/credit-packs` - Get credit packs
- `POST /api/v1/wallet/purchase` - Purchase credits
- `GET /api/v1/wallet/transactions` - Get transaction history

## 🏗️ Architecture Overview

### Core Principles
1. **Strict Visibility Controls** - No public browsing, controlled matchmaking only
2. **Session-Based Interactions** - All interactions happen within sessions
3. **Role-Based Access** - Brand/Converter (buyers) and Dealer (sellers)
4. **Matchmaking Engine** - Automated dealer matching based on criteria

### Database Schema
- `inquiries` - Requirement postings
- `inquiry_items` - Individual items within inquiries
- `matchmaking_logs` - Dealer matching records
- `matching_sessions` - Session lifecycle management
- `session_participants` - Who can see/interact with sessions
- `responses` - Dealer responses to inquiries
- `chat_threads` - Chat threads for locked sessions

### State Management
- **Inquiry States**: DRAFT → POSTED → MATCHING → RESPONSES_RECEIVED → LOCKED → CHAT_ACTIVE → DEAL_SUCCESS/DEAL_FAILED/EXPIRED → REPUBLISHED
- **Session States**: Same as inquiry states

## 🔐 Security & Authorization

- **Policies**: InquiryPolicy, SessionPolicy, DealerResponsePolicy
- **Visibility Scopes**: Database-level visibility flags
- **No Global Search**: Dealers cannot browse all inquiries
- **Anonymization**: Brand identity hidden until session lock

## 📖 For Developers

### Getting Started
1. Import Postman collection: `B2B_MATCHMAKING_POSTMAN_COLLECTION.json`
2. Set environment variables: `base_url`, `token`
3. Start with authentication endpoints
4. Follow the flow: Create Inquiry → Post → Respond → Lock → Chat

### Key Files
- **Controllers**: `app/Http/Controllers/api/InquiryController.php`, `SessionController.php`
- **Services**: `app/Services/MatchmakingService.php`
- **Policies**: `app/Policies/InquiryPolicy.php`, `SessionPolicy.php`, `DealerResponsePolicy.php`
- **Models**: `app/Models/Inquiry.php`, `MatchingSession.php`, `MatchmakingLog.php`, `SessionParticipant.php`

### Migrations
Run migrations to set up the database:
```bash
php artisan migrate
```

## 📞 Support

For issues or questions, contact the development team.

---

**Last Updated**: January 2026
