# Phase 2 — AI Agent Implementation Prompt
## Authentication, RBAC & User Roles
### Laravel Automotive Marketplace

> **How to use this file:** Paste the full contents (or attach this file) at the start of your AI agent
> session. The agent must read `docs/UI_STANDARDS.md` before generating any view code.

---

## FIRST STEP — READ THESE FILES BEFORE WRITING ANY CODE

Before generating any code, read the following files in full:

1. **`docs/UI_STANDARDS.md`** — All Blade views, components, and layouts MUST follow this document
   exactly. Apply its design system (colours, spacing, typography, component patterns) to every view
   you generate. If a standard is not defined in the file for a specific element, ask before assuming.

2. **`docs/Phase1_Implementation_Guide.md`** (or equivalent) — Understand what was built in Phase 1
   so you extend correctly without duplication or conflict. A true reflection of what was done can be seen in the project.

---

## CONTEXT

You are implementing Phase 2 of a Laravel automotive parts marketplace application.
Phase 1 (User model with UUID, Breeze scaffold, basic auth routes) is complete and working.
**Do NOT modify or remove any Phase 1 working code — only extend it.**

---

## STACK

| Item | Value |
|---|---|
| Framework | Laravel 13 |
| PHP | 8.4+ |
| Auth scaffold | Laravel Breeze (install in this phase — routes/controllers only, NO default views) |
| Permissions | spatie/laravel-permission (install in this phase) |
| Database | Postgress|
| Queue driver | `database` |
| UI | Custom Blade, guided by `docs/UI_STANDARDS.md` — no Tailwind UI defaults, no Jetstream |

---

## ROLE ARCHITECTURE

Implement the following 7 roles using Spatie:

| Role | Description |
|---|---|
| `super_admin` | Full system access. One per deployment. |
| `admin` | Platform management — moderate listings, manage vendors, resolve disputes. |
| `vendor_admin` | Owns a vendor account. Can invite and manage vendor_workers. |
| `vendor_worker` | Belongs to a vendor. Manage listings/orders scoped to their vendor only. |
| `agent` | Independent seller/broker with a dedicated profile (licence, territory, commission). No sub-users. |
| `private_seller` | Individual. Can list 1–3 items. Identity-verified only. |
| `customer` | Browse and buy only. |

---

## SECTION 1 — DATABASE MIGRATIONS

Use **fresh migration files** that ALTER existing tables — do not drop and recreate.
All IDs are UUID. All models use SoftDeletes.

### 1a. Alter users table — extend role enum

Create a new migration that alters the existing `users` table `role` enum column to add:
`vendor_admin`, `vendor_worker`, `agent`
(alongside existing: `super_admin`, `admin`, `vendor`, `private_seller`, `customer`)

Also add a `force_password_change` boolean column (default `false`) in the same migration.

### 1b. Create vendors table

```
vendors:
  id              uuid, primary
  name            string
  slug            string, unique
  logo            string, nullable
  description     text, nullable
  contact_email   string
  phone           string(20), nullable
  address         text, nullable
  verified_at     timestamp, nullable
  suspended_at    timestamp, nullable
  timestamps
  softDeletes
```

### 1c. Create vendor_users pivot

```
vendor_users:
  vendor_id     uuid, fk → vendors.id, cascade delete
  user_id       uuid, fk → users.id, cascade delete
  vendor_role   enum(admin, worker)
  invited_at    timestamp, nullable
  joined_at     timestamp, nullable
  primary(vendor_id, user_id)
```

### 1d. Create agent_profiles table

```
agent_profiles:
  id               uuid, primary
  user_id          uuid, fk → users.id, unique, cascade delete
  licence_number   string, nullable
  licence_expiry   date, nullable
  territory        string, nullable
  commission_rate  decimal(5,2), nullable
  bio              text, nullable
  timestamps
```

### 1e. Create vendor_invitations table

```
vendor_invitations:
  id             uuid, primary
  vendor_id      uuid, fk → vendors.id, cascade delete
  invited_by     uuid, fk → users.id
  email          string
  temp_password  string    ← admin sets this; worker must change on first login
  token          string, unique
  expires_at     timestamp
  accepted_at    timestamp, nullable
  timestamps
```

### 1f. Create queue tables

