# Extraordinary African Event Platform - Backend API Documentation

## Base URL
```
http://localhost:8000/api/v1
```

## Authentication
All protected endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer {token}
```

---

## Public Endpoints (No Authentication Required)

### 1. User Registration
**POST** `/auth/register`

Request:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Response:
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "Voter"
    },
    "token": "token_here"
  }
}
```

### 2. User Login
**POST** `/auth/login`

Request:
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

Response: Same as registration

---

## Protected Endpoints (Authentication Required)

### Authentication Endpoints

#### 3. Logout
**POST** `/auth/logout`

#### 4. Get Current User
**GET** `/auth/me`

---

### Category Management (Admin Only)

#### 5. List Categories
**GET** `/categories?page=1`

#### 6. Get Category Details
**GET** `/categories/{id}`

#### 7. Create Category
**POST** `/categories`

Request:
```json
{
  "name": "Best Entrepreneur",
  "description": "Award for best entrepreneur",
  "icon": "star",
  "max_nominees": 10,
  "position": 1
}
```

#### 8. Update Category
**PUT** `/categories/{id}`

#### 9. Delete Category
**DELETE** `/categories/{id}`

---

### Nominees Management

#### 10. List Nominees
**GET** `/nominees?category_id=1&status=published&page=1`

#### 11. Get Nominee Details
**GET** `/nominees/{id}`

Returns nominee info with real-time vote statistics

#### 12. Create Nominee
**POST** `/nominees`

Request:
```json
{
  "full_name": "Jane Smith",
  "bio": "Accomplished entrepreneur",
  "email": "jane@example.com",
  "phone": "1234567890",
  "profile_image": "https://example.com/image.jpg",
  "category_id": 1
}
```

#### 13. Update Nominee (Evaluator/Admin)
**PUT** `/nominees/{id}`

#### 14. Delete Nominee (Evaluator/Admin)
**DELETE** `/nominees/{id}`

#### 15. Approve Nominee (Evaluator/Admin)
**POST** `/nominees/{id}/approve`

#### 16. Reject Nominee (Evaluator/Admin)
**POST** `/nominees/{id}/reject`

Request:
```json
{
  "rejection_reason": "Does not meet criteria"
}
```

#### 17. Publish Nominee (Evaluator/Admin)
**POST** `/nominees/{id}/publish`

---

### Nominations Management

#### 18. List Nominations
**GET** `/nominations?evaluation_status=pending&category_id=1&page=1`

#### 19. Create Nomination
**POST** `/nominations`

Request:
```json
{
  "nominee_id": 1,
  "category_id": 1,
  "nomination_reason": "Strong track record in business"
}
```

#### 20. Update Nomination
**PUT** `/nominations/{id}`

Only pending nominations can be updated.

#### 21. Evaluate Nomination (Evaluator)
**POST** `/nominations/{id}/evaluate`

Request:
```json
{
  "evaluation_status": "approved",
  "evaluator_notes": "Great candidate"
}
```

#### 22. Approve Nomination (Evaluator)
**POST** `/nominations/{id}/approve`

---

### Voting System

#### 23. Cast Vote
**POST** `/votes`

Request:
```json
{
  "nominee_id": 1,
  "mac_address": "00:1A:2B:3C:4D:5E"
}
```

Response:
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

**Features:**
- MAC address tracking for fraud prevention
- One vote per MAC address
- One vote per nominee per MAC address
- Real-time vote counting

#### 24. Get Category Vote Statistics
**GET** `/votes/stats/{categoryId}`

Returns all nominees in category with live vote counts.

#### 25. Get Nominee Vote Statistics
**GET** `/votes/candidate/{nomineeId}`

