# Error Codes Reference

This document provides a comprehensive reference for all error codes and messages used in the Paper-X backend API. Use this to identify and troubleshoot issues.

## Error Response Format

All error responses follow this format:

```json
{
    "success": false,
    "message": "Human-readable error message",
    "errors": {
        "error_code": "ERROR_CODE_NAME",
        "additional_context": "value"
    }
}
```

---

## Authentication & Authorization Errors

### `AUTHORIZATION_FAILED`
**HTTP Status:** 403 Forbidden

**When it occurs:**
- User tries to access a resource they don't have permission for
- Policy check fails (e.g., trying to view/edit someone else's inquiry)

**Example:**
```json
{
    "error_code": "AUTHORIZATION_FAILED",
    "inquiry_id": 123,
    "user_id": 456,
    "message": "You do not have permission to view this inquiry"
}
```

**How to fix:**
- Ensure user has the correct role (brand/converter/dealer)
- Verify user owns the resource they're trying to access
- Complete required profile setup

---

## Profile & Role Errors

### `PROFILE_INCOMPLETE`
**HTTP Status:** 403 Forbidden

**When it occurs:**
- User tries to create an inquiry without completing brand/converter profile
- User tries to access role-specific endpoints without proper profile

**Example:**
```json
{
    "error_code": "PROFILE_INCOMPLETE",
    "user_id": 456,
    "user_roles": {
        "has_brand": false,
        "has_converter": false,
        "has_dealer": true,
        "has_machine_dealer": false
    },
    "message": "You need to complete either a brand profile or converter profile to create inquiries."
}
```

**How to fix:**
- Complete brand profile: `POST /api/v1/brand/profile/complete`
- Complete converter profile: `POST /api/v1/converter/profile/complete`

---

### `INVALID_USER_ROLE`
**HTTP Status:** 403 Forbidden

**When it occurs:**
- User tries to access role-specific endpoint without that role
- Dealer tries to access brand-only endpoints

**Example:**
```json
{
    "error_code": "INVALID_USER_ROLE",
    "user_id": 456,
    "user_roles": {
        "has_brand": true,
        "has_converter": false,
        "has_dealer": false,
        "has_machine_dealer": false
    },
    "message": "Please complete your dealer profile to access dealer inquiries."
}
```

**How to fix:**
- Complete the required profile for the endpoint you're trying to access
- Use the correct endpoint for your role

---

## Wallet & Payment Errors

### `WALLET_NOT_FOUND`
**HTTP Status:** 402 Payment Required

**When it occurs:**
- User's wallet doesn't exist in database
- Wallet creation failed

**Example:**
```json
{
    "error_code": "WALLET_NOT_FOUND",
    "user_id": 456
}
```

**How to fix:**
- Contact support to create a wallet
- System should auto-create wallet, but if missing, manual intervention needed

---

### `INSUFFICIENT_BALANCE`
**HTTP Status:** 402 Payment Required

**When it occurs:**
- User tries to post inquiry without enough credits
- Wallet balance is less than required posting fee

**Example:**
```json
{
    "error_code": "INSUFFICIENT_BALANCE",
    "required_credits": 50,
    "current_balance": 25,
    "shortfall": 25,
    "message": "You need 50 credits to post this inquiry, but you only have 25 credits. Please purchase credits first."
}
```

**How to fix:**
- Purchase credits: `POST /api/v1/wallet/purchase`
- Check wallet balance: `GET /api/v1/wallet`

---

## Inquiry Errors

### `INQUIRY_CREATE_ERROR`
**HTTP Status:** 500 Internal Server Error

**When it occurs:**
- Database error while creating inquiry
- Validation fails
- Unexpected exception during creation

**Example:**
```json
{
    "error_code": "INQUIRY_CREATE_ERROR",
    "file": "/path/to/file.php",
    "line": 123
}
```

**How to fix:**
- Check request data format
- Verify all required fields are provided
- Check server logs for detailed error
- Contact support if issue persists

---

### `INQUIRY_NOT_FOUND`
**HTTP Status:** 404 Not Found

**When it occurs:**
- Inquiry ID doesn't exist
- Inquiry was deleted
- User doesn't have access to inquiry

**Example:**
```json
{
    "error_code": "INQUIRY_NOT_FOUND",
    "inquiry_id": 123,
    "message": "The inquiry you are looking for does not exist."
}
```

**How to fix:**
- Verify inquiry ID is correct
- Check if inquiry was deleted
- Ensure you have access to the inquiry

---

### `INQUIRY_POST_ERROR`
**HTTP Status:** 500 Internal Server Error

**When it occurs:**
- Error while posting inquiry (changing status from DRAFT to POSTED)
- Matchmaking fails
- Session creation fails

**Example:**
```json
{
    "error_code": "INQUIRY_POST_ERROR",
    "inquiry_id": 123,
    "file": "/path/to/file.php",
    "line": 456
}
```

**How to fix:**
- Check inquiry status (must be DRAFT)
- Verify wallet has sufficient balance
- Check server logs for detailed error
- Try again or contact support

---

### `INQUIRY_RETRIEVE_ERROR`
**HTTP Status:** 500 Internal Server Error

**When it occurs:**
- Error loading inquiry data
- Database query fails
- Relationship loading fails

**Example:**
```json
{
    "error_code": "INQUIRY_RETRIEVE_ERROR",
    "inquiry_id": 123
}
```

**How to fix:**
- Verify inquiry exists
- Check server logs
- Try again later
- Contact support if issue persists

---

### `INQUIRY_REPUBLISH_ERROR`
**HTTP Status:** 500 Internal Server Error

**When it occurs:**
- Error while republishing inquiry
- Cooldown period not expired
- Database error

**Example:**
```json
{
    "error_code": "INQUIRY_REPUBLISH_ERROR",
    "inquiry_id": 123
}
```

**How to fix:**
- Check if cooldown period has expired
- Verify inquiry can be republished
- Check server logs

---

### `INQUIRY_STEP_SAVE_ERROR`
**HTTP Status:** 500 Internal Server Error

**When it occurs:**
- Error saving multi-step inquiry form
- Validation fails
- Database error

**Example:**
```json
{
    "error_code": "INQUIRY_STEP_SAVE_ERROR",
    "step": 2
}
```

**How to fix:**
- Verify step data is valid
- Check required fields for the step
- Try saving again

---

## Session Errors

### `SESSION_LOCKED`
**HTTP Status:** 400 Bad Request

**When it occurs:**
- User tries to modify responses after session is locked
- User tries to shortlist/reject after lock

**Example:**
```json
{
    "error_code": "SESSION_LOCKED",
    "inquiry_id": 123,
    "session_id": 456,
    "message": "This session has already been locked. You cannot modify responses anymore."
}
```

**How to fix:**
- Session is locked, no modifications allowed
- Create a new inquiry if needed

---

## Response Errors

### `RESPONSES_RETRIEVE_ERROR`
**HTTP Status:** 500 Internal Server Error

**When it occurs:**
- Error loading responses for an inquiry
- Database query fails

**Example:**
```json
{
    "error_code": "RESPONSES_RETRIEVE_ERROR",
    "inquiry_id": 123
}
```

**How to fix:**
- Verify inquiry exists
- Check server logs
- Try again later

---

### `SHORTLIST_ERROR`
**HTTP Status:** 500 Internal Server Error

**When it occurs:**
- Error shortlisting/rejecting a response
- Database update fails

**Example:**
```json
{
    "error_code": "SHORTLIST_ERROR",
    "response_id": 789
}
```

**How to fix:**
- Verify response exists
- Check if session is locked
- Try again

---

## Matchmaking Errors

### `MATCHMAKING_RESPONSES_ERROR`
**HTTP Status:** 500 Internal Server Error

**When it occurs:**
- Error retrieving matchmaking responses
- Score calculation fails
- Distance calculation fails

**Example:**
```json
{
    "error_code": "MATCHMAKING_RESPONSES_ERROR",
    "inquiry_id": 123
}
```

**How to fix:**
- Verify inquiry exists and is posted
- Check server logs
- Try again later

---

## Database Errors

### `DB_ERROR`
**HTTP Status:** 500 Internal Server Error

**When it occurs:**
- Database query fails
- Constraint violation
- Connection issues

**Example:**
```json
{
    "error_code": "DB_ERROR",
    "message": "SQLSTATE[23000]: Integrity constraint violation..."
}
```

**How to fix:**
- Check data format
- Verify foreign key relationships
- Check server logs for SQL details
- Contact support if issue persists

---

## Fee Calculation Errors

### `FEE_CALCULATION_ERROR`
**HTTP Status:** 500 Internal Server Error

**When it occurs:**
- Error calculating posting fee
- Inquiry not found
- Invalid urgency value

**Example:**
```json
{
    "error_code": "FEE_CALCULATION_ERROR",
    "inquiry_id": 123
}
```

**How to fix:**
- Verify inquiry exists
- Check inquiry urgency value
- Try again

---

## Posting Status Errors

### `POSTING_STATUS_ERROR`
**HTTP Status:** 500 Internal Server Error

**When it occurs:**
- Error retrieving posting status
- Matchmaking logs query fails

**Example:**
```json
{
    "error_code": "POSTING_STATUS_ERROR",
    "inquiry_id": 123
}
```

**How to fix:**
- Verify inquiry exists
- Check server logs
- Try again later

---

## Resource Not Found Errors

### `RESOURCE_NOT_FOUND`
**HTTP Status:** 404 Not Found

**When it occurs:**
- Response or dealer not found
- Related resource missing

**Example:**
```json
{
    "error_code": "RESOURCE_NOT_FOUND",
    "response_id": 789,
    "message": "The response or associated dealer could not be found."
}
```

**How to fix:**
- Verify resource ID is correct
- Check if resource was deleted
- Ensure resource exists

---

## Common Error Scenarios & Solutions

### Scenario 1: "This action is unauthorized"
**Error Code:** `AUTHORIZATION_FAILED`

**Possible Causes:**
1. User doesn't have required role
2. User trying to access someone else's resource
3. Policy check failing

**Solutions:**
- Complete required profile (brand/converter/dealer)
- Verify you own the resource
- Check user roles in error response

---

### Scenario 2: "Insufficient wallet balance"
**Error Code:** `INSUFFICIENT_BALANCE`

**Possible Causes:**
1. Not enough credits for posting fee
2. Wallet balance is zero

**Solutions:**
- Purchase credits: `POST /api/v1/wallet/purchase`
- Check balance: `GET /api/v1/wallet`
- Review error details for exact amounts needed

---

### Scenario 3: "Only brands or converters can create inquiries"
**Error Code:** `PROFILE_INCOMPLETE`

**Possible Causes:**
1. User hasn't completed brand profile
2. User hasn't completed converter profile
3. User only has dealer profile

**Solutions:**
- Complete brand profile: `POST /api/v1/brand/profile/complete`
- Complete converter profile: `POST /api/v1/converter/profile/complete`
- Check user roles in error response

---

### Scenario 4: "Database error"
**Error Code:** `DB_ERROR`

**Possible Causes:**
1. Invalid data format
2. Foreign key constraint violation
3. Database connection issue

**Solutions:**
- Check request data format
- Verify all required relationships exist
- Check server logs for SQL details
- Contact support with error details

---

## Error Logging

All errors are logged with the following information:
- Error code
- User ID (if available)
- Resource IDs (inquiry_id, session_id, etc.)
- Error message
- Stack trace (for 500 errors)
- Timestamp

Check Laravel logs at: `storage/logs/laravel.log`

---

## Support

If you encounter an error not listed here or need help troubleshooting:

1. Check the error code in the response
2. Review the error message and context
3. Check server logs for detailed information
4. Contact support with:
   - Error code
   - Full error response
   - Request details (endpoint, payload)
   - User ID (if applicable)

---

**Last Updated:** January 2026  
**Version:** 1.0

