#!/bin/bash
# Run this with local backend: php artisan serve --host=0.0.0.0 --port=8000
# Usage: ./verify-otp-local.sh [mobile] (default: 9876543210)

BASE="http://127.0.0.1:8000"
MOBILE="${1:-9876543210}"
# Local env uses fixed OTP 123456 (see OtpService.php)
OTP="123456"

echo "=== 1. Request OTP (POST /api/v1/auth/otp/request) ==="
RESP1=$(curl -s -w "\n%{http_code}" -X POST "$BASE/api/v1/auth/otp/request" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d "{\"mobile\":\"$MOBILE\"}")
HTTP1=$(echo "$RESP1" | tail -n1)
BODY1=$(echo "$RESP1" | sed '$d')
echo "Status: $HTTP1"
echo "$BODY1" | head -c 500
echo -e "\n"

if [ "$HTTP1" != "200" ]; then
  echo "Send OTP failed. Is the server running? (php artisan serve)"
  exit 1
fi

echo "=== 2. Verify OTP (POST /api/v1/auth/otp/verify) ==="
RESP2=$(curl -s -w "\n%{http_code}" -X POST "$BASE/api/v1/auth/otp/verify" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d "{\"mobile\":\"$MOBILE\",\"otp\":\"$OTP\"}")
HTTP2=$(echo "$RESP2" | tail -n1)
BODY2=$(echo "$RESP2" | sed '$d')
echo "Status: $HTTP2"
echo "$BODY2" | head -c 800
echo -e "\n"

if [ "$HTTP2" = "200" ]; then
  echo "SUCCESS: OTP flow works locally. Safe to deploy."
else
  echo "FAIL: Verify returned $HTTP2. Check backend logs."
  exit 1
fi
