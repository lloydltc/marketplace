# Salma Drive — Remediation & Audit Task Order (v1.0)

## Purpose

This document drives a **corrective pass** over the existing build, triggered by manual-testing findings. Most issues are not "missing code" — they are **wiring gaps**: features that were built in earlier phases but are not surfaced to the right role, not fed real data, or not exposed in navigation. Example pattern: *listings exist in the database and in code, but never appear on the private-seller or vendor dashboards.*

The agent must therefore **audit before fixing**, and treat the explicit findings below as *representative samples of classes of bugs*, not an exhaustive list. Where one instance of a wiring gap is found, the agent searches for every analogous instance.

**Companion documents** (read first, treat as authoritative):
- `UI_STANDARDS.md` — design/UX rules (multi-step forms, no long continuous scroll, etc.)
- `BUSINESS_MODEL.md` — monetization, fulfilment, wallet, roles
- `task_execution_order.md` (v2.0) — phase definitions and what each phase claims to deliver

---

## Roles in scope

`super_admin`, `admin`, `vendor_admin`, `vendor_worker`/`agent`, `private_seller`, `customer`, `rider`.

A **single source of truth** for "what each role can see and do" must be established (see R1). Today this logic is scattered and inconsistent — that is the root cause of most findings.

---

## Finding → Task Map

