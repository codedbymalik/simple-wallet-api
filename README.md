# Bank Transaction REST API

A robust, PHP-based Bank Transaction REST API built from scratch without frameworks. The API provides complete banking functionality including user management, multiple accounts per user, and secure fund transfers with ACID compliance.

## Architecture

### Key Features
- **Custom Router**: No dependencies, custom URL routing in `Router.php`
- **Dependency Injection**: Services injected into Controllers via constructors
- **Database Singleton**: PDO-based singleton pattern in `Database.php`
- **ACID Transactions**: Full transaction support using `PDO::beginTransaction()`, `commit()`, and `rollBack()`
- **Error Handling**: Comprehensive try/catch with `error_log()` logging
- **Prepared Statements**: All database queries use prepared statements for safety
- **PSR-4 Autoloading**: Via Composer for clean namespace management

### Directory Structure
```
├── public/
│   ├── index.php          # Entry point
│   └── .htaccess          # Apache URL rewriting
├── src/
│   ├── Core/
│   │   ├── BaseModel.php       # Abstract base with generic CRUD
│   │   ├── BaseController.php  # Abstract base with JSON handling
│   │   ├── Database.php        # Singleton PDO instance
│   │   └── Router.php          # Custom URL router
│   ├── Controllers/
│   │   ├── UserController.php
│   │   ├── AccountController.php
│   │   └── TransactionController.php
│   ├── Models/
│   │   ├── UserModel.php
│   │   ├── AccountModel.php
│   │   └── TransactionModel.php
│   ├── Services/
│   │   ├── UserService.php
│   │   ├── AccountService.php
│   │   └── TransactionService.php
│   ├── Interfaces/
│   │   ├── UserServiceInterface.php
│   │   ├── AccountServiceInterface.php
│   │   └── TransactionServiceInterface.php
│   └── Config/
├── docker-compose.yaml    # Docker orchestration
├── Dockerfile             # PHP 8.2 Apache image
├── composer.json          # PSR-4 autoloading
├── schema.sql             # Database schema
└── .env                   # Environment variables
```

## Setup & Deployment

### Prerequisites
- Docker and Docker Compose
- Or PHP 8.0+, MySQL 8.0+, Composer

### Using Docker (Recommended)

1. **Start the containers:**
   ```bash
   docker-compose up --build
   ```

2. **Verify services:**
   - API: `http://localhost/api/users`
   - PHPMyAdmin: `http://localhost:8080`

3. **Initialize database:**
   Database schema is automatically loaded from `schema.sql` on first run.

### Manual Setup

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Configure database:**
   Update `.env` with your MySQL credentials

3. **Create database & tables:**
   ```bash
   mysql -u root -p < schema.sql
   ```

4. **Start PHP server:**
   ```bash
   php -S localhost:8000 -t public
   ```

## API Endpoints

### Users

#### Create User
```
POST /api/users
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "1234567890"
}

Response (201):
{
  "status": "success",
  "message": "User created successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "1234567890",
    "created_at": "2024-02-23 10:00:00",
    "updated_at": "2024-02-23 10:00:00"
  }
}
```

#### Get User
```
GET /api/users/{id}

Response (200):
{
  "status": "success",
  "message": "User retrieved successfully",
  "data": { ... }
}
```

#### Get All Users
```
GET /api/users

Response (200):
{
  "status": "success",
  "message": "Users retrieved successfully",
  "data": [ ... ]
}
```

#### Update User
```
PUT /api/users/{id}
Content-Type: application/json

{
  "name": "Jane Doe",
  "phone": "0987654321"
}
```

#### Delete User
```
DELETE /api/users/{id}

Response (200):
{
  "status": "success",
  "message": "User deleted successfully",
  "data": {}
}
```

### Accounts

#### Create Account (Rule: Balance cannot be negative)
```
POST /api/accounts
Content-Type: application/json

{
  "user_id": 1,
  "account_number": "ACC001",
  "balance": 5000.00,
  "currency": "USD"
}

Response (201): Account created
```

#### Get Account
```
GET /api/accounts/{id}
```

#### Get User's Accounts
```
GET /api/users/{userId}/accounts

Response (200):
{
  "status": "success",
  "message": "Accounts retrieved successfully",
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "account_number": "ACC001",
      "balance": 5000.00,
      "currency": "USD",
      "status": "active",
      "created_at": "2024-02-23 10:00:00"
    }
  ]
}
```

#### Update Account
```
PUT /api/accounts/{id}
Content-Type: application/json

{
  "balance": 6000.00,
  "status": "active"
}
```

#### Delete Account
```
DELETE /api/accounts/{id}
```

