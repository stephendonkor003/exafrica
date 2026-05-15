# Quick API Reference & CURL Commands

## Base URL
```
http://localhost:8000/api/v1
```

## Authentication Token
After login, use the token in all requests:
```
Authorization: Bearer {token}
```

---

## Common CURL Commands

### 1. Register User
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Voter",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### 2. Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@test.com",
    "password": "password"
  }'
```

### 3. Get Current User
```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 4. Create Category (Admin)
```bash
curl -X POST http://localhost:8000/api/v1/categories \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Best Entrepreneur",
    "description": "For exceptional business leaders",
    "icon": "briefcase",
    "max_nominees": 10,
    "position": 1
  }'
```

### 5. List Categories
```bash
curl -X GET "http://localhost:8000/api/v1/categories?page=1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 6. Create Nominee
```bash
curl -X POST http://localhost:8000/api/v1/nominees \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "Jane Smith",
    "bio": "Successful tech entrepreneur with 15 years experience",
    "email": "jane@example.com",
    "phone": "+1234567890",
    "profile_image": "https://example.com/jane.jpg",
    "category_id": 1
  }'
```

### 7. Approve Nominee (Evaluator)
```bash
curl -X POST http://localhost:8000/api/v1/nominees/1/approve \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### 8. Publish Nominee (Evaluator)
```bash
curl -X POST http://localhost:8000/api/v1/nominees/1/publish \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### 9. Reject Nominee with Reason (Evaluator)
```bash
curl -X POST http://localhost:8000/api/v1/nominees/1/reject \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "rejection_reason": "Does not meet the required criteria"
  }'
```

### 10. Cast Vote
```bash
curl -X POST http://localhost:8000/api/v1/votes \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "nominee_id": 1,
    "mac_address": "00:1A:2B:3C:4D:5E"
  }'
```

### 11. Get Category Vote Statistics
```bash
curl -X GET http://localhost:8000/api/v1/votes/stats/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 12. Get Nominee Statistics
```bash
curl -X GET http://localhost:8000/api/v1/votes/candidate/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 13. Create Voting Phase (Admin)
```bash
curl -X POST http://localhost:8000/api/v1/voting-phases \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Voting Period",
    "description": "Public voting",
    "phase_type": "voting",
    "start_date": "2024-06-01 00:00:00",
    "end_date": "2024-06-07 23:59:59"
  }'
```

### 14. Activate Voting Phase (Admin)
```bash
curl -X POST http://localhost:8000/api/v1/voting-phases/1/activate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### 15. Get Current Active Phase
```bash
curl -X GET http://localhost:8000/api/v1/voting-phases/current \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 16. Create Judge (Admin)
```bash
curl -X POST http://localhost:8000/api/v1/judges \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 5,
    "background": "Ph.D. in Business Administration, MBA from Harvard",
    "profile_image": "https://example.com/judge1.jpg",
    "specialization": "Business & Entrepreneurship"
  }'
```

### 17. Publish Judge (Admin)
```bash
curl -X POST http://localhost:8000/api/v1/judges/1/publish \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### 18. List Judges (Public)
```bash
curl -X GET "http://localhost:8000/api/v1/judges?published_only=true" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 19. Admin Dashboard
```bash
curl -X GET http://localhost:8000/api/v1/dashboard/admin \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 20. Voter Dashboard
```bash
curl -X GET http://localhost:8000/api/v1/dashboard/voter \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 21. Evaluator Dashboard
```bash
curl -X GET http://localhost:8000/api/v1/dashboard/evaluator \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 22. Analyst Dashboard
```bash
curl -X GET http://localhost:8000/api/v1/dashboard/analyst \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 23. Fraud Detection Report (Admin)
```bash
curl -X GET http://localhost:8000/api/v1/votes/fraud-detection \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 24. Create User (Admin)
```bash
curl -X POST http://localhost:8000/api/v1/users \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Evaluator",
    "email": "evaluator@test.com",
    "password": "secure_password",
    "role_id": 2,
    "phone": "+1234567890",
    "bio": "Professional evaluator"
  }'
```

