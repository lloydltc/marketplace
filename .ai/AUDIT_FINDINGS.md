# Salma Drive — AUDIT_FINDINGS.md (R0 output)

> **Mode:** Audit only. No application behaviour has been changed. This document maps what exists vs. what is wired up, per role and per surface, and categorises every gap with file references and the remediation task (R1–R9) it maps to.
>
> **Status:** ✅ **Remediation complete.** R1–R10 implemented and tested (379 tests / 901 assertions passing). See the Remediation Log at the end. The original R0 audit body below is preserved as-recorded.

---

## Method

Traversed: `routes/{web,admin,vendor,seller,agent,rider,auth}.php`; the role middleware (`RoleMiddleware`, `CheckUserStatus`, `VendorScope`, `ForcePasswordChange`); `bootstrap/app.php`; policies (`ProductPolicy`, `VehiclePolicy`, `VendorPolicy`); the navigation component (`components/layouts/app.blade.php`); every dashboard view; and the CRUD surfaces for products, vehicles, orders, RFQ, concierge, promotions, users, vendors, wallet, delivery.

---

## 1. Role × Surface matrix — CURRENT vs INTENDED

Legend: ✅ wired & reachable · ⚠️ exists but not surfaced/!reachable to this role · 🔓 leaked (visible/accessible when it should not be) · ❌ absent · `—` N/A

| Surface | customer | private_seller | vendor_admin | vendor_worker | agent | admin | super_admin | rider |
|---|---|---|---|---|---|---|---|---|
| Top-nav **Cart** | ✅ | ✅ | 🔓 (shown) | 🔓 | 🔓 | 🔓 | 🔓 | 🔓 |
| Top-nav **Requests / My orders / Saved searches** | ✅ | ✅(mixed) | 🔓 | 🔓 | 🔓 | 🔓 | 🔓 | 🔓 |
| **Dashboard link in nav** | — | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Cart/Checkout route guard | public | public | public | public | public | 🔓 reachable | 🔓 reachable | 🔓 reachable |
| Orders/RFQ/Saved-search route guard | ✅ | ✅ | 🔓 | 🔓 | 🔓 | 🔓 | 🔓 | 🔓 |
| My **Vehicle** Listings | — | ⚠️ routes exist, **no nav/dashboard link** | ✅ | ✅(view) | ❌ | — | — | — |
| Create Vehicle | — | ⚠️ route exists, **not linked**, blocked by `check.status` while pending | ✅ | ❌(admin-only in group) | — | — | — | — |
| My **Product** Listings / Create Product | — | ❌ (schema: products are vendor-only) | ✅ | view | — | — | — | — |
| Seller orders / RFQ quotes / Wallet | — | ❌ (no seller routes) | ✅ | partial | ❌ | — | — | — |
| Vendor **user management** (own team) | — | — | ⚠️ invite-only, no list/edit/deactivate | ❌ | — | — | — | — |
| All-user management | — | — | — | — | — | ⚠️ list+show only | ⚠️ list+show only | — |
| Approvals queue | — | — | — | — | — | ✅ | ✅ | — |
| Platform settings | — | — | — | — | — | 🔓 (admin can reach; should be super_admin) | ✅ | — |
| Dashboard stats | — | 🔴 fake | 🔴 fake | 🔴 fake | ? | 🔴 fake | 🔴 fake | n/a |
| Delivery tasks | — | — | — | — | — | dispatch ✅ | dispatch ✅ | ✅ |

---

## 2. Categorised gaps (with file refs + R-task)

### 🔴 RBAC leak (server-side authorization missing)

- **RBAC-1 (F5, F2) — Buyer surfaces open to every authenticated role.** `routes/web.php` puts `saved-searches.*`, `rfq.index/store/show/accept/close`, `orders.*`, `concierge.index/store/show/pay` in the shared `['auth','verified','check.status','force.password.change']` group with **no role middleware**. An `admin`, `vendor_admin`, `agent`, or `rider` can hit `/orders`, `/requests`, `/saved-searches`, `/concierge` by URL. → **R1**
- **RBAC-2 (F5) — Cart & checkout are fully public.** `cart.*`, `checkout.*` in `routes/web.php` have no auth or role guard. An admin (or anyone) can hit `/cart`, `/checkout`. → **R1**
- **RBAC-3 — Platform Settings reachable by `admin`.** `routes/web.php` admin group is `role:super_admin|admin`; `routes/admin.php` exposes `settings.*` to both. Per R1 matrix, Platform Settings is **super_admin only**. → **R1/R6**
- **RBAC-4 — No `Gate`/Policy backing for buyer-only abilities.** Nothing defines "who may shop/checkout/own orders"; it is implied by route grouping only. → **R1**

