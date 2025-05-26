# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the official website for BDE Info Montpellier (Student Association for Computer Science Students). It's a PHP-based web application using SQLite for data storage, built without a traditional framework but following MVC-like patterns.

## Common Development Commands

### Database Setup
```bash
# Initialize fresh database
php scripts/init_database.php

# Add test data (optional)
php scripts/init_test_data.php

# Apply pending schema updates
php scripts/apply_pending_schema.php
```

### Start Development Server
```bash
# Option 1: Use port 12000 (work-1)
./start_server.sh
# Access at: https://work-1-hbzrwquisdgvkxqv.prod-runtime.all-hands.dev

# Option 2: Use port 12001 (work-2)
./start_server_alt.sh
# Access at: https://work-2-hbzrwquisdgvkxqv.prod-runtime.all-hands.dev

# Manual start
php -S 0.0.0.0:12000 -t public
```

### Dependency Management
```bash
# Install PHP dependencies
composer install

# Update dependencies
composer update
```

## Architecture & Code Organization

### Single Entry Point Pattern
- All requests go through `public/index.php` which acts as a front controller
- Routes are handled with regex pattern matching in index.php
- Admin routes (starting with `/admin/`) require authentication and admin privileges

### Database Layer
- `src/Database.php` contains all database operations as a singleton class
- SQLite database files are stored in `database/` directory
- Schema is defined in `database/schema.sql`
- Database includes: users, events, memberships, event_registrations, user_memberships, pending_sumup_transactions

### Template System
- Templates are stored in `templates/` directory
- Layout template (`templates/layout.php`) provides consistent structure
- Admin templates are in `templates/admin/` subdirectory
- Templates are included directly with PHP, no template engine

### Payment Integration
- SumUp payment gateway integration for memberships and event payments
- Configuration in `config/sumup.php`
- Development mode simulates payments without actual transactions

### Authentication & Sessions
- PHP session-based authentication
- User roles: regular users and admins (is_admin flag)
- Failed login attempt tracking and account locking

## Key Development Notes

### File Permissions
Ensure proper permissions for SQLite database:
```bash
chmod -R 755 database/
chmod 664 database/*.sqlite database/*.db
```

### Image Uploads
Event images are stored in `public/uploads/events/`

### Utility Scripts
- `scripts/init_database.php` - Creates database structure
- `scripts/init_test_data.php` - Populates test data
- `scripts/auto_close_events.php` - Closes past events
- `scripts/create_event_images.php` - Generates default event images

### Testing
No automated test suite currently exists. Testing is manual.