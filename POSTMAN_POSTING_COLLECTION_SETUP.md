# Postman Collection Setup Guide - Post Requirements (All Roles)

## 📦 Collection File
`Paper_X_Posting_Requirements_Collection.postman_collection.json`

## 🚀 Quick Start

### 1. Import Collection
1. Open Postman
2. Click **Import** button (top left)
3. Select `Paper_X_Posting_Requirements_Collection.postman_collection.json`
4. Collection will be imported with all folders and requests

### 2. Set Base URL
1. Click on the collection name
2. Go to **Variables** tab
3. Set `base_url` to your server URL:
   - Local: `http://localhost:8000`
   - Production: `https://your-domain.com`

### 3. Authentication Flow
1. **Request OTP** - Enter your mobile number
2. **Verify OTP & Login** - Enter OTP (default: `123456`)
3. Token is automatically saved to `auth_token` variable
4. All subsequent requests will use this token

## 📋 Collection Structure

### 1. Authentication
- Request OTP
- Verify OTP & Login (auto-saves token)

### 2. Reference Data
- Get Materials
- Get Machines
- Get Material Finishes
- Get Brands

### 3. Brand - Post Requirement (Legacy)
- Post Packaging Requirement
- Post Printing Requirement
- Post Packaging + Printing

### 4. Brand/Converter - Post Inquiry (New Unified System)
- Create Inquiry (DRAFT) - Material Buy
- Create Inquiry (DRAFT) - Multiple Items
- Calculate Posting Fee
- Post Inquiry (Trigger Matchmaking)
- Get Posting Status
- Get Inquiry Details

### 5. Dealer - Post Requirement
- Post Material Buy Requirement
- Post Material Sell Requirement
- Post Machine Buy Requirement
- Post Job Outsourcing Requirement
- Get My Requirements

### 6. Machine Dealer - Post Machine
- Post Machine for Sale
- Post Machine Buy Requirement
- Get My Active Listings
- Get Active Requirements

### 7. Common - User Profile
- Get User Profile
- Update User Profile
- Switch Role

## 🔑 Key Features

### Auto-Save Variables
The collection automatically saves:
- `auth_token` - Authentication token
- `draft_inquiry_id` - Last created draft inquiry ID
- `posted_inquiry_id` - Last posted inquiry ID
- `dealer_inquiry_id` - Last dealer inquiry ID
- `machine_listing_id` - Last machine listing ID

### Test Scripts
Each request includes test scripts that:
- Save IDs automatically
- Log success messages
- Handle errors gracefully

## 📝 Usage Examples

### Example 1: Brand Posts Packaging Requirement
1. Authenticate (Request OTP → Verify OTP)
2. Go to **3. Brand - Post Requirement (Legacy)**
3. Use **Post Packaging Requirement**
4. Modify the JSON body as needed
5. Send request

### Example 2: Converter Posts Material Inquiry (New System)
1. Authenticate
2. Go to **4. Brand/Converter - Post Inquiry (New Unified System)**
3. **Create Inquiry (DRAFT)** - Creates draft inquiry
4. **Calculate Posting Fee** - Check fee (optional)
5. **Post Inquiry** - Posts and triggers matchmaking
6. **Get Posting Status** - Check matchmaking progress

### Example 3: Dealer Posts Buy Requirement
1. Authenticate
2. Go to **5. Dealer - Post Requirement**
3. Use **Post Material Buy Requirement**
4. Modify material_ids, quantity, etc.
5. Send request

### Example 4: Machine Dealer Posts Machine
1. Authenticate
2. Go to **6. Machine Dealer - Post Machine**
3. Use **Post Machine for Sale**
4. Modify machine_id, price, etc.
5. Send request

## 🔧 Customization

### Update Request Bodies
All request bodies use example data. Update:
- Material IDs (get from Reference Data)
- Machine IDs (get from Reference Data)
- Quantities, prices, locations
- Descriptions and titles

### Add Environment Variables
Create a Postman Environment for:
- Different base URLs (dev, staging, prod)
- Different test user credentials
- Different test data

## ⚠️ Important Notes

1. **Profile Completion Required**: Make sure your user has completed the profile for the role you're testing:
   - Brand profile for brand endpoints
   - Converter profile for converter endpoints
   - Dealer profile for dealer endpoints
   - Machine Dealer profile for machine dealer endpoints

2. **Wallet Credits**: Posting inquiries may require wallet credits. Check wallet balance before posting.

3. **Token Expiry**: If you get 401 errors, re-authenticate to get a new token.

4. **Role Switching**: Use **Switch Role** endpoint if you need to switch between roles for the same user.

## 🐛 Troubleshooting

### Token Not Saving
- Check if test script is running (View → Show Postman Console)
- Manually set `auth_token` in collection variables

### 403 Forbidden
- User doesn't have the required role profile completed
- Check user profile completion status

### 422 Validation Error
- Check request body format
- Ensure all required fields are present
- Verify data types (numbers, strings, arrays)

### 404 Not Found
- Check base_url is correct
- Verify endpoint path matches your API version

## 📚 Related Documentation

- `COMPLETE_API_DOCUMENTATION.md` - Complete API reference
- `BRAND_REQUIREMENT_API_DOCUMENTATION.md` - Brand API details
- `DEALER_API_COMPLETE_DOCUMENTATION.md` - Dealer API details
- `B2B_MATCHMAKING_BACKEND_BLUEPRINT.md` - System architecture

## 💡 Tips

1. **Use Collection Runner**: Run multiple requests in sequence
2. **Save Responses**: Save example responses for reference
3. **Create Examples**: Add response examples to requests
4. **Use Folders**: Organize requests by workflow
5. **Documentation**: Add descriptions to requests for team reference

---

**Happy Testing! 🚀**
