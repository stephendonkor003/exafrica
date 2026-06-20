# Extraordinary African Event Platform - Backend Setup Guide

## Overview
A comprehensive Laravel backend system for managing event nominations, committee evaluations, public voting with fraud prevention, judge management, and real-time voting statistics with role-based dashboards.

## Features Implemented

✅ **Complete Database Schema** (11 tables)
- Users with role-based access
- Categories for award types
- Nominees with voting statistics
- Nominations with evaluation workflow
- Votes with MAC address fraud prevention
- Judges with publication status
- Voting phases with timeline management
- Audit logging

✅ **Authentication & Authorization**
- Laravel Sanctum token-based authentication
- 6 role-based user types
- Role middleware for access control
- Token management and logout

✅ **Voting System**
- MAC address tracking to prevent duplicate voting
- One vote per MAC address per nominee
- Real-time vote counting
- Public and judge vote separation
- Vote statistics with rankings

✅ **Dashboard System**
- Admin dashboard: All system metrics
- Evaluator dashboard: Pending evaluations
- Analyst dashboard: Vote patterns & fraud detection
- Judge dashboard: Judge profile & voting interface
- Voter dashboard: Live candidates & judges

✅ **API Endpoints** (50+ endpoints)
- RESTful API with consistent JSON responses
- Pagination support
- Error handling
- Comprehensive documentation

✅ **Fraud Prevention**
- MAC address validation
- Duplicate vote detection
- Voting analyst reports
- Block suspicious MAC addresses

---

## Installation & Setup

### Step 1: Navigate to Project Directory
```bash
cd "c:\laragon\www\Web Project\extraordinary-african"
```

### Step 2: Install Dependencies
```bash
composer install
```

### Step 3: Run Database Migrations
```bash
php artisan migrate
```

### Step 4: Seed Initial Data
```bash
php artisan db:seed --class=RoleAndPhaseSeeder
```

This creates:
- 6 roles (super_admin, evaluator, voting_analyst, judge, committee_member, voter)
- 9 award categories
- 4 voting phases (nomination, evaluation, voting, results)
- One active super admin login: `donkors@africanunion.org` / `Amodon@2063`

### Step 5: Start the Development Server
```bash
php artisan serve
```

Server will start at: `http://localhost:8000`

---

## Testing the API

### 1. Login to Get Token
**POST** `http://localhost:8000/api/v1/auth/login`

Request Body:
```json
{
  "email": "admin@test.com",
  "password": "password"
}
```

Response will include:
```json
{
  "success": true,
  "data": {
    "user": {...},
    "token": "YOUR_TOKEN_HERE"
  }
}
```

### 2. Use Token for Protected Endpoints
Add to header: `Authorization: Bearer YOUR_TOKEN_HERE`

Example: Get Current User
**GET** `http://localhost:8000/api/v1/auth/me`

### 3. Testing Workflow

#### Create Category (as admin)
```bash
POST /api/v1/categories
{
  "name": "Best Entrepreneur",
  "description": "Award for best entrepreneur",
  "max_nominees": 10,
  "position": 1
}
```

#### Create Nominee
```bash
POST /api/v1/nominees
{
  "full_name": "Jane Smith",
  "bio": "Successful entrepreneur",
  "email": "jane@example.com",
  "phone": "1234567890",
  "category_id": 1
}
```

#### Approve Nominee (as evaluator)
```bash
POST /api/v1/nominees/{id}/approve
```

#### Publish Nominee (as evaluator)
```bash
POST /api/v1/nominees/{id}/publish
```

#### Cast Vote
```bash
POST /api/v1/votes
{
  "nominee_id": 1,
  "mac_address": "00:1A:2B:3C:4D:5E"
}
```

#### View Vote Statistics
```bash
GET /api/v1/votes/stats/1
GET /api/v1/votes/candidate/1
```

#### Access Dashboards
```bash
GET /api/v1/dashboard/admin       (super_admin)
GET /api/v1/dashboard/evaluator   (evaluator)
GET /api/v1/dashboard/analyst     (voting_analyst)
GET /api/v1/dashboard/judge       (judge)
GET /api/v1/dashboard/voter       (any authenticated user)
```

---

## Project Structure

