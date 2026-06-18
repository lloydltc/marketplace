# Salma Tech Automotive Marketplace - Task Execution Order

## Overview

This document provides the **strict implementation sequence** for building Salma Tech Automotive Marketplace. Following this order is essential to:

- Minimize rework and refactoring
- Reduce AI token consumption
- Ensure dependencies are satisfied before implementation
- Allow parallel development after Phase 3
- Reduce technical debt and bugs

**Critical Rule**: Do not skip phases. Each phase depends on previous phases.

---

## Implementation Pyramid

```
Phase 20: Deployment & DevOps
   ↓
Phase 19: Production Hardening & Security
   ↓
Phase 18: Audit Logging & Monitoring
   ↓
Phase 17: Reporting & Analytics
   ↓
Phases 16-8: Features (can run in parallel after Phase 3)
   ↓
Phase 7: Media Management (foundation for products/vehicles)
   ↓
Phase 6: Vehicle Listings (extends Phase 5)
   ↓
Phase 5: Products (extends Phase 3)
   ↓
Phase 4: Categories (foundation for products)
   ↓
Phase 3: Vendor Management (extends Phase 2)
   ↓
Phase 2: Authentication & Roles (foundation for all)
   ↓
Phase 1: Project Foundation (must be first)
```

---

## Phase 1: Project Foundation

**Duration**: 3-5 days  
**Team Size**: 1-2 developers  
**Estimated Effort**: 40-60 hours  

### Objectives

- Setup Laravel project with all dependencies
- Configure Docker environment
- Setup database infrastructure
- Create project file structure
- Setup testing framework
- Configure CI/CD pipeline basics

### Tasks

#### 1.1 Project Initialization

- [ ] Create Laravel 12 project: `composer create-project laravel/laravel marketplace`
- [ ] Initialize Git repository: `git init`
- [ ] Create .gitignore (exclude .env, vendor, node_modules, storage)
- [ ] Setup README.md with project overview
- [ ] Create CONTRIBUTING.md with development guidelines

#### 1.2 Dependency Installation

- [ ] Install Laravel Breeze: `composer require laravel/breeze --dev`
- [ ] Install testing dependencies: `composer require --dev phpunit/phpunit pestphp/pest`
- [ ] Install code analysis: `composer require --dev php-parallel-lint/php-parallel-lint phpstan/phpstan`
- [ ] Install Node dependencies: `npm install`
- [ ] Setup TailwindCSS: `npm install -D tailwindcss postcss autoprefixer`
- [ ] Setup Alpine.js: `npm install alpinejs`

#### 1.3 Configuration Files

- [ ] Copy .env.example to .env
- [ ] Generate APP_KEY: `php artisan key:generate`
- [ ] Configure database (PostgreSQL connection)
- [ ] Configure Redis connection
- [ ] Configure cache driver (redis)
- [ ] Configure queue connection (redis)
- [ ] Setup mail driver (log in dev, production in prod)

#### 1.4 Database Setup

- [ ] Create PostgreSQL database
- [ ] Create database user with limited permissions
- [ ] Run base migrations: `php artisan migrate`
- [ ] Create database seeders directory structure

#### 1.5 Docker Configuration

- [ ] Create Dockerfile for PHP-FPM
- [ ] Create docker-compose.yml with services (app, nginx, postgres, redis)
- [ ] Create Nginx configuration file
- [ ] Test Docker build: `docker-compose build`
- [ ] Test Docker services startup: `docker-compose up`

#### 1.6 Directory Structure

```
app/
├── Modules/              # Created (empty)
├── Shared/               # Created (empty)
└── Exceptions/

database/
├── migrations/           # Create base migrations
├── seeders/              # Created (empty)
└── factories/            # Created (empty)

tests/
├── Unit/                 # Created (empty)
├── Feature/              # Created (empty)
└── TestCase.php          # Extend with custom assertions

docker/
├── Dockerfile            # Created
├── nginx.conf            # Created
└── php.ini               # Created (optimization settings)

config/                   # Update for redis, cache, queue
```

#### 1.7 Testing Framework

