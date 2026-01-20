# Postman Collection Import Instructions

## Paper X - Brand & Dealer API Collection

This document provides instructions for importing and using the Postman collection for Brand and Dealer APIs.

---

## 📦 Collection File

**File Name:** `Paper_X_Brand_Dealer_API_Collection.postman_collection.json`

**Location:** Root directory of the project

---

## 📥 Import Steps

### Method 1: Import from File

1. **Open Postman**
   - Launch Postman application (Desktop or Web)

2. **Click Import**
   - Click the **Import** button in the top left corner
   - Or use shortcut: `Ctrl+O` (Windows/Linux) or `Cmd+O` (Mac)

3. **Select File**
   - Click on the **File** tab
   - Click **Upload Files** or drag and drop
   - Navigate to and select `Paper_X_Brand_Dealer_API_Collection.postman_collection.json`
   - Click **Import**

4. **Verify Import**
   - The collection should appear in your Postman sidebar
   - Collection name: **"Paper X - Brand & Dealer API Collection"**

### Method 2: Import from URL (if hosted)

1. Click **Import**
2. Select **Link** tab
3. Enter the URL to the collection JSON file
4. Click **Continue** → **Import**

---

## ⚙️ Configuration

### 1. Set Base URL

1. Click on the collection name in the sidebar
2. Go to **Variables** tab
3. Find `base_url` variable
4. Set value to your API server URL:
   - Local: `http://localhost:8000`
   - Staging: `https://staging-api.example.com`
   - Production: `https://api.example.com`
5. Click **Save**

### 2. Collection Variables

The collection includes these variables:

| Variable | Default Value | Description |
|----------|---------------|-------------|
| `base_url` | `http://localhost:8000` | API base URL |
| `auth_token` | (empty) | Auto-saved after login |
| `session_id` | (empty) | Session ID for testing |
| `inquiry_id` | (empty) | Inquiry ID for testing |

**Note:** `auth_token` is automatically saved when you use the "Verify OTP & Login" request.

---

## 🚀 Quick Start Guide

### Step 1: Authentication

1. **Request OTP**
   - Open: **Authentication** → **Request OTP**
   - Update mobile number in request body
   - Click **Send**
   - Check console/logs for OTP

2. **Verify OTP & Login**
   - Open: **Authentication** → **Verify OTP & Login**
   - Enter mobile number and OTP
   - Click **Send**
   - ✅ Token is automatically saved to `auth_token` variable

### Step 2: Brand Flow

1. **Complete Brand Profile**
   - Open: **Brand APIs** → **Complete Brand Profile**
   - Fill in brand details
   - Select brand types (IDs from reference data)
   - Click **Send**

2. **Post Requirement**
   - Open: **Brand APIs** → **Post Requirement**
   - Fill in requirement details:
     - `requirement_type`: Packaging, Printing, Packaging + Printing, Corporate Gifting / Stationery
     - `packaging_type`: Required if requirement_type includes Packaging
     - `quantity_range`: e.g., "1000-5000"
     - `timeline`: Emergency (Urgent), 3–5 Days, Flexible
   - Click **Send**
   - Note: Deducts 50 credits from wallet

3. **Get My Inquiries**
   - Open: **Brand APIs** → **Get My Inquiries**
   - Add query parameters if needed (status, urgency, page)
   - Click **Send**

### Step 3: Dealer Flow

1. **Complete Dealer Profile**
   - Open: **Dealer APIs** → **Complete Dealer Profile**
   - Fill in dealer details:
     - Materials dealt in
     - Machines available
     - Capacity details
     - Locations (factory/warehouse)
   - Click **Send**

2. **Get Opportunities**
   - Open: **Dealer APIs** → **Get Opportunities**
   - Click **Send**
   - View matched opportunities based on profile

3. **Accept Opportunity**
   - Open: **Dealer APIs** → **Accept Opportunity**
   - Update `{{inquiry_id}}` variable or replace in URL
   - Click **Send**
   - When 10 dealers accept, session locks automatically

4. **Get Session Details**
   - Open: **Dealer APIs** → **Get Session Details**
   - Update `{{session_id}}` variable
   - Click **Send**
   - Brand details visible after session lock

5. **Send Chat Message**
   - Open: **Dealer APIs** → **Send Chat Message**
   - Update `{{session_id}}` variable
   - Enter message
   - Click **Send**

### Step 4: Converter Flow

1. **Get Brand Requirements**
   - Open: **Converter APIs** → **Get Brand Requirements**
   - Add query parameters (city, requirement_type, urgency)
   - Click **Send**
   - View brand requirements (brand details hidden)

