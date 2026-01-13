# Quick API Test Guide

## Correct Endpoint
```
POST {{BASE_URL}}/api/v1/dealer/profile/complete
```

## Headers Required
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json
```

## Sample Request Body
```json
{
  "materials_dealt_in": [1, 2],
  "machines_available": [1, 2],
  "grades": ["A", "B"],
  "capacity_daily": 1000,
  "capacity_monthly": 30000,
  "capacity_unit": "kg",
  "locations": [
    {
      "type": "factory",
      "address": "123 Test Street",
      "latitude": 28.6139,
      "longitude": 77.2090,
      "city": "Delhi",
      "state": "Delhi"
    }
  ]
}
```

## Common Issues

1. **Wrong URL**: Missing `/api/v1` prefix
   - ❌ `{{BASE_URL}}dealer/profile/complete`
   - ✅ `{{BASE_URL}}/api/v1/dealer/profile/complete`

2. **Wrong Header Format**: 
   - ❌ `Token: YOUR_TOKEN`
   - ✅ `Authorization: Bearer YOUR_TOKEN`

3. **Token Format**: Make sure you're using the full token from login response
   - The token should be from: `POST /api/v1/auth/otp/verify` response