- [ ] Configure PHPUnit: `phpunit.xml`
- [ ] Setup test database (separate from dev)
- [ ] Create TestCase base class
- [ ] Create database factories
- [ ] Create database seeders for testing
- [ ] Configure code coverage reporting

#### 1.8 Code Quality Tools

- [ ] Setup PHPStan (static analysis)
- [ ] Setup PHP_CodeSniffer (PSR-12)
- [ ] Setup Laravel Pint (code formatting)
- [ ] Create pre-commit hooks script
- [ ] Setup IDE configuration (.editorconfig)

### Deliverables

- ✅ Working Laravel application
- ✅ Docker environment ready
- ✅ Database schema initialized
- ✅ Testing infrastructure in place
- ✅ Code quality tools configured
- ✅ Git repository ready
- ✅ CI/CD pipeline skeleton

### Validation Criteria

- `docker-compose up` successfully starts all services
- `php artisan tinker` works (can access models)
- `php artisan test` runs successfully (0 tests pass/fail)
- `php artisan serve` starts development server
- Database migrations run successfully
- No console errors or warnings

### Documentation Updates

- [ ] Update README.md with setup instructions
- [ ] Create DEVELOPMENT.md with local setup guide
- [ ] Document database schema in SCHEMA.md
- [ ] Create architecture diagrams in ARCHITECTURE.md

---

## Phase 2: Authentication & Authorization

**Duration**: 5-7 days  
**Dependencies**: Phase 1  
**Team Size**: 1-2 developers  
**Estimated Effort**: 60-80 hours  

### Objectives

- Implement user authentication system
- Setup role-based access control (RBAC)
- Create user models and migrations
- Implement authorization policies
- Setup session management
- Implement password security

### Tasks

#### 2.1 User Model & Migration

- [ ] Create User model (extend Authenticatable)
- [ ] Create users migration with fields:
  - id (UUID), email (unique), password_hash, phone, role, verified_at, deleted_at, timestamps
- [ ] Create user factories for testing
- [ ] Setup password hashing and verification
- [ ] Implement SoftDeletes trait

#### 2.2 Authentication

- [ ] Setup Laravel Breeze scaffold
- [ ] Implement registration controller
- [ ] Implement login controller
- [ ] Implement logout functionality
- [ ] Implement password reset workflow
- [ ] Implement email verification
- [ ] Create authentication middleware
- [ ] Setup session configuration

#### 2.3 Role-Based Access Control

- [ ] Create Role enum with values: SUPER_ADMIN, ADMIN, VENDOR, PRIVATE_SELLER, CUSTOMER
- [ ] Add role column to users migration
- [ ] Create Permission model
- [ ] Create Role model
- [ ] Create RolePermission pivot table
- [ ] Implement permissions checking service
- [ ] Create authorization middleware

#### 2.4 Authorization Policies

- [ ] Create base Policy class
- [ ] Implement policy authorization middleware
- [ ] Create gate checks for common permissions
- [ ] Implement policy for User resource
- [ ] Test authorization scenarios

#### 2.5 Email Verification

- [ ] Create email verification middleware
- [ ] Implement email verification notification
- [ ] Create verification token table
- [ ] Implement token validation
- [ ] Setup SMTP or sendmail for testing

#### 2.6 Two-Factor Authentication (Phase 2+, Optional)

- [ ] Create 2FA model
- [ ] Implement OTP generation and validation
- [ ] Create 2FA middleware
- [ ] Create backup codes for recovery

### Deliverables

- ✅ User authentication system (register, login, logout)
- ✅ Email verification workflow
- ✅ Password reset system
- ✅ Role-based access control
- ✅ Authorization policies
- ✅ Session management

### Database Changes

```php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('email')->unique();
    $table->string('password_hash');
    $table->string('phone', 20)->nullable();
    $table->enum('role', ['super_admin', 'admin', 'vendor', 'private_seller', 'customer']);
    $table->timestamp('verified_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index('role');
    $table->index('verified_at');
});

Schema::create('roles', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name')->unique();
    $table->text('description')->nullable();
    $table->timestamps();
});

Schema::create('permissions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name')->unique();
    $table->text('description')->nullable();
    $table->timestamps();
});

Schema::create('role_permission', function (Blueprint $table) {
    $table->uuid('role_id');
    $table->uuid('permission_id');
    $table->primary(['role_id', 'permission_id']);
    
    $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
    $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
});
```

