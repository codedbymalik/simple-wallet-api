# Quick Start Guide - Bank Transaction API

## Start with Docker (Recommended)

### Prerequisites
- Docker and Docker Compose installed

### Step 1: Build and Start Services
```bash
cd "/Users/zohaibmalik/DATA ENGINEERING/PHP Projects/simple wallet"
docker-compose up --build -d
```

### Step 2: Verify Services
```bash
# Check if containers are running
docker-compose ps

# Output should show:
# NAME              STATUS      PORTS
# bank_api_db       Up          3306->3306/tcp
# bank_api_php      Up          0.0.0.0:80->80/tcp
# bank_api_phpmyadmin Up       0.0.0.0:8080->80/tcp
```

### Step 3: Access Services
- **API**: http://localhost/api/users
- **PHPMyAdmin**: http://localhost:8080
  - User: `bank_user`
  - Password: `bank_password`

### Step 4: Test API
```bash
# Test creating a user
curl -X POST http://localhost/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","phone":"555-0000"}'
```

---

## Manual Setup (Without Docker)

### Prerequisites
- PHP 8.0+
- MySQL 8.0+
- Composer

### Step 1: Install Dependencies
```bash
cd "/Users/zohaibmalik/DATA ENGINEERING/PHP Projects/simple wallet"
composer install
```

### Step 2: Configure Database
1. Create a MySQL database:
```sql
CREATE DATABASE bank_db;
CREATE USER 'bank_user'@'localhost' IDENTIFIED BY 'bank_password';
GRANT ALL PRIVILEGES ON bank_db.* TO 'bank_user'@'localhost';
FLUSH PRIVILEGES;
```

2. Load schema:
```bash
mysql -u bank_user -p bank_db < schema.sql
```

### Step 3: Update Environment
Edit `.env`:
```
DB_HOST=localhost
DB_USER=bank_user
DB_PASSWORD=bank_password
DB_NAME=bank_db
```

### Step 4: Start PHP Server
```bash
php -S localhost:8000 -t public
```

Access API at: http://localhost:8000/api/users

---

## Quick API Test Examples

### Create a User
```bash
curl -X POST http://localhost/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "555-1234"
  }'
```

### Create an Account
```bash
curl -X POST http://localhost/api/accounts \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "account_number": "ACC001",
    "balance": 5000,
    "currency": "USD"
  }'
```

### Create Another Account
```bash
curl -X POST http://localhost/api/accounts \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "account_number": "ACC002",
    "balance": 3000,
    "currency": "USD"
  }'
```

### Transfer Funds (ACID Transaction)
```bash
curl -X POST http://localhost/api/transactions/transfer \
  -H "Content-Type: application/json" \
  -d '{
    "from_account_id": 1,
    "to_account_id": 2,
    "amount": 1000,
    "description": "Payment"
  }'
```

### View Transaction History
```bash
curl http://localhost/api/accounts/1/transactions
```

### View All User Accounts
```bash
curl http://localhost/api/users/1/accounts
```

---

## Project Structure

```
simple wallet/
├── src/
│   ├── Core/              # Core infrastructure
│   │   ├── Database.php   # Singleton PDO instance
│   │   ├── Router.php     # Custom URL router
│   │   ├── BaseModel.php  # Abstract model with CRUD
│   │   └── BaseController.php # Abstract controller
│   ├── Controllers/       # HTTP request handlers
│   ├── Models/            # Database models
│   ├── Services/          # Business logic
│   └── Interfaces/        # Service contracts
├── public/
│   ├── index.php          # Entry point
│   └── .htaccess          # URL rewriting
├── docker-compose.yaml    # Docker orchestration
├── Dockerfile             # PHP 8.2 Apache image
├── composer.json          # PSR-4 autoloading
├── schema.sql             # Database schema
└── .env                   # Configuration
```

---

## Key Features

**No Framework** - Pure PHP with custom router and DI
**ACID Transactions** - Safe fund transfers with rollback
**Prepared Statements** - SQL injection protection
**Docker Ready** - Complete containerization
**PSR-4 Autoloading** - Clean namespace organization
**Error Handling** - Comprehensive validation and logging
**RESTful API** - Standardized JSON responses

---

## Troubleshooting

### Database Connection Error
```
Check .env file has correct credentials
php -r "print_r($_ENV);" to verify loaded from .env
```

### 404 errors on API routes
```
Ensure Apache mod_rewrite is enabled
Check .htaccess in public/ directory
```

### Port Already in Use
```
# Kill process on port 80 (macOS)
sudo lsof -ti:80 | xargs kill -9

# Or use different port in PHP
php -S localhost:8080 -t public
```

### Permission Denied (Dockerfile)
```
chmod +x Dockerfile
chmod +x docker-compose.yaml
```

---

## Database Diagram

```
Users (1) ──→ (*) Accounts
             ↓
        (FROM/TO) Transactions
        
Users:
  - id (PK)
  - name
  - email (UNIQUE)
  - phone

Accounts:
  - id (PK)
  - user_id (FK → Users)
  - account_number (UNIQUE)
  - balance (DECIMAL, CHECK >= 0)
  - currency
  - status (active|inactive|blocked)

Transactions:
  - id (PK)
  - from_account_id (FK → Accounts)
  - to_account_id (FK → Accounts)
  - amount
  - type (transfer|deposit|withdrawal)
  - status (completed|pending|failed)
  - created_at
```

---

## Full Documentation

See `README.md` for complete API documentation, all endpoints, error codes, and examples.

---

## Common Tasks

### Rebuild Docker Image
```bash
docker-compose down
docker-compose up --build -d
```

### View Docker Logs
```bash
docker-compose logs -f php
docker-compose logs -f db
```

### Execute Database Query
```bash
docker-compose exec db mysql -u bank_user -p bank_db
```

### Access PHP Container
```bash
docker-compose exec php bash
```

---

**Ready to start?** Run `docker-compose up --build -d` and access the API!