```
extraordinary-african/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── BaseController.php          (Base response methods)
│   │   │   ├── AuthController.php          (Login/Register/Logout)
│   │   │   ├── CategoryController.php      (Category CRUD)
│   │   │   ├── NomineeController.php       (Nominee management)
│   │   │   ├── NominationController.php    (Nomination evaluation)
│   │   │   ├── VoteController.php          (Voting system)
│   │   │   ├── JudgeController.php         (Judge management)
│   │   │   ├── VotingPhaseController.php   (Phase management)
│   │   │   ├── DashboardController.php     (All dashboards)
│   │   │   └── UserController.php          (User management)
│   │   └── CheckRole.php                   (Role middleware)
│   ├── Models/
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Category.php
│   │   ├── Nominee.php
│   │   ├── Nomination.php
│   │   ├── Vote.php
│   │   ├── Voter.php
│   │   ├── Judge.php
│   │   ├── VotingPhase.php
│   │   ├── VoteStatistic.php
│   │   └── AuditLog.php
├── database/
│   ├── migrations/          (11 migration files)
│   └── seeders/
│       └── RoleAndPhaseSeeder.php
├── routes/
│   ├── api.php              (All API routes)
│   └── web.php
├── API_DOCUMENTATION.md     (Complete API reference)
└── bootstrap/
    └── app.php              (App configuration)
```

---

## Database Schema

### Tables

1. **roles** - User roles (6 types)
2. **users** - User accounts with role_id
3. **categories** - Event categories created by admin
4. **nominees** - People nominated for awards
5. **nominations** - Nomination records with evaluation
6. **voting_phases** - Timeline phases (nomination → evaluation → voting → results)
7. **voters** - MAC address tracking for fraud prevention
8. **votes** - All votes with MAC address & IP tracking
9. **judges** - Published judges for voting
10. **vote_statistics** - Real-time vote counts & rankings
11. **audit_logs** - Activity logging for compliance

---

## Key Features Explained

### 1. MAC Address Fraud Prevention
- Each voter is tracked by MAC address
- System prevents:
  - Multiple votes from same device
  - Duplicate votes for same nominee
  - Vote manipulation

### 2. Real-Time Vote Statistics
- Vote counts update immediately
- Rankings calculated per category
- Percentage of total votes shown
- Visible to voters during voting phase

### 3. Role-Based Dashboards
Each role sees relevant information:
- **Admin**: All metrics, all users, phase control
- **Evaluator**: Pending nominations to evaluate
- **Analyst**: Vote patterns, fraud reports
- **Judge**: Their profile, voting interface
- **Voter**: Live candidates with votes, judges

### 4. Voting Phases
Four controlled phases:
- **Nomination**: Accept nominees
- **Evaluation**: Committee evaluates
- **Voting**: Public voting period
- **Results**: Announce winners

### 5. Evaluation Workflow
1. Nominations submitted
2. Evaluators review and approve/reject
3. Approved nominees published
4. Voters vote during voting phase
5. Results announced

---

## API Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {...}
}
```

### Paginated Response
```json
{
  "success": true,
  "message": "Data retrieved",
  "data": [...],
  "pagination": {
    "total": 100,
    "per_page": 20,
    "current_page": 1,
    "last_page": 5,
    "from": 1,
    "to": 20
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["Error detail"]
  }
}
```

---

## User Roles & Permissions

| Role | Can | Cannot |
|------|-----|--------|
| super_admin | Everything | Nothing |
| evaluator | Evaluate nominees, approve/reject | Manage users, phases |
| voting_analyst | View votes, fraud detection | Create nominees, vote |
| judge | Vote, see statistics | Manage phases, users |
| committee_member | Participate in evaluations | Manage system |
| voter | Vote during voting phase | See other voters |

---

## Configuration

### Database
- Using SQLite (database.sqlite)
- Located at: `database/database.sqlite`
- To use MySQL, update `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=extraordinary_african
DB_USERNAME=root
DB_PASSWORD=
```

### Authentication
- Using Laravel Sanctum
- Token-based API authentication
- Tokens stored in `personal_access_tokens` table

### Cache
- Using file-based caching
- For production, consider Redis: `CACHE_STORE=redis`

---

## Troubleshooting

### Migration Fails
```bash
php artisan migrate:fresh --seed
```

### Token Invalid
```bash
php artisan tinker
# Delete all tokens
DB::table('personal_access_tokens')->delete();
```

### Database Locked (SQLite)
```bash
rm database/database.sqlite
php artisan migrate --seed
```

### Roles Not Found
```bash
php artisan db:seed --class=RoleAndPhaseSeeder
```

---

## Future Enhancements

- Email notifications for nominees & voters
- Image upload for nominees & judges
- Advanced fraud detection algorithms
- Vote recounting & audit trails
- Bulk user import
- Voting result exports
- SMS voting support
- Real-time WebSocket updates
- Mobile app API
- Analytics dashboard

---

## Support

For API issues, check:
1. `API_DOCUMENTATION.md` - Complete API reference
2. Database migrations - Table structure
3. Model relationships - Data connections
4. Controller methods - Business logic

---

## License

This project is part of the Extraordinary African Event Platform.