### Tests Required

- [ ] Registration with valid data
- [ ] Registration with invalid email
- [ ] Registration with weak password
- [ ] Login with correct credentials
- [ ] Login with incorrect credentials
- [ ] Email verification workflow
- [ ] Password reset workflow
- [ ] Role-based access (each role)
- [ ] Authorization policy (allow/deny)

### Validation Criteria

- All authentication routes protected
- Registration creates verified user (after email verification)
- Login creates session
- Logout destroys session
- Password reset works
- Roles cannot be changed without admin
- Unauthorized users cannot access protected routes
- Code coverage ≥ 80%

---

## Phase 3: Vendor Management

**Duration**: 5-7 days  
**Dependencies**: Phase 2  
**Team Size**: 1-2 developers  
**Estimated Effort**: 60-80 hours  

### Objectives

- Create vendor/seller accounts
- Implement vendor approval workflow
- Setup vendor documentation requirements
- Implement vendor bank verification
- Create vendor dashboard
- Implement vendor tier system

### Tasks

#### 3.1 Vendor Model & Migration

- [ ] Create Vendor model
- [ ] Create vendors migration with fields:
  - id (UUID), user_id (FK), business_name, business_registration, tax_id, status, tier, commission_rate, verified_at, deleted_at, timestamps
- [ ] Create vendor factories
- [ ] Implement vendor relationships (user, products, orders)

#### 3.2 Vendor Bank Account

- [ ] Create VendorBankAccount model
- [ ] Create migrations for bank accounts
- [ ] Implement bank account verification (micro-deposits)
- [ ] Create bank account service
- [ ] Implement account update with re-verification

#### 3.3 Vendor Approval Workflow

- [ ] Create vendor status enum: PENDING, APPROVED, SUSPENDED, CLOSED
- [ ] Implement vendor approval controller
- [ ] Create approval form request
- [ ] Implement approval/rejection notifications
- [ ] Create admin approval dashboard
- [ ] Implement documentation review system

#### 3.4 Vendor Documentation

- [ ] Create VendorDocument model
- [ ] Create document upload functionality
- [ ] Implement document verification
- [ ] Create document storage (local for dev, S3 for prod)
- [ ] Create admin document review interface

#### 3.5 Vendor Tier System

- [ ] Create tier configuration (Bronze, Silver, Gold, Platinum)
- [ ] Implement tier upgrade/downgrade
- [ ] Create tier benefits (listing limits, commission rates, features)
- [ ] Implement tier enforcement in product listing
- [ ] Create tier upgrade controller

#### 3.6 Vendor Dashboard

- [ ] Create vendor routes (protected by vendor middleware)
- [ ] Create vendor layout/template
- [ ] Implement vendor profile page
- [ ] Implement vendor settings
- [ ] Create dashboard with KPIs (orders, revenue, rating)

### Deliverables

- ✅ Vendor model and database schema
- ✅ Vendor registration and approval workflow
- ✅ Bank account verification system
- ✅ Vendor tier system
- ✅ Admin vendor management interface
- ✅ Vendor dashboard and profile

### Database Changes

```php
Schema::create('vendors', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id')->unique();
    $table->string('business_name');
    $table->string('business_registration');
    $table->string('tax_id');
    $table->enum('status', ['pending', 'approved', 'suspended', 'closed'])->default('pending');
    $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze');
    $table->decimal('commission_rate', 5, 2)->default(10.00);
    $table->timestamp('verified_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->index('status');
    $table->index('tier');
});

Schema::create('vendor_bank_accounts', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('vendor_id');
    $table->string('account_number');
    $table->string('bank_name');
    $table->string('account_holder');
    $table->timestamp('verified_at')->nullable();
    $table->timestamps();
    
    $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
    $table->unique(['vendor_id', 'account_number']);
});

Schema::create('vendor_documents', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('vendor_id');
    $table->string('document_type'); // business_registration, tax_id, bank_proof, etc.
    $table->string('file_path');
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->text('rejection_reason')->nullable();
    $table->timestamps();
    
    $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
});
```