Run `php artisan queue:table` and include the generated migration.
Also create `failed_jobs` table if not already present.

---

## SECTION 2 — SPATIE ROLES & PERMISSIONS

Install and configure `spatie/laravel-permission` and `laravel/breeze`:

```bash
commands to be run using docker since the app is running on docker 
docker-compose run --rm app composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate

```

Add `HasRoles` trait to the `User` model.

Create `database/seeders/RolesAndPermissionsSeeder.php` with the following permission matrix:

```
super_admin:     all permissions (wildcard)

admin:           manage-vendors, suspend-users, manage-listings,
                 manage-orders, view-admin-dashboard, approve-vendors

vendor_admin:    manage-team, manage-own-listings, view-vendor-dashboard,
                 manage-vendor-orders, invite-vendor-worker, manage-vendor-profile

vendor_worker:   manage-own-listings, view-vendor-dashboard,
                 manage-vendor-orders

agent:           manage-own-listings, view-agent-dashboard,
                 manage-agent-profile

private_seller:  create-listing, view-listings

customer:        view-listings, place-order
```

Register the seeder in `DatabaseSeeder.php`.

Create a separate `SuperAdminSeeder.php` that creates one `super_admin` user for local dev
using credentials from `.env`:

```env
SUPER_ADMIN_EMAIL=admin@dev.local
SUPER_ADMIN_PASSWORD=SuperAdmin@2025
```

---

## SECTION 3 — MODELS & RELATIONSHIPS

### User model additions

- Relationship: `vendor()` — belongsToMany Vendor via `vendor_users` pivot, with pivot fields
  (`vendor_role`, `invited_at`, `joined_at`)
- Relationship: `agentProfile()` — hasOne AgentProfile
- Helper methods: `isVendorAdmin()`, `isVendorWorker()`, `isAgent()`, `belongsToVendor($vendorId)`

### Vendor model

- Relationships: `users()` (belongsToMany via vendor_users), `admins()` (scoped to
  `vendor_role=admin`), `workers()` (scoped to `vendor_role=worker`)
- Auto-generate `slug` from `name` on creation

### AgentProfile model

- Belongs to User
- Cast `licence_expiry` as date, `commission_rate` as float

### VendorInvitation model

- Belongs to Vendor, belongs to User (invited_by)
- Scope: `pending()` — where `accepted_at` is null and `expires_at` > now

---

## SECTION 4 — EMAIL CONFIGURATION

### .env settings

Add the following to `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=bd53947e31917f
MAIL_PASSWORD=f5423e721f7782
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@automarket.dev
MAIL_FROM_NAME="AutoMarket"

QUEUE_CONNECTION=database
```

> **Note:** Mailtrap sandbox is for development only. SPF/DKIM/DMARC DNS records will be
> configured when a production mail domain is assigned — skip DNS setup for now.

### Email template branding

Logo and final brand colours will be provided before production. For now, generate professional
world-standard email templates using this interim palette:

| Element | Value |
|---|---|
| Header background | `#1E2D40` (deep navy) |
| Header text | `#FFFFFF` |
| Body background | `#FFFFFF` |
| Footer background | `#F4F6F8` |
| CTA button | `#1E2D40` background, `#FFFFFF` text |
| App name (header) | "AutoMarket" — styled typographically as a wordmark placeholder |
| Footer copy | `© 2025 AutoMarket. All rights reserved.` |
| Support line | `Need help? Contact support@automarket.dev` |

> Company name, registered address, and logo will replace these placeholders before go-live.

All email templates use **Laravel Markdown Mailables** (Blade component-based).
All emails are dispatched via **queued jobs** (implement `ShouldQueue`).

### Mailables to build

| Mailable | Trigger | Key content |
|---|---|---|
| `VerifyEmailMailable` | User registers | Signed verification URL, 60-min expiry |
| `PasswordResetMailable` | Forgot password | Reset link, 60-min expiry |
| `VendorInvitationMailable` | vendor_admin invites a worker | Inviter name, vendor name, temp password (plaintext), signed accept URL (48-hr expiry) |
| `VendorApprovedMailable` | Admin approves vendor account | Approval confirmation, link to vendor dashboard |
| `AccountSuspendedMailable` | Admin suspends a user | Reason placeholder, support contact link |