### 25. List All Users (Admin)
```bash
curl -X GET "http://localhost:8000/api/v1/users?page=1&role_id=2" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Response Examples

### Vote Success
```json
{
  "success": true,
  "message": "Vote recorded successfully",
  "data": {
    "vote_id": 123,
    "nominee_id": 1
  }
}
```

### Vote Statistics
```json
{
  "success": true,
  "message": "Nominee statistics retrieved successfully",
  "data": {
    "nominee_id": 1,
    "full_name": "Jane Smith",
    "public_votes": 150,
    "judge_votes": 5,
    "total_votes": 155,
    "percentage": 35.5,
    "rank": 1
  }
}
```

### Category Statistics
```json
{
  "success": true,
  "message": "Category statistics retrieved successfully",
  "data": {
    "category_id": 1,
    "nominees": [
      {
        "id": 1,
        "full_name": "Jane Smith",
        "bio": "Successful entrepreneur",
        "profile_image": "https://...",
        "vote_count": 155,
        "rank": 1,
        "percentage": 35.5
      }
    ],
    "total_votes": 436
  }
}
```

### Voter Dashboard
```json
{
  "success": true,
  "message": "Voter dashboard retrieved successfully",
  "data": {
    "active_phase": {
      "name": "Voting Phase",
      "start_date": "2024-06-01T00:00:00Z",
      "end_date": "2024-06-07T23:59:59Z"
    },
    "categories": [
      {
        "id": 1,
        "name": "Best Entrepreneur",
        "nominees": [
          {
            "id": 1,
            "full_name": "Jane Smith",
            "vote_count": 155,
            "rank": 1,
            "percentage": 35.5
          }
        ]
      }
    ],
    "judges": [
      {
        "id": 1,
        "name": "Dr. John Expert",
        "background": "Ph.D. in Business",
        "specialization": "Entrepreneurship"
      }
    ]
  }
}
```

---

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Success |
| 201 | Created - Resource created |
| 400 | Bad Request - Invalid data |
| 401 | Unauthorized - No token |
| 403 | Forbidden - Permission denied |
| 404 | Not Found - Resource not found |
| 422 | Unprocessable - Validation error |
| 500 | Server Error |

---

## Common Error Responses

### Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken"],
    "password": ["The password must be at least 8 characters"]
  }
}
```

### Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### Permission Denied
```json
{
  "success": false,
  "message": "You do not have permission to access this resource"
}
```

### Not Found
```json
{
  "success": false,
  "message": "Nominee not found"
}
```

---

## Testing Script (Bash)

Save as `test_api.sh` and run with `bash test_api.sh`:

```bash
#!/bin/bash

BASE_URL="http://localhost:8000/api/v1"
TOKEN=""

# Login
echo "=== Logging in ==="
RESPONSE=$(curl -s -X POST $BASE_URL/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@test.com",
    "password": "password"
  }')
echo $RESPONSE | jq .
TOKEN=$(echo $RESPONSE | jq -r '.data.token')

echo "Token: $TOKEN"
echo ""

# Get current user
echo "=== Getting current user ==="
curl -s -X GET $BASE_URL/auth/me \
  -H "Authorization: Bearer $TOKEN" | jq .
echo ""

# Get admin dashboard
echo "=== Getting admin dashboard ==="
curl -s -X GET $BASE_URL/dashboard/admin \
  -H "Authorization: Bearer $TOKEN" | jq .
```

---

## Postman Collection

Import this into Postman as a collection:
- Method: POST/GET/PUT/DELETE
- URL: http://localhost:8000/api/v1/{endpoint}
- Headers: Authorization: Bearer {{token}}
- Body: JSON format

---

## Notes

1. Replace `YOUR_TOKEN` with actual token from login
2. Replace ID numbers (1, 2, etc.) with actual resource IDs
3. Replace MAC address with actual device MAC address
4. All timestamps use ISO 8601 format
5. Pagination: Add `?page=1` to list endpoints