### Tests Required

- [ ] Vendor registration with all required documents
- [ ] Vendor registration with missing documents
- [ ] Admin vendor approval workflow
- [ ] Bank account verification (micro-deposits)
- [ ] Vendor tier upgrade/downgrade
- [ ] Vendor cannot list products until approved
- [ ] Vendor dashboard shows correct KPIs

### Validation Criteria

- Vendor must be approved before listing products
- All required documents validated
- Bank account verified before payout
- Vendor tier enforced (listing limits)
- Admin can approve/reject/suspend vendors
- Vendors receive notifications on status changes

---

## Phase 4: Categories

**Duration**: 2-3 days  
**Dependencies**: Phase 1  
**Team Size**: 1 developer  
**Estimated Effort**: 20-30 hours  

### Objectives

- Create product categories
- Implement category hierarchy
- Setup category-specific commissions
- Create category management interface

### Tasks

#### 4.1 Category Model & Migration

- [ ] Create Category model
- [ ] Create categories migration with fields:
  - id (UUID), parent_id, name, slug, description, icon, commission_override, timestamps
- [ ] Create hierarchical relationships (parent/children)
- [ ] Implement slug generation

#### 4.2 Category Management

- [ ] Create category controller (admin only)
- [ ] Implement category CRUD operations
- [ ] Create category form request
- [ ] Implement category sorting/ordering
- [ ] Create category listing page

#### 4.3 Category Commission Override

- [ ] Allow commission rate override per category
- [ ] Default to marketplace rate if not set
- [ ] Display override in admin interface

### Deliverables

- ✅ Category model and schema
- ✅ Category hierarchy (parent/child relationships)
- ✅ Admin category management interface

### Database Changes

```php
Schema::create('categories', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('parent_id')->nullable();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('icon')->nullable();
    $table->decimal('commission_override', 5, 2)->nullable();
    $table->integer('sort_order')->default(0);
    $table->timestamps();
    
    $table->foreign('parent_id')->references('id')->on('categories')->onDelete('set null');
    $table->index('parent_id');
});
```

---

## Phase 5: Products

**Duration**: 7-10 days  
**Dependencies**: Phase 1, 3, 4  
**Team Size**: 2 developers  
**Estimated Effort**: 80-100 hours  

### Objectives

- Create product listing system
- Implement product approval workflow
- Create product search foundation
- Implement inventory management
- Setup SKU management
- Create vendor product management

### Tasks

#### 5.1 Product Model & Migration

- [ ] Create Product model
- [ ] Create products migration with fields:
  - id, vendor_id (FK), category_id (FK), title, description, sku, price_zwl, price_usd, quantity, status, rating, review_count, timestamps, deleted_at
- [ ] Create product factories
- [ ] Implement relationships (vendor, category, images, reviews)

#### 5.2 Product Validation

- [ ] Implement product title validation (no spam)
- [ ] Implement price range validation
- [ ] Implement category validation
- [ ] Create ProductRequest form request
- [ ] Implement duplicate detection (same SKU)

#### 5.3 Product Listing

- [ ] Create product controller
- [ ] Implement product listing (customer view)
- [ ] Create product detail page
- [ ] Implement product filtering (category, price range)
- [ ] Implement product sorting (price, rating, date)
- [ ] Create vendor product management (vendor dashboard)

#### 5.4 Product Status Workflow

- [ ] Implement status enum: PENDING, ACTIVE, INACTIVE, REJECTED
- [ ] Create approval workflow for manual review categories
- [ ] Auto-approve standard products
- [ ] Implement rejection notifications
- [ ] Allow vendor to resubmit after rejection

#### 5.5 Inventory Management

- [ ] Implement quantity tracking
- [ ] Create low inventory alerts
- [ ] Implement stock depletion (auto-deactivate at 0)
- [ ] Create inventory update service
- [ ] Implement SKU uniqueness per vendor

#### 5.6 Product Search Foundation

