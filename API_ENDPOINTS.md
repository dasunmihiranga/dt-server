# Digital Wallet API Endpoints Documentation

## Table of Contents
1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Base URLs and Headers](#base-urls-and-headers)
4. [Authentication Endpoints](#authentication-endpoints)
5. [User Management Endpoints](#user-management-endpoints)
6. [Wallet Endpoints](#wallet-endpoints)
7. [Transaction Endpoints](#transaction-endpoints)
8. [Bill Payment Endpoints](#bill-payment-endpoints)
9. [Dashboard Endpoints](#dashboard-endpoints)
10. [Activity Log Endpoints](#activity-log-endpoints)
11. [Response Format](#response-format)
12. [Error Handling](#error-handling)
13. [Status Codes](#status-codes)

## Overview

This documentation covers all API endpoints for the Digital Wallet Laravel backend. The API uses Laravel Sanctum for authentication and follows RESTful principles with consistent JSON responses.

## Authentication

All protected endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer {sanctum_token}
```

## Base URLs and Headers

**Base URL:** `http://localhost:8000/api`

**Common Headers:**
```json
{
  "Content-Type": "application/json",
  "Accept": "application/json",
  "Authorization": "Bearer {token}" // for protected routes
}
```

---

## Authentication Endpoints

### 1. Register User
**POST** `/auth/register`

Register a new user account.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "balance": 0.0
  },
  "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz",
  "message": "Registration successful"
}
```

**Validation Error (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password confirmation does not match."]
  }
}
```

---

### 2. Login User
**POST** `/auth/login`

Authenticate user and get access token.

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "balance": 1250.75
  },
  "token": "1|AbCdEfGhIjKlMnOpQrStUvWxYz",
  "message": "Login successful"
}
```

**Authentication Error (401):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

---

### 3. Logout User
**POST** `/auth/logout`

**Headers:** `Authorization: Bearer {token}`

Logout user and revoke current access token.

**Success Response (200):**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

---

### 4. Get Current User
**GET** `/auth/user`

**Headers:** `Authorization: Bearer {token}`

Get authenticated user information.

**Success Response (200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "balance": 1250.75,
    "created_at": "2025-01-15T10:30:00.000Z",
    "updated_at": "2025-01-20T14:45:00.000Z"
  }
}
```

---

## User Management Endpoints

### 5. Get User Profile
**GET** `/user/profile`

**Headers:** `Authorization: Bearer {token}`

Get detailed user profile information.

**Success Response (200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "balance": 1250.75,
    "created_at": "2025-01-15T10:30:00.000Z",
    "updated_at": "2025-01-20T14:45:00.000Z"
  }
}
```

---

### 6. Search User by Email
**GET** `/users/search?email={email}`

**Headers:** `Authorization: Bearer {token}`

Search for users by email address (excludes current user).

**Query Parameters:**
- `email` (required): Email address to search for

**Example Request:**
```
GET /users/search?email=jane@example.com
```

**Success Response (200):**
```json
{
  "success": true,
  "user": {
    "id": 2,
    "name": "Jane Smith",
    "email": "jane@example.com"
  }
}
```

**Not Found (404):**
```json
{
  "success": false,
  "message": "User not found"
}
```

---

## Wallet Endpoints

### 7. Get Wallet Balance
**GET** `/wallet/balance`

**Headers:** `Authorization: Bearer {token}`

Get current wallet balance.

**Success Response (200):**
```json
{
  "success": true,
  "balance": 1250.75
}
```

---

### 8. Account Top-up
**POST** `/topup` or **POST** `/wallet/topup`

**Headers:** `Authorization: Bearer {token}`

Add funds to user account.

**Request Body:**
```json
{
  "amount": 100.00,
  "payment_method": "credit_card"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "transaction": {
    "id": 123,
    "type": "topup",
    "amount": 100.0,
    "status": "completed",
    "reference": "TXN67890ABC",
    "created_at": "2025-01-20T15:30:00.000Z"
  },
  "new_balance": 1350.75,
  "message": "Top-up successful"
}
```

**Validation Error (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "amount": ["The amount must be at least 1."]
  }
}
```

---

### 9. Fund Transfer
**POST** `/transfer` or **POST** `/wallet/transfer`

**Headers:** `Authorization: Bearer {token}`

Transfer funds to another user.

**Request Body:**
```json
{
  "recipient_id": 2,
  "amount": 50.00,
  "note": "Payment for lunch"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "transaction": {
    "id": 125,
    "type": "transfer_sent",
    "amount": 50.0,
    "status": "completed",
    "reference": "TXN67890DEF",
    "recipient": "Jane Smith",
    "note": "Payment for lunch",
    "created_at": "2025-01-20T16:30:00.000Z"
  },
  "new_balance": 1150.75,
  "message": "Transfer successful"
}
```

**Insufficient Balance (400):**
```json
{
  "success": false,
  "message": "Insufficient balance for this transaction"
}
```

---

## Transaction Endpoints

### 10. Get Transaction History
**GET** `/transactions`

**Headers:** `Authorization: Bearer {token}`

Get paginated transaction history with optional filters.

**Query Parameters:**
- `type` (optional): Filter by transaction type (`topup`, `bill_payment`, `transfer_sent`, `transfer_received`)
- `status` (optional): Filter by status (`completed`, `pending`, `failed`)
- `search` (optional): Search term for descriptions
- `limit` (optional): Number of transactions per page (default: 50)
- `offset` (optional): Number of transactions to skip (default: 0)

**Example Request:**
```
GET /transactions?type=transfer_sent&status=completed&limit=10&offset=0
```

**Success Response (200):**
```json
{
  "success": true,
  "transactions": [
    {
      "id": 125,
      "type": "transfer_sent",
      "amount": 50.0,
      "description": "Transfer to Jane Smith",
      "status": "completed",
      "reference": "TXN67890DEF",
      "recipient": "Jane Smith",
      "note": "Payment for lunch",
      "created_at": "2025-01-20T16:30:00.000Z"
    },
    {
      "id": 124,
      "type": "bill_payment",
      "amount": 150.0,
      "description": "Bill payment to Electricity Company",
      "status": "completed",
      "reference": "TXN67890XYZ",
      "biller": "Electricity Company",
      "account_number": "ACC123456789",
      "created_at": "2025-01-20T16:00:00.000Z"
    }
  ],
  "pagination": {
    "total": 25,
    "limit": 10,
    "offset": 0,
    "has_more": true
  }
}
```

---

### 11. Get Single Transaction
**GET** `/transactions/{id}`

**Headers:** `Authorization: Bearer {token}`

Get details of a specific transaction.

**Success Response (200):**
```json
{
  "success": true,
  "transaction": {
    "id": 125,
    "user_id": 1,
    "type": "transfer_out",
    "amount": 50.0,
    "description": "Transfer to Jane Smith",
    "metadata": {
      "recipient_id": 2,
      "recipient_name": "Jane Smith",
      "recipient_email": "jane@example.com",
      "note": "Payment for lunch"
    },
    "reference": "TXN67890DEF",
    "status": "completed",
    "created_at": "2025-01-20T16:30:00.000Z",
    "updated_at": "2025-01-20T16:30:00.000Z"
  }
}
```

---

### 12. Get Transaction Statistics
**GET** `/transactions/stats`

**Headers:** `Authorization: Bearer {token}`

Get transaction statistics for the current user.

**Success Response (200):**
```json
{
  "success": true,
  "stats": {
    "total_topups": 500.0,
    "total_payments": 300.0,
    "total_transfers_out": 200.0,
    "total_transfers_in": 150.0,
    "transaction_count": 15,
    "recent_transactions": [
      {
        "id": 125,
        "type": "transfer_out",
        "amount": 50.0,
        "description": "Transfer to Jane Smith",
        "created_at": "2025-01-20T16:30:00.000Z"
      }
    ]
  }
}
```

---

## Bill Payment Endpoints

### 13. Get Available Billers
**GET** `/billers`

**Headers:** `Authorization: Bearer {token}`

Get list of all active billers.

**Success Response (200):**
```json
{
  "success": true,
  "billers": [
    {
      "id": 1,
      "name": "Electricity Company",
      "category": "Utilities",
      "description": "Monthly electricity bill payment",
      "is_active": true,
      "created_at": "2025-01-15T10:30:00.000Z",
      "updated_at": "2025-01-15T10:30:00.000Z"
    },
    {
      "id": 2,
      "name": "Water Department",
      "category": "Utilities",
      "description": "Monthly water bill payment",
      "is_active": true,
      "created_at": "2025-01-15T10:30:00.000Z",
      "updated_at": "2025-01-15T10:30:00.000Z"
    }
  ]
}
```

---

### 14. Pay Bill
**POST** `/bills/pay` or **POST** `/billers/pay`

**Headers:** `Authorization: Bearer {token}`

Make a bill payment to a registered biller.

**Request Body:**
```json
{
  "biller_id": 1,
  "amount": 150.00,
  "account_number": "ACC123456789"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "transaction": {
    "id": 124,
    "type": "bill_payment",
    "amount": 150.0,
    "status": "completed",
    "reference": "TXN67890XYZ",
    "biller": "Electricity Company",
    "account_number": "ACC123456789",
    "created_at": "2025-01-20T16:00:00.000Z"
  },
  "new_balance": 1200.75,
  "message": "Bill payment successful"
}
```

---

## Dashboard Endpoints

### 15. Get Dashboard Statistics
**GET** `/dashboard/stats`

**Headers:** `Authorization: Bearer {token}`

Get comprehensive dashboard statistics.

**Success Response (200):**
```json
{
  "success": true,
  "stats": {
    "current_balance": 1150.75,
    "total_income": 2000.0,
    "total_expenses": 849.25,
    "recent_transactions_count": 5,
    "pending_transactions_count": 0,
    "monthly_spending": {
      "current_month": 350.0,
      "previous_month": 280.5
    },
    "transaction_summary": {
      "topups": {
        "count": 3,
        "total_amount": 500.0
      },
      "transfers": {
        "sent": {
          "count": 8,
          "total_amount": 200.0
        },
        "received": {
          "count": 5,
          "total_amount": 150.0
        }
      },
      "bills": {
        "count": 4,
        "total_amount": 499.25
      }
    }
  }
}
```

---

## Activity Log Endpoints

### 16. Get User Activities
**GET** `/activities`

**Headers:** `Authorization: Bearer {token}`

Get paginated user activity log.

**Success Response (200):**
```json
{
  "success": true,
  "activities": {
    "data": [
      {
        "id": 1,
        "log_name": "default",
        "description": "User logged in",
        "subject_type": null,
        "subject_id": null,
        "causer_type": "App\\Models\\User",
        "causer_id": 1,
        "properties": [],
        "created_at": "2025-01-20T10:30:00.000Z",
        "updated_at": "2025-01-20T10:30:00.000Z"
      }
    ],
    "current_page": 1,
    "per_page": 20,
    "total": 50
  }
}
```

---

### 17. Get Single Activity
**GET** `/activities/{id}`

**Headers:** `Authorization: Bearer {token}`

Get details of a specific activity.

**Success Response (200):**
```json
{
  "success": true,
  "activity": {
    "id": 1,
    "log_name": "default",
    "description": "User transferred funds",
    "subject_type": null,
    "subject_id": null,
    "causer_type": "App\\Models\\User",
    "causer_id": 1,
    "properties": {
      "amount": 50.0,
      "recipient": "jane@example.com"
    },
    "created_at": "2025-01-20T10:30:00.000Z",
    "updated_at": "2025-01-20T10:30:00.000Z"
  }
}
```

---

## Response Format

All API responses follow a consistent format:

### Success Response
```json
{
  "success": true,
  "message": "Optional success message",
  "data": {
    // Response data varies by endpoint
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": [
      "Specific error message"
    ]
  }
}
```

---

## Error Handling

### Common Error Types

#### Validation Errors (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

#### Authentication Errors (401)
```json
{
  "success": false,
  "message": "Token expired or invalid"
}
```

#### Business Logic Errors (400)
```json
{
  "success": false,
  "message": "Insufficient balance for this transaction"
}
```

#### Not Found Errors (404)
```json
{
  "success": false,
  "message": "Resource not found"
}
```

---

## Status Codes

| Code | Description |
|------|-------------|
| 200 | OK - Request successful |
| 201 | Created - Resource created successfully |
| 400 | Bad Request - Business logic error |
| 401 | Unauthorized - Authentication required or failed |
| 403 | Forbidden - Access denied |
| 404 | Not Found - Resource not found |
| 422 | Unprocessable Entity - Validation failed |
| 500 | Internal Server Error - Server error |

---

## Transaction Types

| Type | Description |
|------|-------------|
| `topup` | Account top-up |
| `bill_payment` | Bill payment to billers |
| `transfer_sent` | Outgoing fund transfer |
| `transfer_received` | Incoming fund transfer |

---

## Notes

- All monetary amounts are returned as floats
- All timestamps are in ISO 8601 format (UTC)
- Pagination uses `limit` and `offset` parameters
- All protected endpoints require authentication
- Activity logging is automatic for all user actions

---

**API Version:** 1.0.0  
**Documentation Version:** 1.0.0  
**Last Updated:** July 27, 2025