| # | Finding | Addressed in |
|---|---------|--------------|
| F1 | Private seller has no option to create products/vehicles | R3 |
| F2 | Private seller is not a customer — sees customer-only items | R1, R3 |
| F3 | "Apply as vendor" form is a long continuous scroll — violates `UI_STANDARDS.md` | R9 |
| F4 | No easy navigation to dashboard on all profiles | R2 |
| F5 | Admin profiles see customer items (cart, request, my orders, saved searches) | R1 |
| F6 | No user management on the vendor-admin side (manage that vendor's users) | R5 |
| F7 | No user management on super-admin side (manage all users: resets, email-verify bypass, etc.) | R6 |
| F8 | "Pending Approvals / Listings / Active Vendors / Total Users" show fake stats, not DB values | R7 |
| F9 | Sections marked "implemented" in phases don't actually work | R0, R8 |
| F10 | Top navigation must reflect the logged-in profile | R1, R2 |
| F11 | Can't test vehicle listing and much admin functionality | R0, R8 |
| F12 | Vendors/private sellers must see their profile immediately after applying, in **pending** status | R4 |
| F13 | Sellers must be able to list while pending, showing an **unverified** badge to customers | R4 |

---

## ⚠️ Business-rule change introduced by F12/F13

The original Phase 3 validation said *"vendor must be approved before listing products."* Findings F12–F13 **override** this: sellers now get an active (pending) profile immediately and may create listings while unverified, with listings carrying an **unverified seller** badge to buyers.

**Open decision the agent must confirm before R4 (do not guess silently):**
> Can an *unverified/pending* seller's listings be **purchased / transacted** (added to cart, checked out, paid), or are they **display-only** (visible with badge, but "buy"/RFQ-accept disabled) until the seller is approved?

Default assumption if unconfirmed: **display-only** — listings are visible with the unverified badge, but checkout/COD/payout for that seller is blocked until approval. This protects buyers and your commission/payout integrity (no payouts to unverified bank accounts) while still letting sellers build inventory. Implement behind a config flag so it can be flipped.

This change must be reflected back into `task_execution_order.md` Phase 3 notes and `BUSINESS_MODEL.md` once confirmed.

---

## R0 — Project Audit & Gap Analysis (do this first, change no behaviour)

**Goal**: produce a written map of what exists vs. what is wired up, per role and per surface. Output is a document, not code changes.

### Tasks
- [ ] Enumerate every route, controller action, policy/gate, and navigation component (top nav, sidebars, dashboard widgets) in the project.
- [ ] Build a **Role × Surface matrix**: for each role, list what is currently visible/accessible vs. what *should* be (cross-referenced against R1's intended matrix).
- [ ] For every model that has CRUD (products, vehicles, orders, RFQ, users, vendors), verify the **full path is wired**: route → controller → policy → view → navigation entry. Flag any link in the chain that is missing, stubbed, hardcoded, or returns mock data.
- [ ] Specifically verify each phase marked complete in `task_execution_order.md` actually functions end-to-end (F9, F11). Note especially vehicle listing creation/display and admin screens.
- [ ] Identify every dashboard statistic / counter and record whether it is a real query or a hardcoded/placeholder value (F8).
- [ ] Produce **`AUDIT_FINDINGS.md`**: a categorised gap list (Wiring gap / Fake data / RBAC leak / UX violation / Not implemented), each with file references and the remediation task (R1–R9) it maps to.

### Exit criteria
- `AUDIT_FINDINGS.md` exists and covers all roles and all CRUD surfaces.
- Every explicit finding F1–F13 appears in it, plus any newly discovered analogous issues.

---

## R1 — Role Access Matrix & Navigation Authority (foundation)

**Root cause of F2, F5, F10.** Establish one authoritative definition of role capabilities and drive **all** navigation and menu rendering from it. No more per-view `@if(role == ...)` scattered across blades.

### Intended capability matrix (confirm/adjust against business docs)

| Surface / menu item | customer | private_seller | vendor_admin | vendor_worker/agent | admin | super_admin | rider |
|---|---|---|---|---|---|---|---|
| Browse / Shop | ✅ | ✅ | ✅ | – | – | – | – |
| Cart / Checkout | ✅ | ✅* | ✅* | – | ❌ | ❌ | – |
| My Orders (as buyer) | ✅ | ✅* | ✅* | – | ❌ | ❌ | – |
| Saved Searches / Wishlist | ✅ | ✅* | ✅* | – | ❌ | ❌ | – |
| Request a Part (RFQ buyer) | ✅ | ✅* | ✅* | – | ❌ | ❌ | – |
| **Dashboard** (role home) | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| My Product Listings | ❌ | ✅ | ✅ | ✅ | – | – | – |
| My Vehicle Listings | ❌ | ✅ | ✅ | ✅ | – | – | – |
| Create Product / Vehicle | ❌ | ✅ | ✅ | ✅(perm) | – | – | – |
| Incoming RFQ / submit quotes (seller) | ❌ | ✅ | ✅ | ✅(perm) | – | – | – |
| Vendor sales / orders (as seller) | ❌ | ✅ | ✅ | ✅(perm) | – | – | – |
| Wallet / Payouts | ❌ | ✅ | ✅ | ❌ | view | view | – |
| Vendor User Management | ❌ | ❌ | ✅ | ❌ | – | – | – |
| Approvals queue (vendors/listings) | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | – |
| All-User Management | ❌ | ❌ | ❌ | ❌ | ✅(scoped) | ✅(full) | – |
| Platform Settings | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | – |
| Reports / Analytics | ❌ | own | own | – | ✅ | ✅ | – |
| Delivery tasks | ❌ | ❌ | ❌ | ❌ | dispatch | dispatch | ✅ |

`✅* ` = a seller *may* also act as a buyer, but buyer surfaces live under a clearly separated "Shopping" context, never mixed into the seller dashboard. `(perm)` = gated by vendor-assigned Spatie permission.

### Tasks
- [ ] Define the matrix as **data/config + Spatie permissions**, not inline view logic (a single `navigation`/`abilities` definition keyed by role).
- [ ] Refactor top navigation to render strictly from this definition (F10).
- [ ] Refactor sidebar/dashboard menus likewise.
- [ ] Add/repair **policies and route middleware** so access is enforced server-side, not just hidden in UI (an admin must not be able to hit `/cart` even by URL).
- [ ] Remove customer-only items from admin and seller contexts (F5, F2).

### Exit criteria
- Logging in as each role shows only its matrix-defined items in top nav and dashboard.
- Direct-URL access to out-of-scope routes is rejected by middleware/policy for every role.

---

## R2 — Dashboard Shells & "Home" Navigation for every authenticated role

**Addresses F4, F10.**

### Tasks
- [ ] Ensure every authenticated non-customer role lands on a role-appropriate **dashboard** after login.
- [ ] Provide a persistent, obvious "Dashboard / Home" control in the primary nav for all profiles (logo/home + explicit dashboard link).
- [ ] Customers: home is the shop; a clear account menu replaces a "dashboard."
- [ ] Verify breadcrumb/return-to-dashboard from deep pages.

### Exit criteria
- From any page, each role can reach its dashboard in one click.

---

## R3 — Private Seller Capabilities

**Addresses F1, F2.** Private sellers are sellers, not customers. They must have the seller toolset (scaled to an individual), and must not be presented as customers.

### Tasks
- [ ] Add private-seller dashboard with: Create Product, Create Vehicle, My Listings (products + vehicles), seller orders/leads, incoming RFQ + quote submission, wallet (if transacting), profile/status.
- [ ] Verify the create-product and create-vehicle flows (controllers, validation, media upload from Phase 7) are reachable and functional for `private_seller`, not only `vendor`.
- [ ] Confirm private-seller listings appear on their own dashboard **and** in the public catalogue (the exact "built but not shown" class of bug — verify the full route→view→nav chain).
- [ ] Differentiate private-seller limits vs. vendor (e.g. listing caps) via config where the business model implies it.

### Exit criteria
- A private seller can create, see, edit, and publish both a product and a vehicle, and those listings appear publicly.

---

## R4 — Seller Onboarding: immediate pending profile + list-while-unverified

**Addresses F12, F13. Depends on the confirmed business-rule decision above.**

### Tasks
- [ ] On submitting the vendor/private-seller application, immediately create the seller profile in `pending` status and route the user to their seller dashboard (no dead-end "await approval" screen).
- [ ] Dashboard clearly displays current status: `Pending approval` / `Unverified`.
- [ ] Allow listing creation while pending (per confirmed decision); tag the seller/listings with an **unverified seller** badge surfaced to customers in catalogue and detail views.
- [ ] Gate transaction capability per the confirmed decision (default: display-only until approved — checkout/COD/payout blocked for unverified sellers; behind a config flag).
- [ ] On approval, badge clears and any transaction gating lifts automatically.
- [ ] Reconcile this rule back into `task_execution_order.md` Phase 3 notes and `BUSINESS_MODEL.md`.

### Exit criteria
- New seller sees a working, clearly-pending dashboard immediately after applying and can create a (badged) listing.

---

## R5 — Vendor-Admin User Management (manage users within one vendor)

**Addresses F6.** Leverages existing Spatie permissions and vendor multi-user (`vendor_admin`, `vendor_worker`, `agent`).

### Tasks
- [ ] Vendor-admin screen to invite/create, list, edit, deactivate users **belonging to that vendor only** (strictly scoped — a vendor-admin must never see other vendors' users).
- [ ] Assign roles/permissions to vendor workers/agents within allowed bounds.
- [ ] Enforce scoping in policies (server-side), not just UI filtering.

### Exit criteria
- Vendor-admin manages only their own team; cross-vendor access is impossible by URL or API.

---

## R6 — Super-Admin (and Admin) User Management

**Addresses F7.** Site-wide user administration.

### Tasks
- [ ] Super-admin user-management screen: search/list all users with filters (role, status, verified, vendor).
- [ ] Actions: create user, edit, suspend/reactivate, soft-delete, change role.
- [ ] **Password reset** on behalf of a user (trigger reset link and/or set temporary password with forced change).
- [ ] **Bypass / manually mark email verification** for an account (audited).
- [ ] Approve/reject/suspend vendors and sellers from here or the approvals queue.
- [ ] Define **admin vs super-admin** scope: e.g. admin may manage customers/sellers but not other admins or platform settings; super-admin has full reach. Enforce in policies.
- [ ] Every privileged user action is **audit-logged** (actor, target, action, timestamp) per the audit-logging phase.

### Exit criteria
- Super-admin can fully administer any account; sensitive actions are audited; admin scope is correctly limited.

---

## R7 — Real Statistics (no placeholders)

**Addresses F8.** Replace fake/hardcoded counters with live queries.

### Tasks
- [ ] Identify every dashboard stat (Pending Approvals, Listings, Active Vendors, Total Users, plus any others found in R0).
- [ ] Back each with an accurate, efficient query (correct status filters; respect soft-deletes; scope per role — a vendor sees *their* numbers, super-admin sees site-wide).
- [ ] Cache expensive aggregates with sane invalidation; never hardcode.
- [ ] Verify counts against direct DB checks.

### Exit criteria
- Every displayed statistic matches a verifiable database query for the logged-in scope.

---

## R8 — Functional Verification of "Implemented" Phases

**Addresses F9, F11.** For each phase claimed complete, prove it works end-to-end; fix what doesn't.

### Tasks
- [ ] Walk each completed phase's deliverables and validation criteria from `task_execution_order.md`; execute the happy path as the relevant role.
- [ ] Prioritise items flagged untestable in findings: **vehicle listing** (create/approve/display/search) and **admin screens**.
- [ ] For each broken/stubbed item, fix the wiring or implement the missing piece, then re-verify.
- [ ] Record pass/fail per deliverable in `AUDIT_FINDINGS.md`.

### Exit criteria
- Every phase marked complete is demonstrably exercisable by its intended role.

---

## R9 — "Apply as Vendor" Form Redesign (UX)

**Addresses F3.** Must comply with `UI_STANDARDS.md`.

### Tasks
- [ ] Replace the single long-scroll form with a **multi-step / wizard** flow (e.g. Business details → Documents → Bank details → Review & submit), with progress indication.
- [ ] Per-step validation; allow back/next; preserve entered data between steps.
- [ ] Apply spacing, grouping, and field-design rules from `UI_STANDARDS.md`.
- [ ] Keep it accessible (labels, keyboard nav, error summaries) and mobile-friendly.
- [ ] Apply the same pattern to any other offending long-scroll forms found in R0.

### Exit criteria
- Application is a stepped flow conforming to `UI_STANDARDS.md`; no single overwhelming scroll.

---

## R10 — Regression & Cross-Role Validation

### Tasks
- [ ] Re-run R0's Role × Surface matrix as a checklist; every cell behaves as defined in R1.
- [ ] Log in as each of the 7 role types and confirm nav, dashboard, capabilities, and stats.
- [ ] Attempt out-of-scope URL access per role; confirm server-side rejection.
- [ ] Confirm seller pending/unverified flow and that approval clears gating.
- [ ] Add/extend automated tests for RBAC (policy allow/deny per role) and for stat queries.
- [ ] Update `AUDIT_FINDINGS.md` to closed status; update business/phase docs where rules changed.

### Exit criteria
- All findings F1–F13 closed and verified; no new RBAC leaks; tests cover the role matrix.

---

## Execution principles (apply throughout)

1. **Audit before edit** — R0 produces the map; everything else references it.
2. **Findings are samples, not the full set** — for each fixed instance, search for siblings (the "built but not shown" pattern almost always recurs).
3. **Authorization is server-side** — hiding a menu item is never sufficient; back it with policy/middleware.
4. **Single source of truth for navigation/abilities** — no scattered role conditionals.
5. **No hardcoded data or fees** — stats from queries, config from `platform_settings`.
6. **Confirm the F12/F13 transaction decision** before R4; don't guess silently.
7. **Checkpoint after each R-task** — summarise what changed and what was discovered before proceeding.

---

## Suggested order

`R0 → R1 → R2 → R3 → R4 → R5 → R6 → R7 → R8 → R9 → R10`

R5/R6/R7 may parallelise after R1. R9 (UI) can run any time after R0.

---

*Document Version: 1.0*
*Companion: AUDIT_FINDINGS.md (produced by R0), UI_STANDARDS.md, BUSINESS_MODEL.md, task_execution_order.md v2.0*
*Status: Ready for execution*