- [ ] Setup PostgreSQL full-text search index
- [ ] Create search service (basic keyword search)
- [ ] Implement search result pagination
- [ ] Index new/updated products automatically
- [ ] Setup search caching

### Deliverables

- ✅ Product model and schema
- ✅ Product listing and detail pages
- ✅ Product approval workflow
- ✅ Inventory management system
- ✅ Basic search functionality
- ✅ Vendor product management

### Database Changes

```php
Schema::create('products', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('vendor_id');
    $table->uuid('category_id');
    $table->string('title', 200);
    $table->text('description');
    $table->string('sku', 50)->nullable();
    $table->decimal('price_zwl', 12, 2);
    $table->integer('quantity')->default(0);
    $table->enum('status', ['pending', 'active', 'inactive', 'rejected'])->default('pending');
    $table->decimal('rating', 3, 2)->default(0);
    $table->integer('review_count')->default(0);
    $table->timestamps();
    $table->softDeletes();
    
    $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
    $table->foreign('category_id')->references('id')->on('categories');
    $table->index('vendor_id');
    $table->index(['category_id', 'status']);
    $table->unique(['vendor_id', 'sku']);
});
```

---



## Phase 6: Vehicle Listings

**Duration**: 7-10 days  
**Dependencies**: Phase 1, 3, 4  
**Team Size**: 2 developers  
**Estimated Effort**: 80-100 hours  

### Objectives

- Create vehicle listing system
- Implement vehicle-specific fields
- Create vehicle approval workflow (strict manual review)
- Implement vehicle search and filtering
- Create vehicle condition validation
- Implement vehicle inspection system (Phase 2+)

### Tasks

#### 6.1 Vehicle Model & Migration

- [ ] Create Vehicle model
- [ ] Create vehicles migration with fields:
  - id, vendor_id, year, make, model, body_type, transmission, fuel_type, engine_cc, mileage, vin, condition, status, price_zwl, price_usd, rating, review_count, timestamps, deleted_at
- [ ] Create vehicle factories
- [ ] Implement relationships (vendor, images, reviews)

#### 6.2 Vehicle Information Requirements

- [ ] Create vehicle information service (validates year, make, model)
- [ ] Pre-populate make/model dropdown (100+ makes)
- [ ] Implement transmission enum
- [ ] Implement fuel type enum
- [ ] Implement body type enum
- [ ] Implement condition enum

#### 6.3 Vehicle Validation & Approval

- [ ] Implement VIN validation (17-character format)
- [ ] Implement mileage validation (consistent with condition)
- [ ] Create strict approval workflow (manual review all)
- [ ] Create approval controller (admin only)
- [ ] Implement vehicle inspection checklist
- [ ] Send approval notifications to vendor

#### 6.4 Vehicle Listing Management

- [ ] Create vehicle controller (list, show, create, update, delete)
- [ ] Create vendor vehicle management (dashboard)
- [ ] Implement vehicle detail page (more fields than products)
- [ ] Implement financing options display
- [ ] Create vehicle comparison tool (future)

#### 6.5 Vehicle Search & Filtering

- [ ] Implement search by make/model
- [ ] Implement filter by year range
- [ ] Implement filter by mileage range
- [ ] Implement filter by price range
- [ ] Implement filter by body type, transmission, fuel type
- [ ] Create saved search functionality (customer)

#### 6.6 Vehicle Condition Validation

- [ ] New: mileage = 0
- [ ] Used: mileage > 0
- [ ] Salvage: special documentation required
- [ ] Rebuilt: inspection report recommended
- [ ] Cross-validate year + mileage

### Deliverables

- ✅ Vehicle model and schema
- ✅ Vehicle listing and detail pages
- ✅ Vehicle approval workflow (manual)
- ✅ Vehicle search and filtering
- ✅ Vehicle condition validation
- ✅ Vendor vehicle management

### Database Changes