### 🔌 Wiring gap ("built but not shown")

- **WIRE-1 (F1) — Private-seller vehicle listing is built but unreachable.** `routes/seller.php` has the full `vehicles.*` CRUD + image chain, controllers exist (`Vehicles/Controllers/PrivateSeller/VehicleController`), but `resources/views/seller/dashboard.blade.php` shows a placeholder ("Listing creation will be available in Phase 3") with **no links** to `seller.vehicles.create` / `seller.vehicles.index`. Classic built-but-not-shown. → **R3**
- **WIRE-2 (F4, F10) — No "Dashboard/Home" control for any authenticated non-customer role.** Nav (`components/layouts/app.blade.php`) only has the logo→`home` (public landing) + buyer links + sign-out. No link to `vendor.dashboard`, `seller.dashboard`, `agent.dashboard`, `admin.dashboard`, `rider.dashboard`. → **R2**
- **WIRE-3 — Nav is hardcoded, not role-driven.** All nav items are rendered under a single `@auth` with no role awareness — the root cause behind RBAC-1 visibility and WIRE-2. → **R1**
- **WIRE-4 (F6) — Vendor-admin team management is invite-only.** `routes/vendor.php` has `invitation.create/store` but no screen to **list / edit / deactivate** the vendor's existing users (the `vendor_users` pivot + `vendor_worker`/`agent` roles already exist). → **R5**
- **WIRE-5 (F7) — Super-admin user management is read-only.** `Admin/UserController` has only `index` + `show` (+ tier via `UserTierController`). No create, suspend/reactivate, role change, password reset, or email-verification bypass. → **R6**
- **WIRE-6 — Agent surface is an empty shell.** `routes/agent.php` = dashboard only; `agent/dashboard` is a placeholder. The matrix treats agent ≈ vendor_worker, but no agent capabilities are wired. → **R1/R3 (confirm intended agent scope)**

### 🟠 Fake data (hardcoded stats)

- **STAT-1 (F8) — Admin dashboard KPIs are literals.** `resources/views/admin/dashboard.blade.php`: `['Total Users','—'],['Active Vendors','—'],['Listings','—'],['Pending Approvals','—']`. → **R7**
- **STAT-2 (F8) — Vendor dashboard.** `vendor/dashboard.blade.php`: "Active Listings" and "Pending Orders" are `—`; only Team Members and Tier are real. → **R7**
- **STAT-3 (F8) — Seller dashboard.** `seller/dashboard.blade.php`: "Active Listings" = `'0 / 3'` literal, "Enquiries" = `—`. → **R7**

### 🎨 UX violation

- **UX-1 (F3) — "Apply as vendor" is one long scroll.** `resources/views/auth/apply-vendor.blade.php` (112 lines, 9 fields: account + business + bank/registration) in a single form — violates `UI_STANDARDS.md` ("multi-step / wizard, no long continuous scroll"). → **R9**
- **UX-2 — "Apply as seller"** (`auth/apply-seller.blade.php`) should be checked against the same rule (shorter, likely acceptable, but verify in R9). → **R9**

### ⛔ Onboarding / business-rule conflict (F12/F13)

- **ONB-1 (F12) — Pending sellers are sent to a dead-end, not their dashboard.** `Auth/ApplicationController@storeVendor/@storeSeller` set `status = 'pending'` and redirect to `verification.notice`. `CheckUserStatus` middleware then redirects any `pending` user from all protected routes to `application.pending`. Net effect: a new seller **cannot reach a working dashboard** — contradicts F12. → **R4**
- **ONB-2 (F13) — Listing-while-pending is blocked at two layers.** (a) `CheckUserStatus` bounces pending users off `/seller/*` and `/vendor/*` entirely; (b) `ProductPolicy@create` and `VehiclePolicy@create` (for `vendor_admin`) require `$user->vendor?->isApproved()`. So even if the dashboard were reachable, listing creation is denied until approved — contradicts F13. No **unverified-seller badge** exists on catalogue/detail views. → **R4**
- **ONB-3 — Schema reality for "private seller products".** `products.vendor_id` is **NOT NULL** (`create_products_table`) and `ProductPolicy@create` is `vendor_admin`-only. Private sellers therefore **cannot own products** — only vehicles. F1's "create products or vehicles" must be read as **vehicles** for private sellers (parts are a vendor product). Flagged as a decision for R3 (allow private-seller products would need a schema change). → **R3 (confirm)**