2. **Respond to Requirement**
   - Open: **Converter APIs** → **Respond to Requirement**
   - Update `{{inquiry_id}}` variable
   - Fill in response details
   - Click **Send**

---

## 📋 Collection Structure

```
Paper X - Brand & Dealer API Collection
├── Authentication
│   ├── Request OTP
│   └── Verify OTP & Login
├── Brand APIs
│   ├── Complete Brand Profile
│   ├── Get Brand Dashboard
│   ├── Post Requirement
│   ├── Get My Inquiries
│   └── Get Messages
├── Dealer APIs
│   ├── Complete Dealer Profile
│   ├── Get Dealer Dashboard
│   ├── Post Requirement
│   ├── Get Requirements
│   ├── Get Opportunities
│   ├── Get Opportunity Details
│   ├── Accept Opportunity
│   ├── Decline Opportunity
│   ├── Get Session Details
│   ├── Get Session History
│   ├── Get Chat Messages
│   ├── Send Chat Message
│   ├── Submit Quotation
│   └── Get Notifications
├── Converter APIs
│   ├── Get Brand Requirements
│   └── Respond to Requirement
└── Reference Data
    ├── Get Brand Types
    ├── Get Materials
    └── Get Machines
```

---

## 🔧 Tips & Best Practices

### 1. Using Variables

- Variables are automatically used in requests
- Update variables in collection settings or environment
- Use `{{variable_name}}` syntax in URLs and request bodies

### 2. Testing Workflows

- Use Postman's **Collection Runner** to test complete flows
- Create **Environments** for different stages (dev, staging, prod)
- Use **Tests** tab to add assertions

### 3. Token Management

- Token is auto-saved after login
- If token expires, re-run "Verify OTP & Login"
- Token is used automatically in all authenticated requests

### 4. Query Parameters

- Many endpoints support pagination (`per_page`, `page`)
- Use filters to narrow down results
- Check documentation for available filters

### 5. Error Handling

- Check response status codes
- Review error messages in response body
- Common errors:
  - `401`: Unauthorized (invalid/missing token)
  - `422`: Validation error (check request body)
  - `400`: Bad request (check parameters)

---

## 📝 Example Test Scripts

### Auto-save Session ID

Add this to "Get Session Details" response test:

```javascript
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.session_id) {
        pm.collectionVariables.set('session_id', jsonData.data.session_id);
    }
}
```

### Auto-save Inquiry ID

Add this to "Post Requirement" response test:

```javascript
if (pm.response.code === 201) {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.inquiry_id) {
        pm.collectionVariables.set('inquiry_id', jsonData.data.inquiry_id);
    }
}
```

---

## 🐛 Troubleshooting

### Token Not Saving

- Check if "Verify OTP & Login" test script is enabled
- Verify response format matches expected structure
- Manually set token in collection variables if needed

### 401 Unauthorized

- Token may have expired
- Re-run "Verify OTP & Login"
- Check if token variable is set correctly

### 422 Validation Error

- Review request body format
- Check required fields
- Verify data types match API expectations

### Base URL Issues

- Ensure `base_url` variable is set
- Check for trailing slashes
- Verify server is running

---

## 📚 Additional Resources

> **📚 [View All Documentation](./DOCS.md)** - Complete documentation index with all links

- **[BRAND_REQUIREMENT_API_DOCUMENTATION.md](./BRAND_REQUIREMENT_API_DOCUMENTATION.md)** - Detailed API endpoints and request/response examples
- **[BRAND_DEALER_COMPLETE_FLOW_DOCUMENTATION.md](./BRAND_DEALER_COMPLETE_FLOW_DOCUMENTATION.md)** - Complete flow documentation with use cases
- **[DEALER_API_COMPLETE_DOCUMENTATION.md](./DEALER_API_COMPLETE_DOCUMENTATION.md)** - Complete Dealer API documentation
- **[COMPLETE_API_DOCUMENTATION.md](./COMPLETE_API_DOCUMENTATION.md)** - Complete API reference for all endpoints
- **[COMPLETE_FLOW_DOCUMENTATION.md](./COMPLETE_FLOW_DOCUMENTATION.md)** - Complete flow documentation for all user types

---

## ✅ Checklist

Before testing, ensure:

- [ ] Postman collection imported successfully
- [ ] `base_url` variable set correctly
- [ ] Server is running and accessible
- [ ] OTP login working (token saved)
- [ ] Profile completed for role you're testing
- [ ] Wallet has sufficient credits (for brand posting)

---

**Last Updated:** January 17, 2026  
**Collection Version:** 1.0