Create a corresponding Job class in `app/Jobs/Mail/` for each Mailable.

---

## SECTION 5 — VENDOR INVITATION FLOW

When a `vendor_admin` invites a worker:

1. `vendor_admin` fills a form: worker email + sets a temporary password
2. System creates a `VendorInvitation` record (`token = Str::uuid()`, expires 48 hours)
3. Dispatches `VendorInvitationMailable` to the worker's email via queue
4. Worker clicks the accept link → lands on an accept-invitation page
5. If token valid and not expired: create user account with role `vendor_worker`, attach to vendor
   in pivot, set `force_password_change = true`
6. Worker is redirected to a forced password-change screen before accessing any dashboard
7. Once password changed: `force_password_change` set to `false`, worker lands on vendor dashboard

Create middleware `ForcePasswordChange` that redirects any user with `force_password_change = true`
to the change-password route on every request (except that route itself and logout).

---

## SECTION 6 — CONTROLLERS & MIDDLEWARE

### Controllers

| Controller | Responsibility |
|---|---|
| `RegisteredUserController` | On successful registration, dispatch `VerifyEmailMailable` via queue. Redirect to "check your email" page. |
| `AuthenticatedSessionController` | After login, redirect based on role (see route table below). |
| `PasswordResetLinkController` | Dispatch `PasswordResetMailable` via queue. |
| `VendorInvitationController` | Handle invite creation (vendor_admin) and acceptance (worker). |

### Post-login redirect map

| Role(s) | Redirect |
|---|---|
| `super_admin`, `admin` | `/admin/dashboard` |
| `vendor_admin`, `vendor_worker` | `/vendor/dashboard` |
| `agent` | `/agent/dashboard` |
| `private_seller` | `/seller/dashboard` |
| `customer` | `/` |

### Middleware

| Middleware | Purpose |
|---|---|
| `EnsureEmailIsVerified` | Already in Breeze — confirm it applies to all non-guest routes |
| `ForcePasswordChange` | See Section 5 |
| `RoleMiddleware` | Wrap Spatie's `role:` middleware with a clean 403 view matching UI_STANDARDS.md |
| `VendorScope` | On requests from `vendor_admin` or `vendor_worker`, bind the user's `vendor_id` to the request so controllers can scope queries without repeating the check |

Register all middleware in `bootstrap/app.php` (Laravel 11 style — no `Kernel.php`).

---

## SECTION 7 — ROUTES

Organise routes in `routes/web.php`:

```php
// Guest routes
Route::middleware('guest')->group(fn() => require base_path('routes/auth.php'));

// Authenticated — all roles
Route::middleware(['auth', 'verified', 'force.password.change'])->group(function () {

    Route::get('/', [HomeController::class, 'index']);

    // Admin
    Route::middleware('role:super_admin|admin')
        ->prefix('admin')->name('admin.')
        ->group(base_path('routes/admin.php'));

    // Vendor
    Route::middleware('role:vendor_admin|vendor_worker')
        ->prefix('vendor')->name('vendor.')
        ->group(base_path('routes/vendor.php'));

    // Agent
    Route::middleware('role:agent')
        ->prefix('agent')->name('agent.')
        ->group(base_path('routes/agent.php'));

    // Private seller
    Route::middleware('role:private_seller')
        ->prefix('seller')->name('seller.')
        ->group(base_path('routes/seller.php'));
});
```

Create stub route files — each with a dashboard route as a starting point:
- `routes/admin.php`
- `routes/vendor.php`
- `routes/agent.php`
- `routes/seller.php`

---

## SECTION 8 — VIEWS (CUSTOM UI)

> All views MUST follow `docs/UI_STANDARDS.md`. Read it before generating any Blade file.
> Delete all default Breeze views after scaffold — they are not used.

### Auth views

| File | Purpose |
|---|---|
| `auth/login.blade.php` | Login form |
| `auth/register.blade.php` | Registration form |
| `auth/forgot-password.blade.php` | Request password reset |
| `auth/reset-password.blade.php` | Enter new password |
| `auth/verify-email.blade.php` | "Check your email" holding page |
| `auth/confirm-password.blade.php` | Sudo / confirm password prompt |
| `auth/force-password-change.blade.php` | Forced change screen for invited vendor workers |

