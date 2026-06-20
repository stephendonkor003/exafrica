# 🧪 Testing Checklist & Validation Guide

## Database Validation

### ✅ Tables Created
```bash
php artisan tinker
# Then run:
Schema::getTables()
```

Expected tables: roles, users, categories, nominees, nominations, voting_phases, voters, votes, judges, vote_statistics, audit_logs

### ✅ Seed Data Loaded
```bash
# Optional: set INITIAL_SUPER_ADMIN_EMAIL and INITIAL_SUPER_ADMIN_PASSWORD in .env first.
php artisan config:clear

php artisan db:seed --class=RoleAndPhaseSeeder
# Check:
Role::all()                 # Should have 6 roles
VotingPhase::all()         # Should have 4 phases
```

---

## Authentication Testing

### 1. Register New User
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Voter",
    "email": "voter@test.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```
✅ Should return: `success: true` with user data and token

### 2. Login with Credentials
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@test.com",
    "password": "password"
  }'
```
✅ Should return: token and user info
✅ Save token for subsequent requests

### 3. Get Current User
```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```
✅ Should return: current authenticated user data

### 4. Logout
```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN"
```
✅ Should return: `success: true`

---

## Authorization Testing

### 1. Admin-Only Endpoints
```bash
# Create category (admin only)
curl -X POST http://localhost:8000/api/v1/categories \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "Test Category", "description": "Test", "max_nominees": 10}'
```
✅ Should return: 201 Created as admin
❌ Should return: 403 Forbidden as non-admin

### 2. Role Middleware
```bash
# Try accessing admin dashboard as non-admin
curl -X GET http://localhost:8000/api/v1/dashboard/admin \
  -H "Authorization: Bearer VOTER_TOKEN"
```
❌ Should return: 403 Forbidden

### 3. Multiple Role Access
```bash
# Evaluator can approve nominees
curl -X POST http://localhost:8000/api/v1/nominees/1/approve \
  -H "Authorization: Bearer EVALUATOR_TOKEN"