```php
Schema::create('vehicles', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('vendor_id');
    $table->integer('year');
    $table->string('make', 100);
    $table->string('model', 100);
    $table->string('body_type', 50);
    $table->enum('transmission', ['manual', 'automatic', 'cvt']);
    $table->enum('fuel_type', ['petrol', 'diesel', 'electric', 'hybrid']);
    $table->integer('engine_cc')->nullable();
    $table->integer('mileage')->nullable();
    $table->string('vin', 17)->nullable();
    $table->string('color', 50);
    $table->enum('condition', ['new', 'used', 'salvage', 'rebuilt'])->default('used');
    $table->enum('status', ['pending', 'active', 'inactive', 'rejected'])->default('pending');
    $table->decimal('price_zwl', 12, 2);
    $table->decimal('rating', 3, 2)->default(0);
    $table->integer('review_count')->default(0);
    $table->timestamps();
    $table->softDeletes();
    
    $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
    $table->index('vendor_id');
    $table->index(['year', 'make', 'model']);
    $table->index(['status', 'created_at']);
    $table->unique('vin');
});
```

---

## Phase 7: Media Management

**Duration**: 5-7 days  
**Dependencies**: Phase 1, 5, 6  
**Team Size**: 1 developer  
**Estimated Effort**: 50-70 hours  

### Objectives

- Create product image system
- Implement image optimization
- Setup vehicle image gallery
- Create image upload service
- Implement CDN integration (Phase 2)

### Tasks

#### 7.1 Product Images

- [ ] Create ProductImage model
- [ ] Create product_images migration
- [ ] Implement image upload (local storage dev, S3 prod)
- [ ] Create image validation (size, format, type)
- [ ] Implement image optimization (resize, compress)
- [ ] Create image ordering/display logic


#### 7.2 Vehicle Images

- [ ] Create VehicleImage model
- [ ] Create vehicle_images migration
- [ ] Implement vehicle gallery display
- [ ] Minimum 3 images requirement for vehicles
- [ ] Maximum 5 images per vehicle
- [ ] Maximum 20 images per vehicle for premium sellers
- [ ] Front/side/back views recommended

#### 7.3 Image Processing

- [ ] Create ImageProcessingJob
- [ ] Implement image resizing (thumbnail, detail, full)
- [ ] Implement image compression
- [ ] Create WebP conversion (for modern browsers)
- [ ] Implement lazy loading
- [ ] Create responsive image srcset generation

#### 7.4 Storage Service

- [ ] Create StorageService abstraction
- [ ] Implement local storage driver (dev)
- [ ] Implement S3 driver (prod via DigitalOcean Spaces)
- [ ] Create storage facade
- [ ] Implement file cleanup (orphaned images)

#### 7.5 Image Metadata

- [ ] Extract and store image dimensions
- [ ] Store file size
- [ ] Store upload timestamp
- [ ] Store image order/priority

### Deliverables

- ✅ Product and vehicle image systems
- ✅ Image upload and processing
- ✅ Image optimization pipeline
- ✅ Storage abstraction (local/S3)
- ✅ CDN-ready structure

### Database Changes

```php
Schema::create('product_images', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('product_id');
    $table->string('image_path');
    $table->integer('width')->nullable();
    $table->integer('height')->nullable();
    $table->integer('file_size')->nullable();
    $table->integer('display_order')->default(0);
    $table->timestamps();
    
    $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
    $table->index(['product_id', 'display_order']);
});

Schema::create('vehicle_images', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('vehicle_id');
    $table->string('image_path');
    $table->string('view_type')->nullable(); // front, side, back, interior, etc.
    $table->integer('width')->nullable();
    $table->integer('height')->nullable();
    $table->integer('file_size')->nullable();
    $table->integer('display_order')->default(0);
    $table->timestamps();
    
    $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
    $table->index(['vehicle_id', 'display_order']);
});
```

---


## Phases 8-16: Features (Can Run in Parallel)

After completing Phase 7, these features can be developed in parallel:

### Phase 8: Search & Filtering (3-5 days)
- Advanced search with filters
- Search result ranking
- Autocomplete suggestions
- Saved searches

### Phase 9: Cart (3-4 days)
- Add to cart
- Cart management
- Apply coupons (Phase 15+)

### Phase 10: Checkout (4-5 days)
- support guest checkout
- Shipping address selection
- Shipping method selection
- Order summary
- Cash on delivery 
- Payment initiation

