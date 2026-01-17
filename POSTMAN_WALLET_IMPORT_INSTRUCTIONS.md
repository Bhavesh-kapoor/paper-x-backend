# Postman Collection Import Instructions - Wallet & Payment API

## Collection Name
**Zupply - Wallet & Payment API**

## Import Steps

1. **Open Postman**
   - Launch the Postman application

2. **Import Collection**
   - Click on **"Import"** button (top left corner)
   - Select **"File"** tab
   - Click **"Choose Files"** or drag and drop the `Zupply_Wallet_API_Collection.postman_collection.json` file
   - Click **"Import"**

3. **Set Environment Variables**
   - After importing, you'll see the collection in the left sidebar
   - Click on the collection name: **"Zupply - Wallet & Payment API"**
   - Go to the **"Variables"** tab
   - Set the following variables:
     - `base_url`: `http://127.0.0.1:8000` (for local) or `https://your-production-domain.com` (for production)
     - `auth_token`: Your authentication token (obtained from login API)

## Environment Setup

### Option 1: Use Collection Variables
- Edit the collection variables directly in Postman
- Variables are available to all requests in the collection

### Option 2: Create Postman Environment
1. Click on **"Environments"** in the left sidebar
2. Click **"+"** to create a new environment
3. Name it: **"Zupply - Local"** or **"Zupply - Production"**
4. Add variables:
   - `base_url` = `http://127.0.0.1:8000`
   - `auth_token` = `your_token_here`
5. Select the environment from the dropdown (top right)

## Collection Structure

The collection is organized into the following folders:

### 1. **Wallet Management**
   - Get Wallet Balance
   - Get Credit Packs
   - Calculate Custom Credits

### 2. **Purchase Credits**
   - Purchase from Pack
   - Purchase Custom Amount

### 3. **Add Credits**
   - Add Credits (Admin/System)

### 4. **Transaction History**
   - Get All Transactions
   - Get Added Transactions
   - Get Deducted Transactions
   - Get Transactions by Date Range
   - Get Transactions by Type

### 5. **Deduct Credits**
   - Deduct Credits - Requirement Posted
   - Deduct Credits - Deal Closed
   - Deduct Credits - Machinery Inspection

## Authentication

All requests require authentication via Bearer token:
- Header: `Authorization: Bearer {{auth_token}}`
- Get your token from the login/OTP verification API

## Testing Workflow

1. **First, get your authentication token:**
   - Use the Auth API to login and get token
   - Set the token in collection variables

2. **Get Wallet Balance:**
   - Test: `GET /api/v1/wallet`
   - This will auto-create a wallet if it doesn't exist

3. **View Credit Packs:**
   - Test: `GET /api/v1/wallet/credit-packs`
   - See available packs (Starter, Growth, Business, Factory)

4. **Purchase Credits:**
   - Test: `POST /api/v1/wallet/purchase`
   - Use either pack ID or custom amount

5. **View Transactions:**
   - Test: `GET /api/v1/wallet/transactions`
   - Filter by type, date range, etc.

6. **Deduct Credits:**
   - Test: `POST /api/v1/wallet/deduct`
   - Use when posting requirements, closing deals, etc.

## Credit Packs Reference

| Pack ID | Name | Credits | Price (₹) | GST (₹) | Total (₹) |
|---------|------|---------|-----------|---------|-----------|
| 1 | Starter | 50 | 500 | 90 | 590 |
| 2 | Growth | 120 | 1,000 | 180 | 1,180 |
| 3 | Business | 300 | 2,500 | 450 | 2,950 |
| 4 | Factory | 750 | 5,000 | 900 | 5,900 |

## Transaction Types

### For Added Credits:
- `PURCHASE` - Credits purchased
- `REFERRAL_BONUS` - Referral bonus
- `REFUND` - Refunded credits
- `ADMIN_ADJUSTMENT` - Admin adjustment
- `OTHER` - Other types

### For Deducted Credits:
- `REQUIREMENT_POSTED` - Posting a requirement
- `DEAL_CLOSED` - Closing a deal
- `MACHINERY_INSPECTION` - Machinery inspection
- `LISTING_FEE` - Listing fees
- `PREMIUM_FEATURE` - Premium features
- `OTHER` - Other types

## Notes

- All amounts are in Indian Rupees (₹)
- Credit calculation rate: ₹100 = 10 credits
- Default GST: 18%
- Minimum purchase amount: ₹100
- Wallet is auto-created on first access
- Transaction IDs are auto-generated (TXN-XXXXX format)
- Wallet IDs are auto-generated (B2B-XXXXX-WP format)

## Support

For API documentation, refer to: `WALLET_API_DOCUMENTATION.md`


