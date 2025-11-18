# UI Dashboard System - Complete Implementation

A comprehensive multi-tenant dashboard and redirect management system built with PHP 8.3+, PDO/MySQL, and Tailwind CSS.

## Overview

This system provides a complete traffic routing and redirect management platform with three distinct user roles:
- **Superadmin**: Manages admins, tags, and global system controls
- **Admin (Pre-admin)**: Manages users, parked domains, routing inputs, and target URLs
- **User**: Creates redirect rules, manages domains, and views metrics

## Features

### 1. Superadmin Dashboard
- Admin Management (CRUD operations)
- Tag Management with assignment synchronization
- System-wide On/Off toggle
- Target URL management
- Country configuration (ISO alpha-2 codes)

### 2. Pre-admin Dashboard
- User Management (CRUD operations)
- Parked Domains (1-10 domains, automatic wildcard handling)
- Optional Cloudflare DNS synchronization
- Country whitelist management
- Target URL configuration with {domain} placeholder support
- Device scope selection (WAP, WEB, ALL)

### 3. User Dashboard
- Redirect Rule Management (mute_unmute, random_route, static_route)
- Parked Domain Management (1-10 domains)
- Target URL Management with dynamic domain replacement
- Country Whitelist Configuration
- Device Scope Configuration
- Domain Selection Strategy (random_global, random_user, specific)
- Real-time Metrics and Analytics

### 4. Reporting Dashboard
- Metrics: clicks, earnings, conversions, country, IP, device
- Date Ranges: Today, Yesterday, Weekly, Custom
- Real-time performance monitoring
- Country and device statistics

### 5. Redirect System (Safe Query)
- REST API endpoint: `/api/redirect.php`
- Security: Input validation, prepared statements, anti-abuse
- Performance: Fast decision engine with rule evaluation
- Logging: Comprehensive redirect logs with analytics

## Technology Stack

- **Backend**: PHP 8.3+ with PDO
- **Database**: MySQL with prepared statements
- **Frontend**: HTML + Tailwind CSS + jQuery
- **Authentication**: Session-based with Argon2ID password hashing
- **Security**: Input validation, CSRF protection, role-based access control

## Directory Structure

```
/
├── api/                    # REST API endpoints
│   ├── auth.php           # Authentication
│   ├── admins.php         # Superadmin: Admin CRUD
│   ├── tags.php           # Superadmin: Tag management
│   ├── users.php          # Admin: User CRUD
│   ├── domains.php        # Admin/User: Parked domains
│   ├── countries.php      # Admin/User: Country whitelist
│   ├── target_urls.php    # Admin/User: Target URLs
│   ├── rules.php          # User: Redirect rules
│   ├── metrics.php        # User: Analytics
│   └── redirect.php       # Public: Redirect engine
│
├── config/
│   └── database.php       # Database connection
│
├── db/
│   └── schema.sql         # Complete database schema
│
├── includes/              # Core utilities
│   ├── Auth.php           # Session management
│   ├── Validator.php      # Input validation
│   ├── GeoIP.php          # IP geolocation & device detection
│   └── RedirectLogic.php  # Redirect decision engine
│
└── public/                # Dashboard UIs
    ├── index.php          # Redirect to login
    ├── login.php          # Login page
    ├── superadmin.php     # Superadmin dashboard
    ├── admin.php          # Admin dashboard
    └── user.php           # User dashboard
```

## Installation

### 1. Database Setup

```bash
mysql -u root -p < db/schema.sql
```

### 2. Environment Configuration

Set the following environment variables or modify `config/database.php`:

```bash
export DB_HOST=localhost
export DB_NAME=ui_dashboard
export DB_USER=root
export DB_PASS=your_password
```

### 3. Create Initial Superadmin

```sql
INSERT INTO superadmins (username, email, password, is_active)
VALUES ('admin', 'admin@example.com', '$argon2id$v=19$m=65536,t=4,p=1$...', 1);
```

Generate password hash:
```php
echo password_hash('your_password', PASSWORD_ARGON2ID);
```

## API Documentation

### Authentication

**POST /api/auth.php**
```json
{
  "action": "login",
  "username": "admin",
  "password": "password",
  "role": "superadmin|admin|user"
}
```

### Redirect Engine

**GET /api/redirect.php?user_id={id}&token={token}**

Returns:
```json
{
  "decision": "redirect|normal",
  "target": "https://example.com/offer",
  "rule_applied": 123
}
```

### Metrics

**GET /api/metrics.php?date_range=today|yesterday|weekly|custom&start_date=YYYY-MM-DD&end_date=YYYY-MM-DD**

Returns comprehensive analytics including clicks, conversions, country stats, and device stats.

## Security Features

1. **Authentication**: Session-based with 1-hour timeout
2. **Password Hashing**: Argon2ID (memory-hard, GPU-resistant)
3. **Input Validation**: Comprehensive validation via Validator class
4. **SQL Injection Prevention**: Prepared statements for all queries
5. **Role-Based Access Control**: Strict role verification on all endpoints
6. **Audit Logging**: System logs for all administrative actions

## Rule Types

### 1. Mute/Unmute Cycle
Cycles between active and inactive periods with configurable durations.

### 2. Random Route
Randomly selects from user's target URLs, subject to filters.

### 3. Static Route
Always redirects to a configured target URL, subject to filters.

## License

Proprietary - All rights reserved