### Phase 11: Pesepay Integration (5-7 days)
- Payment processing
- Webhook handling
- Payment status tracking
- Refund processing

### Phase 12: Orders (5-7 days)
- Order creation and tracking
- Order status management
- Order history
- Invoice generation

### Phase 13: Notifications (4-5 days)
- Email notifications
- SMS notifications (future)
- In-app notifications
- Notification preferences

### Phase 14: Reviews & Ratings (4-5 days)
- Product/vehicle reviews
- Seller ratings
- Review moderation
- Review responses

### Phase 15: Promotions & Coupons (3-4 days)
- Admin promotions management
- Coupon code generation
- Coupon validation
- Discount application

### Phase 16: CMS Pages (2-3 days)
- Static page creation
- CMS editor
- Page publishing
- SEO optimization

---

## Phase 17: Audit Logging & Monitoring

**Duration**: 4-5 days  
**Dependencies**: All previous phases  
**Team Size**: 1 developer  
**Estimated Effort**: 40-50 hours  

### Objectives

- Implement comprehensive audit logging
- Setup application monitoring
- Create audit dashboard
- Implement log rotation
- Setup alerting

### Tasks

#### 17.1 Audit Logging

- [ ] Create AuditLog model
- [ ] Implement audit middleware
- [ ] Log all CRUD operations
- [ ] Log authentication events
- [ ] Log payment events
- [ ] Create audit dashboard (admin)

#### 17.2 Monitoring

- [ ] Setup error tracking (Sentry)
- [ ] Configure error alerts
- [ ] Monitor application performance
- [ ] Track slow queries
- [ ] Monitor queue job failures

#### 17.3 Log Management

- [ ] Configure log rotation
- [ ] Setup log aggregation (future)
- [ ] Create log search interface
- [ ] Implement log cleanup (retention policy)

### Deliverables

- ✅ Complete audit logging system
- ✅ Application monitoring
- ✅ Error tracking and alerting

---

## Phase 18: Production Hardening & Security

**Duration**: 5-7 days  
**Dependencies**: All previous phases  
**Team Size**: 1-2 developers  
**Estimated Effort**: 60-80 hours  

### Objectives

- Complete security audit
- Implement security headers
- Setup rate limiting
- Implement fraud detection
- Security testing

### Tasks

#### 18.1 Security Hardening

- [ ] Implement all HTTP security headers
- [ ] Setup rate limiting
- [ ] Implement CORS properly
- [ ] Setup CSP headers
- [ ] Implement field encryption
- [ ] Setup secure password storage

#### 18.2 Fraud Detection

- [ ] Implement velocity checking
- [ ] Geographic mismatch detection
- [ ] Card testing prevention
- [ ] Chargeback tracking

#### 18.3 Security Testing

- [ ] Penetration testing
- [ ] OWASP Top 10 validation
- [ ] SQL injection testing
- [ ] XSS testing
- [ ] CSRF testing

### Deliverables

- ✅ Security hardening complete
- ✅ Fraud detection implemented
- ✅ Security audit passed

---

## Phase 19: Reporting & Analytics

**Duration**: 5-7 days  
**Dependencies**: Phase 12 (Orders), Phase 14 (Reviews)  
**Team Size**: 1 developer  
**Estimated Effort**: 60-80 hours  

### Objectives

- Create admin reports
- Implement vendor analytics
- Setup data visualization
- Create export functionality

### Tasks

#### 19.1 Admin Reports

- [ ] Create dashboard with KPIs
- [ ] Implement transaction reports
- [ ] Vendor performance reports
- [ ] Customer analytics
- [ ] Fraud detection reports

#### 19.2 Vendor Analytics

- [ ] Create vendor dashboard
- [ ] Revenue tracking
- [ ] Sales trends
- [ ] Customer insights
- [ ] Competitor benchmarking

#### 19.3 Data Visualization

- [ ] Implement charts (revenue, orders, ratings)
- [ ] Trend analysis
- [ ] Forecasting (future)

#### 19.4 Data Export

- [ ] Export to CSV
- [ ] Export to Excel
- [ ] Export to PDF
- [ ] Scheduled reports

