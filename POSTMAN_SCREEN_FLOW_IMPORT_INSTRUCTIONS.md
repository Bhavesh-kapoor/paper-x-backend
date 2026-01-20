# Postman Collection Import Instructions - Screen Flow API

## Collection File
**File**: `B2B_MATCHMAKING_SCREEN_FLOW_POSTMAN_COLLECTION.json`

## Quick Import Steps

1. **Open Postman**
2. **Click "Import"** (top left)
3. **Select "File"** tab
4. **Choose** `B2B_MATCHMAKING_SCREEN_FLOW_POSTMAN_COLLECTION.json`
5. **Click "Import"**

## Collection Structure

The collection is organized by **screen flow**:

### 1. Authentication
- Request OTP
- Verify OTP (auto-saves token)

### 2. Post Requirement Screen (Step 2 of 9)
- Save Inquiry Step

### 3. Payment Confirmation Screen
- Calculate Posting Fee
- Pay & Post Inquiry

### 4. Posting Success Screen
- Get Posting Status (Matchmaking Progress)

### 5. Active Sessions (Sourcing Hub)
- Get Active Sessions - All
- Get Active Sessions - Finding Matches
- Get Active Sessions - Active
- Get Active Sessions - Locked

### 6. Matchmaking Responses Screen
- Get Matchmaking Responses - All
- Get Matchmaking Responses - Exact Match
- Get Matchmaking Responses - Slight Variation
- Get Matchmaking Responses - Nearest
- Shortlist Response
- Reject Response

### 7. Session Locked Screen
- Lock Session (Select Dealers)
- Get Session Details (Locked)

### 8. Partner Chat Screen
- Get Chat Messages
- Send Chat Message

### 9. Session History Screen
- Get Session History - All
- Get Session History - Completed
- Get Session History - Expired
- Search Session History
- Republish Session

### Additional Endpoints
- Get Inquiry Details
- Get Responses (Brand/Converter)
- Get Dealer Inquiries (Matched)
- Dealer Respond to Inquiry
- Mark Deal as Failed

## Environment Variables

The collection uses these variables (auto-saved):

- `base_url`: `http://127.0.0.1:8000`
- `auth_token`: Auto-saved after OTP verification
- `inquiry_id`: Auto-saved after creating/saving inquiry step
- `session_id`: Auto-saved after posting inquiry
- `response_id`: Auto-saved after getting responses
- `chat_thread_id`: Auto-saved after locking session

## Testing Flow

### Complete Flow Test:

1. **Authenticate**
   - Request OTP → Verify OTP (token saved)

2. **Create Inquiry**
   - Save Inquiry Step (Step 2) → `inquiry_id` saved

3. **Payment & Post**
   - Calculate Posting Fee → Check wallet balance
   - Pay & Post Inquiry → `session_id` saved

4. **Check Status**
   - Get Posting Status → See matchmaking progress

5. **View Active Sessions**
   - Get Active Sessions → See all active sessions

6. **View Responses**
   - Get Matchmaking Responses → See dealer responses
   - Shortlist/Reject responses

7. **Lock Session**
   - Lock Session → Select dealers → Chat enabled

8. **Chat**
   - Get Chat Messages → Send Chat Message

9. **History**
   - Get Session History → Republish if needed

## Features

✅ **Auto-save IDs**: Inquiry ID, Session ID, Response ID automatically saved  
✅ **Pre-request Scripts**: Auto-set Authorization headers  
✅ **Test Scripts**: Validate responses and save IDs  
✅ **Query Parameters**: Pre-configured with descriptions  
✅ **Request Examples**: Complete request bodies with sample data  
✅ **Screen Mapping**: Organized by mobile app screens

## Base URL Setup

1. Click on collection name
2. Go to "Variables" tab
3. Set `base_url` to your server URL:
   - Local: `http://127.0.0.1:8000`
   - Production: `https://your-domain.com`

## Usage Tips

1. **Start with Authentication**: Always authenticate first to get token
2. **Follow Screen Order**: Use endpoints in the order they appear (matches app flow)
3. **Check Variables**: After each request, check if IDs were auto-saved
4. **Use Filters**: Try different filter values for sessions and responses
5. **Test Error Cases**: Try invalid IDs or missing required fields

## Related Documentation

- [Screen-Based API Documentation](./SCREEN_BASED_API_DOCUMENTATION.md)
- [B2B Matchmaking API Documentation](./B2B_MATCHMAKING_API_DOCUMENTATION.md)
- [Backend Blueprint](./B2B_MATCHMAKING_BACKEND_BLUEPRINT.md)
- [Main Documentation Index](./DOCS.md)

## Support

For issues or questions, refer to the API documentation or contact the development team.

