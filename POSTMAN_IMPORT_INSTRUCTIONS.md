# Postman Collection Import Instructions

## 📥 How to Import the Collection

### Method 1: Direct Import
1. Open Postman
2. Click **Import** button (top left)
3. Select **File** tab
4. Choose `Paper_X_B2B_API_Collection.postman_collection.json`
5. Click **Import**

### Method 2: Drag & Drop
1. Open Postman
2. Drag `Paper_X_B2B_API_Collection.postman_collection.json` into Postman window
3. Collection will be imported automatically

---

## 🔧 Setup Environment Variables

After importing, set up environment variables:

### Create New Environment:
1. Click **Environments** (left sidebar)
2. Click **+** to create new environment
3. Name it: `Zupply - Local` or `Zupply - Production`

### Add Variables:
1. `base_url` = `http://localhost:8000` (or your server URL)
2. `auth_token` = (will be auto-filled after login)

### Select Environment:
- Click on the environment name to activate it
- Make sure it's selected in the top-right dropdown

---

## 🚀 Quick Start

### Step 1: Request OTP
1. Go to **Authentication** → **Request OTP**
2. Update mobile number in request body
3. Click **Send**

### Step 2: Verify OTP & Login
1. Go to **Authentication** → **Verify OTP & Login**
2. Enter OTP received
3. Click **Send**
4. **Token will be automatically saved** to `auth_token` variable

### Step 3: Use Protected APIs
- All APIs with 🔒 icon require authentication
- Token is automatically included via Bearer token
- No need to manually add token!

---

## 📁 Collection Structure

```
Zupply - B2B Matchmaking Platform API
├── Authentication
│   ├── Request OTP
│   └── Verify OTP & Login (auto-saves token)
├── Reference Data
│   ├── Get Materials
│   ├── Get Machines
│   ├── Get Material Finishes
│   ├── Get Material Mills
│   ├── Get Material Thickness Types
│   ├── Get Brands (Mills)
│   └── Get Material Details
├── Common APIs
│   ├── Get User Profile
│   ├── Update User Profile
│   └── Switch Role
├── Dealer APIs
│   ├── Complete Dealer Profile
│   ├── Get Dealer Dashboard
│   ├── Get Opportunities
│   ├── Accept/Decline Opportunity
│   ├── Session Management
│   ├── Chat
│   ├── Quotations
│   └── Notifications
├── Machine Dealer APIs
│   ├── Complete Machine Dealer Profile
│   ├── Get Dashboard
│   ├── Post Machine
│   ├── Get Active Listings
│   └── Browse Requirements
├── Converter APIs
│   ├── Complete Converter Profile
│   └── Get Dashboard
└── Brand APIs
    ├── Complete Brand Profile
    └── Get Dashboard
```

---

## 🔑 Authentication

### Automatic Token Management:
- Token is automatically saved after login
- All protected APIs use Bearer token automatically
- No manual token management needed!

### Manual Token Update (if needed):
1. Go to **Environments**
2. Edit `auth_token` variable
3. Paste your token

---

## 📝 Notes

1. **Base URL**: Update `base_url` variable for different environments
   - Local: `http://localhost:8000`
   - Staging: `https://staging.example.com`
   - Production: `https://api.example.com`

2. **Token Expiry**: If token expires, just run "Verify OTP & Login" again

3. **Request Examples**: All requests have example payloads included

4. **Query Parameters**: Many endpoints have query parameters pre-filled with examples

---

## ✅ Testing Checklist

- [ ] Import collection successfully
- [ ] Set up environment variables
- [ ] Test OTP request
- [ ] Test OTP verify (check token is saved)
- [ ] Test a protected endpoint (should work automatically)
- [ ] Update base_url for your environment

---

## 🐛 Troubleshooting

### Token not saving?
- Check if environment is selected
- Verify "Verify OTP & Login" test script is working
- Manually set `auth_token` in environment

### 401 Unauthorized?
- Check if token is set in environment
- Verify environment is selected
- Re-run "Verify OTP & Login"

### 404 Not Found?
- Check `base_url` is correct
- Verify API routes are registered
- Check server is running

---

**Happy Testing!** 🚀