Returns:
```json
{
  "success": true,
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

#### 26. List All Votes (Admin/Analyst)
**GET** `/votes?category_id=1&vote_type=public_vote&page=1`

#### 27. Fraud Detection Report (Admin/Analyst)
**GET** `/votes/fraud-detection`

Returns suspicious voting patterns and MAC addresses.

---

### Judges Management

#### 28. List Judges
**GET** `/judges?published_only=true&page=1`

#### 29. Get Judge Details
**GET** `/judges/{id}`

#### 30. Create Judge (Admin Only)
**POST** `/judges`

Request:
```json
{
  "user_id": 5,
  "background": "Ph.D. in Business Administration",
  "profile_image": "https://example.com/judge.jpg",
  "specialization": "Entrepreneurship"
}
```

#### 31. Update Judge (Admin Only)
**PUT** `/judges/{id}`

#### 32. Delete Judge (Admin Only)
**DELETE** `/judges/{id}`

#### 33. Publish Judge (Admin Only)
**POST** `/judges/{id}/publish`

Makes judge visible to public voters.

#### 34. Unpublish Judge (Admin Only)
**POST** `/judges/{id}/unpublish`

---

### Voting Phases Management (Admin Only)

#### 35. List All Phases
**GET** `/voting-phases`

#### 36. Get Current Active Phase
**GET** `/voting-phases/current`

#### 37. Create Phase
**POST** `/voting-phases`

Request:
```json
{
  "name": "Voting Phase",
  "description": "Public voting period",
  "phase_type": "voting",
  "start_date": "2024-06-01 00:00:00",
  "end_date": "2024-06-07 23:59:59"
}
```

Phase types: `nomination`, `evaluation`, `voting`, `results`

#### 38. Update Phase
**PUT** `/voting-phases/{id}`

#### 39. Delete Phase
**DELETE** `/voting-phases/{id}`

#### 40. Activate Phase
**POST** `/voting-phases/{id}/activate`

Only one phase can be active at a time.

---

### Dashboard Endpoints

#### 41. Admin Dashboard
**GET** `/dashboard/admin` (Requires: super_admin role)

Returns:
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_nominees": 50,
      "total_votes": 10000,
      "total_categories": 5,
      "active_phase": "Voting Phase"
    },
    "votes_by_category": [
      {
        "category": "Best Entrepreneur",
        "vote_count": 2000
      }
    ],
    "top_nominees": [...],
    "phase_status": [...]
  }
}
```

#### 42. Evaluator Dashboard
**GET** `/dashboard/evaluator` (Requires: evaluator role)

Returns pending evaluations, evaluated count, approved nominees.

#### 43. Analyst Dashboard
**GET** `/dashboard/analyst` (Requires: voting_analyst role)

Returns vote statistics, distribution, fraud metrics.

#### 44. Judge Dashboard
**GET** `/dashboard/judge` (Requires: judge role)

Returns judge info, categories, nominees for judging.

#### 45. Voter Dashboard
**GET** `/dashboard/voter`

Returns active voting phase, published candidates with live vote counts, published judges.

---

### User Management (Admin Only)

#### 46. List Users
**GET** `/users?role_id=1&is_active=true&page=1`

#### 47. Get User Details
**GET** `/users/{id}`

#### 48. Create User
**POST** `/users`

Request:
```json
{
  "name": "Admin User",
  "email": "admin@example.com",
  "password": "secure_password",
  "role_id": 1,
  "phone": "1234567890",
  "bio": "System administrator"
}
```

#### 49. Update User
**PUT** `/users/{id}`

#### 50. Delete User
**DELETE** `/users/{id}`

---

## Error Responses

All errors follow this format:
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["error detail"]
  }
}
```

Common HTTP Status Codes:
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden (permission denied)
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## User Roles

1. **super_admin** - Full system access
2. **evaluator** - Evaluate nominations
3. **voting_analyst** - Analyze voting patterns
4. **judge** - Vote and rate nominees
5. **committee_member** - Committee participation
6. **voter** - Public voting

---

## Testing the API

### 1. Setup
```bash
cd c:\laragon\www\Web\ Project\extraordinary-african
composer install
php artisan migrate
php artisan db:seed --class=RoleAndPhaseSeeder
php artisan serve
```

### 2. Login and Test
Use Postman or cURL to test endpoints with the token from login response.

---

## MAC Address Tracking

The voting system uses MAC address tracking to prevent duplicate voting:

1. MAC address is captured from the client device
2. Server validates MAC address hasn't voted
3. Vote is recorded with MAC address
4. System tracks one vote per MAC address per event
5. Voting analyst can detect fraud patterns

**Security Note:** MAC addresses are validated against the votes table to prevent:
- Multiple votes from same device
- Duplicate votes for same nominee
- Vote manipulation

---

## Real-Time Vote Statistics

Vote statistics are updated in real-time when:
- A new vote is cast
- A nominee is published
- Voting phase changes

Statistics include:
- Public votes count
- Judge votes count
- Total votes
- Percentage of total votes
- Current rank in category

---

## Database Seeding

Run seeders to populate initial data:
```bash
php artisan db:seed --class=RoleAndPhaseSeeder
```

This creates:
- 6 default roles
- 4 voting phases (nomination, evaluation, voting, results)