### Transactions

#### Transfer Funds (ACID Compliant)
```
POST /api/transactions/transfer
Content-Type: application/json

{
  "from_account_id": 1,
  "to_account_id": 2,
  "amount": 500.00,
  "description": "Payment for services"
}

Response (201):
{
  "status": "success",
  "message": "Transfer completed successfully",
  "data": {
    "id": 1,
    "from_account_id": 1,
    "to_account_id": 2,
    "amount": 500.00,
    "type": "transfer",
    "status": "completed",
    "description": "Payment for services",
    "created_at": "2024-02-23 10:05:00"
  }
}
```

**Rules:**
- Validates sufficient balance in source account
- Validates both accounts are active
- Uses ACID transactions (begins, commits, or rolls back)
- Automatically rolls back on validation failure

**Error Response (422 - Insufficient Funds):**
```json
{
  "error": "Insufficient funds. Current balance: 3000.00"
}
```

#### Get Account Transactions
```
GET /api/accounts/{accountId}/transactions

Response (200):
{
  "status": "success",
  "message": "Transactions retrieved successfully",
  "data": [
    {
      "id": 1,
      "from_account_id": 1,
      "to_account_id": 2,
      "amount": 500.00,
      "type": "transfer",
      "status": "completed",
      "description": "Payment for services",
      "created_at": "2024-02-23 10:05:00"
    }
  ]
}
```

#### Get Transaction
```
GET /api/transactions/{id}
```

## Error Handling

All errors return standardized JSON responses:

```json
{
  "error": "Error message description"
}
```

**HTTP Status Codes:**
- `200` - Success
- `201` - Created
- `400` - Bad Request (validation error)
- `404` - Not Found
- `422` - Unprocessable Entity (business logic error, e.g., insufficient funds)
- `500` - Internal Server Error

## Database Schema

### Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Accounts Table
```sql
CREATE TABLE accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    account_number VARCHAR(50) UNIQUE NOT NULL,
    balance DECIMAL(15, 2) NOT NULL CHECK (balance >= 0),
    currency VARCHAR(3) DEFAULT 'USD',
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Transactions Table
```sql
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    from_account_id INT NOT NULL,
    to_account_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    type ENUM('transfer', 'deposit', 'withdrawal') DEFAULT 'transfer',
    status ENUM('completed', 'pending', 'failed') DEFAULT 'completed',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_account_id) REFERENCES accounts(id) ON DELETE RESTRICT,
    FOREIGN KEY (to_account_id) REFERENCES accounts(id) ON DELETE RESTRICT
);
```

## Testing with cURL

```bash
# Create a user
curl -X POST http://localhost/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","phone":"1234567890"}'

# Create first account
curl -X POST http://localhost/api/accounts \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"account_number":"ACC001","balance":5000}'

# Create second account
curl -X POST http://localhost/api/accounts \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"account_number":"ACC002","balance":2000}'

# Transfer funds (ACID transaction)
curl -X POST http://localhost/api/transactions/transfer \
  -H "Content-Type: application/json" \
  -d '{"from_account_id":1,"to_account_id":2,"amount":1000,"description":"Payment"}'

# Get transaction history
curl http://localhost/api/accounts/1/transactions
```

## Core Classes Documentation

### Database.php (Singleton)
```php
// Get PDO instance
$pdo = Database::getInstance()->getConnection();

// Use in prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => 1]);
```

### BaseModel.php
```php
// Generic CRUD methods
$model->find($id);                    // Get by ID
$model->all();                        // Get all records
$model->findBy(['field' => 'value']); // Find by criteria
$model->findOneBy(['email' => '...']); // Find one record
$model->create($data);                // Create record
$model->update($id, $data);           // Update record
$model->delete($id);                  // Delete record
```

### BaseController.php
```php
// Response methods
$this->sendSuccess($data, 'Message', 200);
$this->sendError('Error message', 400);
$this->sendJson(['key' => 'value'], 200);

// Parse request
$payload = $this->getJsonPayload();
```

## Features Implemented

Custom Router (no framework dependencies)
Dependency Injection for Services
Singleton Database pattern
PSR-4 Autoloading via Composer
Abstract BaseModel with CRUD operations
Abstract BaseController with JSON handling
Service layer with validation
ACID transactions for fund transfers
Prepared statements for SQL injection prevention
Comprehensive error handling
Docker setup with MySQL and phpMyAdmin
Multiple accounts per user
Balance validation (no negative balances)
Account status management
Transaction history tracking
Environment configuration (.env)

## License

This project is provided as-is for educational purposes.