### Vendor invitation views

| File | Purpose |
|---|---|
| `vendor/invitation/accept.blade.php` | Accept invite landing page (token in URL) |
| `vendor/invitation/create.blade.php` | Form for vendor_admin to invite a worker |

### Error views

| File | Purpose |
|---|---|
| `errors/403.blade.php` | Role-unauthorised page, styled per UI_STANDARDS.md |

### Dashboard stubs

Create a minimal dashboard stub view for each role group:
- `admin/dashboard.blade.php`
- `vendor/dashboard.blade.php`
- `agent/dashboard.blade.php`
- `seller/dashboard.blade.php`
- `customer/dashboard.blade.php`

---

## SECTION 9 — TESTS

Create feature tests in `tests/Feature/Auth/`:

| Test class | Scenarios |
|---|---|
| `RegistrationTest` | Valid data, invalid email, weak password, duplicate email |
| `LoginTest` | Correct credentials, wrong password, unverified account blocked |
| `EmailVerificationTest` | Valid token, expired token, already verified |
| `PasswordResetTest` | Request email, valid token reset, expired token |
| `RbacTest` | Each role can access its own routes; cannot access others' routes |
| `VendorInvitationTest` | Invite sent, accept with valid token, expired token rejected, force password change triggered |
| `VendorScopeTest` | vendor_worker cannot access another vendor's data |

**Target: ≥ 80% code coverage on all Phase 2 code.**

---

## DELIVERABLES CHECKLIST

Confirm each item before closing Phase 2:

- [ ] Role enum extended with `vendor_admin`, `vendor_worker`, `agent`
- [ ] `force_password_change` column added to users
- [ ] `vendors`, `vendor_users`, `agent_profiles`, `vendor_invitations` tables created
- [ ] `jobs` and `failed_jobs` tables created for queue
- [ ] Spatie installed, roles seeded, permissions assigned per matrix
- [ ] `SuperAdminSeeder` created
- [ ] User model updated with relationships and helper methods
- [ ] `Vendor`, `AgentProfile`, `VendorInvitation` models created
- [ ] 5 Mailable classes + queue Job classes built with branded templates
- [ ] Mailtrap sandbox configured in `.env`
- [ ] Vendor invitation flow complete (invite → accept → force password change)
- [ ] `ForcePasswordChange` middleware active
- [ ] `VendorScope` middleware active
- [ ] Role-based post-login redirect working
- [ ] All custom Blade views built per `docs/UI_STANDARDS.md`
- [ ] Route files organised by role group
- [ ] Feature tests written, coverage ≥ 80%

---

## CONSTRAINTS

- Never hardcode credentials in source files — use `.env` for all secrets
- Never break Phase 1 functionality
- All new code follows PSR-12 coding standards
- Use Laravel 13 conventions — `bootstrap/app.php` for middleware, not `Kernel.php`
- SPF/DKIM/DMARC DNS setup is deferred to production — skip entirely for now
- Logo and brand colours in email templates are placeholders — use the navy/white scheme in Section 4
  until final assets are provided
- Queue worker must be running for emails to send in development:
  ```bash
  php artisan queue:work --tries=3
  
  ```

  ## Colour palette 
  - #F0A820
  - #2EBD7A
  - #3DB8E8
  - #D4295A
  - #5A6070
  - #C8CDD6
  - #1A1A24
  - #080810

## logo
- logos are in src/public/logo
- same logo but with different backgrounds

## tagline 
- "Find It. Buy It. Drive It."

## Strategic foundation
- Built to own the digital-first automotive retail space in Zimbabwe, leveraging Salma Technology's brand equity and ICT capabilities as a structural competitive moat.

##  vision 
- To be Zimbabwe's most trusted virtual auto marketplace — where every Zimbabwean can buy, sell, or upgrade their vehicle and accessories confidently online.

## mission 
- To eliminate friction in the Zimbabwean automotive market by connecting buyers and sellers through a secure, transparent, technology-powered platform.

## Positioning
- Premium-accessible. Not a cheap classifieds board — a curated, trust-verified marketplace. Premium enough for new cars; accessible enough for second-hand deals and small parts orders.