```
✅ Should work for evaluator

---

## Category Management Testing

### 1. Create Category
```bash
POST /api/v1/categories
{
  "name": "Best Entrepreneur",
  "description": "For entrepreneurs",
  "max_nominees": 10,
  "position": 1
}
```
✅ Check: Category created with ID
✅ Check: Created_by set to current user

### 2. List Categories
```bash
GET /api/v1/categories
```
✅ Check: Returns paginated list
✅ Check: Pagination metadata included

### 3. Get Single Category
```bash
GET /api/v1/categories/1
```
✅ Check: Returns category data with creator

### 4. Update Category
```bash
PUT /api/v1/categories/1
{"name": "Updated Category Name"}
```
✅ Check: Name updated

### 5. Delete Category
```bash
DELETE /api/v1/categories/1
```
✅ Check: Category removed from database

---

## Nominee Management Testing

### 1. Create Nominee
```bash
POST /api/v1/nominees
{
  "full_name": "Jane Smith",
  "bio": "Entrepreneur",
  "email": "jane@example.com",
  "category_id": 1
}
```
✅ Check: Nominee created with status "pending"
✅ Check: Vote count initialized to 0

### 2. List Nominees
```bash
GET /api/v1/nominees?category_id=1&status=published
```
✅ Check: Filters work
✅ Check: Pagination works

### 3. Approve Nominee
```bash
POST /api/v1/nominees/1/approve
```
✅ Check: Status changed to "approved"

### 4. Publish Nominee
```bash
POST /api/v1/nominees/1/publish
```
✅ Check: Status changed to "published"
✅ Check: Now visible to voters

### 5. Reject Nominee
```bash
POST /api/v1/nominees/1/reject
{"rejection_reason": "Does not meet criteria"}
```
✅ Check: Status changed to "rejected"
✅ Check: Rejection reason saved

---

## Voting System Testing

### ✅ Vote Recording
```bash
POST /api/v1/votes
{
  "nominee_id": 1,
  "mac_address": "00:1A:2B:3C:4D:5E"
}
```
✅ Should return: vote_id, nominee_id
✅ Check: Vote count incremented for nominee

### ✅ MAC Address Fraud Prevention
```bash
# Try voting again with same MAC
POST /api/v1/votes
{
  "nominee_id": 2,
  "mac_address": "00:1A:2B:3C:4D:5E"
}
```
❌ Should return: 403 error "You have already voted"

### ✅ Duplicate Vote Prevention
```bash
# Try voting same nominee with same MAC (different device scenario)
POST /api/v1/votes
{
  "nominee_id": 1,
  "mac_address": "00:1A:2B:3C:4D:5E"
}
```
❌ Should return: 403 error "You have already voted for this candidate"

### ✅ Unpublished Nominee Check
```bash
# Try voting for unpublished nominee
POST /api/v1/votes
{
  "nominee_id": 99,  # unpublished
  "mac_address": "AA:BB:CC:DD:EE:FF"
}
```
❌ Should return: 403 error "Nominee is not published"

---

## Vote Statistics Testing

### 1. Category Statistics
```bash
GET /api/v1/votes/stats/1
```
✅ Check: Returns all nominees in category
✅ Check: Vote counts accurate
✅ Check: Ranked correctly
✅ Check: Percentages calculated

### 2. Nominee Statistics
```bash
GET /api/v1/votes/candidate/1
```
✅ Check: Returns nominee details
✅ Check: Public and judge vote counts
✅ Check: Percentage and rank

### 3. Real-Time Updates
- Cast a vote
- Immediately check stats
- ✅ Should show new vote count

---

## Judge Management Testing

### 1. Create Judge
```bash
POST /api/v1/judges
{
  "user_id": 5,
  "background": "Ph.D. in Business",
  "specialization": "Entrepreneurship"
}
```
✅ Check: Judge created and linked to user

### 2. Publish Judge
```bash
POST /api/v1/judges/1/publish
```
✅ Check: is_published set to true
✅ Check: Now visible in public dashboard

### 3. List Published Judges
```bash
GET /api/v1/judges?published_only=true
```
✅ Check: Only published judges returned

### 4. Unpublish Judge
```bash
POST /api/v1/judges/1/unpublish
```
✅ Check: is_published set to false

---

## Voting Phase Testing

### 1. Create Phase
```bash
POST /api/v1/voting-phases
{
  "name": "Voting Phase",
  "phase_type": "voting",
  "start_date": "2024-06-01 00:00:00",
  "end_date": "2024-06-07 23:59:59"
}
```
✅ Check: Phase created

### 2. Get Current Phase
```bash
GET /api/v1/voting-phases/current
```
✅ Should return active phase if one exists
❌ Should return 404 if no active phase

### 3. Activate Phase
```bash
POST /api/v1/voting-phases/1/activate
```
✅ Check: Phase marked as active
✅ Check: Other phases marked inactive

### 4. List Phases
```bash
GET /api/v1/voting-phases
```
✅ Check: All phases returned in order

---

## Dashboard Testing

### 1. Admin Dashboard
```bash
GET /api/v1/dashboard/admin
```
✅ Check: summary with total_nominees, total_votes, total_categories
✅ Check: votes_by_category array
✅ Check: top_nominees list
✅ Check: phase_status array

### 2. Evaluator Dashboard
```bash
GET /api/v1/dashboard/evaluator
```
✅ Check: pending_evaluations count
✅ Check: evaluated_count
✅ Check: approved_nominees count

### 3. Analyst Dashboard
```bash
GET /api/v1/dashboard/analyst
```
✅ Check: vote_summary with breakdown
✅ Check: vote_distribution by category
✅ Check: unique_voters count
✅ Check: fraud_metrics

### 4. Judge Dashboard
```bash
GET /api/v1/dashboard/judge
```
✅ Check: judge_info with profile
✅ Check: categories with nominees
✅ Check: vote_count accurate

### 5. Voter Dashboard
```bash
GET /api/v1/dashboard/voter
```
✅ Check: active_phase info
✅ Check: categories with nominees
✅ Check: judges list
✅ Check: live vote counts

---

## User Management Testing

### 1. Create User (Admin)
```bash
POST /api/v1/users
{
  "name": "New User",
  "email": "newuser@test.com",
  "password": "secure_password",
  "role_id": 2
}
```
✅ Check: User created with role

### 2. List Users
```bash
GET /api/v1/users?page=1&role_id=2
```
✅ Check: Filters work
✅ Check: Pagination works

### 3. Update User
```bash
PUT /api/v1/users/1
{"name": "Updated Name", "is_active": false}
```
✅ Check: User data updated

### 4. Get User
```bash
GET /api/v1/users/1
```
✅ Check: Returns user with role

### 5. Delete User
```bash
DELETE /api/v1/users/1
```
✅ Check: User removed

---

## Fraud Detection Testing

### 1. Fraud Report
```bash
GET /api/v1/votes/fraud-detection
```
✅ Check: Returns suspicious_voters_count
✅ Check: Returns duplicate_votes_found
✅ Check: Returns suspicious_mac_addresses

### 2. Suspicious MAC Detection
- Have one MAC vote multiple times (should be blocked)
- Run fraud detection
- ✅ Should show MAC in suspicious list

---

## Error Handling Testing

### 1. Validation Errors
```bash
POST /api/v1/nominees
{"full_name": "Jane"}  # Missing category_id
```
✅ Should return: 422 with errors object

### 2. Unauthorized Access
```bash
GET /api/v1/users
# Without token
```
❌ Should return: 401 Unauthorized

### 3. Forbidden Access
```bash
GET /api/v1/dashboard/admin
# As non-admin
```
❌ Should return: 403 Forbidden

### 4. Not Found
```bash
GET /api/v1/nominees/99999
```
❌ Should return: 404 Not Found

### 5. Invalid Input
```bash
POST /api/v1/votes
{"nominee_id": "invalid"}
```
❌ Should return: 422 Validation Error

---

## Performance Testing

### 1. List Endpoint Performance
```bash
GET /api/v1/nominees?page=1
```
✅ Should return within 500ms

### 2. Vote Statistics Performance
```bash
GET /api/v1/votes/stats/1
```
✅ Should calculate and return within 1 second

### 3. Dashboard Performance
```bash
GET /api/v1/dashboard/admin
```
✅ Should aggregate and return within 2 seconds

---

## Data Integrity Testing

### 1. Vote Count Accuracy
- Create nominee with vote_count = 0
- Cast 5 votes
- Check stats
- ✅ Should show exactly 5 votes

### 2. Ranking Accuracy
- Create multiple nominees in category
- Cast different vote counts
- Get category stats
- ✅ Rankings should be correct

### 3. MAC Address Unique Constraint
- Try creating two votes with same mac_address
- ✅ Second should be rejected

### 4. Referential Integrity
- Create nominee with category_id
- Try deleting category
- ✅ Should cascade delete or prevent

---

## Final Verification Checklist

- [ ] Database migrations run without errors
- [ ] All 11 tables created
- [ ] Seeder runs and creates roles & phases
- [ ] Admin account can be created
- [ ] Login returns valid token
- [ ] Token authentication works
- [ ] All 50+ endpoints respond
- [ ] Role-based access control works
- [ ] Vote recording prevents duplicates
- [ ] Vote statistics calculate correctly
- [ ] All dashboards return data
- [ ] Error responses are consistent
- [ ] Pagination works on all list endpoints
- [ ] MAC address fraud prevention works
- [ ] Fraud detection reports generate

---

## Test Credentials

After setup, use these for testing:

**Admin Account:**
- Email: `admin@test.com`
- Password: `password`
- Role: `super_admin`

**Evaluator Account:**
- Create via API with role_id = 2

**Voter Account:**
- Register via `/auth/register`
- Role: `voter` (auto-assigned)

---

## Quick Test Script

Run this bash script to test main endpoints:

```bash
#!/bin/bash
TOKEN=""
BASE="http://localhost:8000/api/v1"

# Login
echo "=== LOGIN ==="
TOKEN=$(curl -s -X POST $BASE/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"password"}' \
  | jq -r '.data.token')

# Create category
echo "=== CREATE CATEGORY ==="
curl -s -X POST $BASE/categories \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","description":"Test","max_nominees":10}' | jq .

# List categories
echo "=== LIST CATEGORIES ==="
curl -s -X GET "$BASE/categories" \
  -H "Authorization: Bearer $TOKEN" | jq .

echo "All tests complete!"
```

---

## Success Criteria

✅ All authentication tests pass
✅ All CRUD operations work
✅ All dashboards return data
✅ Vote system prevents fraud
✅ Real-time statistics accurate
✅ Role-based access enforced
✅ Error handling consistent
✅ API responses standardized
✅ Performance acceptable
✅ Data integrity maintained