### Deliverables

- ✅ Comprehensive reporting system
- ✅ Admin and vendor dashboards
- ✅ Data export functionality

---

## Phase 20: Deployment & DevOps

**Duration**: 5-7 days  
**Dependencies**: All previous phases  
**Team Size**: 1 DevOps engineer  
**Estimated Effort**: 60-80 hours  

### Objectives

- Setup production infrastructure
- Configure DigitalOcean Droplet
- Setup CI/CD pipeline
- Implement monitoring and alerting
- Establish backup and recovery procedures

### Tasks

#### 20.1 Infrastructure Setup

- [ ] Create DigitalOcean Droplet
- [ ] Configure firewall
- [ ] Setup SSL/TLS certificates
- [ ] Configure Nginx
- [ ] Setup PostgreSQL managed database (optional)
- [ ] Configure Redis instance

#### 20.2 Deployment Process

- [ ] Create deployment script
- [ ] Setup git hooks
- [ ] Configure auto-deployment on main branch
- [ ] Setup rollback procedure
- [ ] Test deployment process

#### 20.3 CI/CD Pipeline

- [ ] Setup GitHub Actions
- [ ] Configure automated tests
- [ ] Setup code quality checks
- [ ] Configure automated deployment
- [ ] Setup monitoring and alerting

#### 20.4 Monitoring & Alerting

- [ ] Setup uptime monitoring
- [ ] Configure error alerting
- [ ] Setup performance monitoring
- [ ] Create incident response playbooks

#### 20.5 Backup & Recovery

- [ ] Automated database backups
- [ ] Backup verification
- [ ] Recovery testing
- [ ] Backup storage (S3)

### Deliverables

- ✅ Production infrastructure
- ✅ CI/CD pipeline
- ✅ Monitoring and alerting
- ✅ Backup and recovery procedures

---

## Parallel Development Opportunities

After Phase 7, the following can be developed in parallel by different teams:

**Team A** (Backend): Phases 11, 12, 17, 18
**Team B** (Features): Phases 8, 9, 10, 13, 14, 15, 16
**Team C** (Frontend/UI): Phases 8, 9, 13, 14, 16, 19
**Team D** (DevOps): Phase 20 (can start earlier)

---

## Milestone Timeline

| Milestone | Target Date | Phases | Status |
|-----------|-------------|--------|--------|
| **MVP Foundation** | Week 2 | 1-4 | In Progress |
| **Auth & Vendors** | Week 4 | 2-3 | Pending |
| **Products Ready** | Week 6 | 5-7 | Pending |
| **Payments** | Week 8 | 11 | Pending |
| **Orders** | Week 9 | 12 | Pending |
| **MVP Launch** | Week 10 | 1-12 | Pending |
| **Features Complete** | Week 14 | 13-16 | Pending |
| **Production Ready** | Week 18 | 17-20 | Pending |

---

## Risk Mitigation

| Risk | Mitigation |
|------|-----------|
| **Pesepay Integration Delays** | Start Phase 11 early, use sandbox environment |
| **Database Performance Issues** | Regular performance testing, query optimization |
| **Security Issues** | Continuous security testing, code reviews |
| **Scope Creep** | Strict phase adherence, documented requirements only |

---

## Phase Completion Checklist

Each phase is only "complete" when:

- ✅ All tasks completed
- ✅ Code review approved
- ✅ All tests passing (coverage ≥ 80%)
- ✅ Documentation updated
- ✅ No console errors/warnings
- ✅ Performance acceptable
- ✅ Security review passed
- ✅ Database migrations tested
- ✅ Ready for next phase
- ✅ Stakeholder sign-off (where applicable)

---

## Notes

- **Phases 1-4** must be completed sequentially
- **Phases 5-7** are prerequisites for later features
- **Phases 8-16** can be parallel after Phase 7
- **Phases 17-20** are final preparation steps
- Estimated total duration: 18-20 weeks for 1 team, 10-12 weeks with 2-3 parallel teams

---

*Document Version: 1.0*  
*Last Updated: 2026*  
*Status: Approved*