### ❓ Not implemented / to verify (R8)

- **NI-1 — Vendor/seller dashboard "My listings" tables** don't exist (dashboards are placeholders); listing management lives only under `/{vendor,seller}/vehicles` and `/vendor/products`. → **R3/R8**
- **NI-2 — Audit logging** of privileged actions (password reset, verification bypass, role change, suspend) — none exists yet; required by R6. → **R6**

---

## 3. Phase pass/fail (claimed-complete phases, focus on vehicle listing + admin)

| Phase | Deliverable | Verdict | Note |
|---|---|---|---|
| 2 RBAC | Roles/policies/middleware | ⚠️ Partial | Roles exist; **buyer surfaces not role-gated** (RBAC-1/2). |
| 3 Vendor | "must be approved before listing" | ⚠️ Changing | Overridden by F12/F13; policies still enforce old rule. |
| 5 Products | Vendor product CRUD + approval | ✅ Works for vendor_admin | Not exposed on dashboard (no "My products" table) — NI-1. |
| 6 Vehicles | Create/approve/display/search | ✅ Functional (routes+controllers+public index/show wired) | **Private-seller create unreachable from UI** (WIRE-1); vendor create works. |
| 7R/8–17 | settings, search, cart, checkout, money spine, RFQ, concierge, promotion | ✅ Green (337 tests) | Engine works; gaps are exposure/nav/stats, not logic. |
| Admin screens | approvals, categories, products, vehicles, settings, payouts, dispatch, cash, rfq, concierge, promotions | ✅ Reachable for admin | **No dashboard nav links** to them except the dashboard quick-grid; **user management read-only** (WIRE-5); **stats fake** (STAT-1). |

**Untestable items from findings (F11):** vehicle listing as a **private seller** (blocked by WIRE-1 + ONB-1/2) and full **admin user management** (WIRE-5) — both confirmed as real gaps above, not test-environment problems.

---

## 4. Findings F1–F13 coverage

| # | Finding | Confirmed? | Maps to |
|---|---|---|---|
| F1 | Private seller can't create products/vehicles | ✅ (WIRE-1, ONB-3) | R3 |
| F2 | Private seller treated as customer | ✅ (RBAC-1, WIRE-3) | R1, R3 |
| F3 | Apply-vendor long scroll | ✅ (UX-1) | R9 |
| F4 | No easy nav to dashboard | ✅ (WIRE-2) | R2 |
| F5 | Admin sees customer items | ✅ (RBAC-1/2) | R1 |
| F6 | No vendor-admin user mgmt | ✅ (WIRE-4) | R5 |
| F7 | No super-admin user mgmt | ✅ (WIRE-5) | R6 |
| F8 | Fake stats | ✅ (STAT-1/2/3) | R7 |
| F9 | "Implemented" doesn't work | ✅ partial (WIRE-1, NI-1) | R0/R8 |
| F10 | Nav must reflect profile | ✅ (WIRE-2/3) | R1, R2 |
| F11 | Can't test vehicle listing / admin | ✅ (WIRE-1, WIRE-5) | R0/R8 |
| F12 | Pending profile + dashboard immediately | ✅ (ONB-1) | R4 |
| F13 | List while pending + unverified badge | ✅ (ONB-2) | R4 |

**Newly discovered (beyond F1–F13):** RBAC-2 (public cart/checkout), RBAC-3 (settings reachable by admin), WIRE-6 (agent shell), ONB-3 (products are vendor-only by schema), NI-2 (no audit log).

---

## 5. OPEN DECISION (blocks R4 — please answer)

> **Can an unverified/pending seller's listings be transacted** (added to cart, checked out, paid), or are they **display-only** (visible with an "unverified seller" badge, but buy / RFQ-accept / COD / payout disabled) until the seller is approved?

**My recommendation & default if unanswered:** **display-only behind a config flag** (`platform_settings`). Pending sellers may build inventory (listings appear with an *Unverified seller* badge), but checkout/COD/payout for an unverified seller is blocked until approval — protecting buyers and payout integrity (no payouts to unverified bank accounts). On approval the badge clears and gating lifts automatically.

A second, smaller decision for **R3/ONB-3:** keep **private sellers = vehicles only** (current schema), or invest in making products owner-agnostic so individuals can sell parts too? Default: **vehicles only** for now (no schema churn).

---

*Generated by R0. Next: on your decision, proceed R1 → R10, checkpointing after each task. No code changed yet.*

---

## Remediation Log (R1 → R10) — COMPLETE

**Decisions taken** (the two open questions above): (1) pending-seller listings are **display-only behind a config flag** — `sellers.unverified_can_transact` (default `0`); (2) **private sellers = vehicles only** (no schema churn).

| Task | Outcome | Key files | Tests |
|---|---|---|---|
| **R1** | Server-side RBAC + single nav source of truth. `config/navigation.php`, `App\Support\Navigation`, `ShopAccess` middleware; buyer surfaces gated `role:customer\|private_seller\|vendor_admin`; cart/checkout behind `shop.access`; settings → `super_admin` only. Nav refactored to one injected component. | `config/navigation.php`, `app/Support/Navigation.php`, `Http/Middleware/ShopAccess.php`, `routes/web.php`, `routes/admin.php`, `components/layouts/app.blade.php` | `ShopAccessTest` (6) |
| **R2** | Login redirect resolves dashboard from config for **all** roles (fixed rider gap); dashboard link in nav for non-customers. | `Auth/AuthenticatedSessionController.php`, `Navigation.php` | (covered by R1 + smoke) |
| **R3** | Private-seller dashboard: real stat cards, "List a vehicle" / "View all" links, pending banner. | `Seller/DashboardController.php`, `seller/dashboard.blade.php` | (smoke) |
| **R4** | List-while-unverified: pending sellers reach dashboard + list; listings carry **Unverified seller** badge; transactions blocked at cart/RFQ-accept via `Vendor::canTransact()` / `Vehicle::ownerIsVerified()` + kill-switch setting. Policies dropped `isApproved()` on create. `CheckUserStatus` no longer dead-ends pending. | `Models/Vendor.php`, `Vehicles/Models/Vehicle.php`, `ProductPolicy`, `VehiclePolicy`, `CartController`, `RfqController`, `unverified-badge` + listing views, `PlatformSettingsSeeder` | `ListWhileUnverifiedTest` (8) |
| **R5** | Vendor-admin team management, **scoped server-side** to own vendor (cross-vendor target → 404); promote/demote, remove, last-admin guard; audit-logged. | `Vendor/TeamController.php`, `vendor/team/index.blade.php`, `User::pivotRoleFor()`, `routes/vendor.php` | `TeamManagementTest` (6) |
| **R6** | Super-admin user management (create / suspend / reactivate / role change / password reset / email-verify bypass), **super_admin-only** (plain admins read-only); suspended users force-logged-out; all actions audit-logged via new append-only `audit_logs` + `AuditLog` model. | `Admin/UserManagementController.php`, `Models/AuditLog.php`, migration, `CheckUserStatus`, `admin/users/{show,create}.blade.php`, `routes/admin.php` | `UserManagementTest` (10) |
| **R7** | Real, query-backed dashboard KPIs — admin (users/active vendors/listings/pending approvals) and vendor (active listings/pending orders/team/tier), vendor figures scoped. | `Admin/DashboardController.php`, `Vendor/DashboardController.php`, both dashboard views | `DashboardStatsTest` (2) |
| **R8** | Functional verification of every role's surfaces. **Found & fixed** a real wiring bug: `vendor.products.create` / `vehicles.create` were shadowed by UUID-less `{product}`/`{vehicle}` show routes — constrained with `->whereUuid(...)`. | `routes/vendor.php` | `CrossRoleSurfaceTest` (7) |
| **R9** | Apply-as-vendor converted from a single long scroll to a 2-step Alpine wizard (`novalidate` + server validation, auto-opens errored step) per `UI_STANDARDS.md`. Server flow unchanged. | `auth/apply-vendor.blade.php`, `layouts/auth.blade.php` | `VendorApplicationWizardTest` (3) |
| **R10** | Full regression: **379 passed / 901 assertions**. Docs reconciled (`BUSINESS_MODEL.md §11`, `task_execution_order_v2.md §7R.3`). | — | full suite |

**Non-negotiables honoured:** authorization is server-side everywhere (staff hitting `/cart`, cross-vendor team edits, plain-admin destructive user actions all rejected by route/controller, not just hidden in UI); navigation + abilities come from one config source; no hardcoded stats or fees (queries + `platform_settings`); Spatie permissions + vendor multi-user model reused; privileged actions audit-logged; existing tests stayed green.
