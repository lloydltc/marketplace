# Salma Drive Marketplace - Task Execution Order (v2.0)

## Overview

This document supersedes v1.0 **from Phase 8 onward**. Phases 1–7 are unchanged and treated as complete/in-progress per v1.0 — except for the **Phase 7R retrofit tasks** below, which adapt already-built foundations to the approved business model.

**Companion document**: `BUSINESS_MODEL.md` is the authoritative reference for all monetization, wallet, fulfilment, and RFQ rules. Every phase below that touches money must conform to it.

**Critical rule (unchanged)**: Do not skip phases. Each phase depends on previous phases.

---

## Implementation Pyramid (v2.0)

```
Phase 25: Deployment & DevOps
   ↓
Phase 24: Reporting & Analytics
   ↓
Phase 23: Production Hardening & Security
   ↓
Phase 22: Audit Logging & Monitoring
   ↓
Phases 18–21: Engagement features (parallel: Notifications, Reviews, Promotions, CMS)
   ↓
Phases 15–17: Differentiators (RFQ, Concierge, Vehicle Promotion)
   ↓
Phase 14: Delivery & Rider Management (FBS + COD)
   ↓
Phase 13: Vendor Wallet & Ledger          ← heart of the money system
   ↓
Phase 12: Orders & Fulfilment Tracking
   ↓
Phase 11: Pesepay Integration & Commission Engine
   ↓
Phase 10: Checkout & Fulfilment Selection
   ↓
Phase 9: Cart (vendor-split, fulfilment-aware)
   ↓
Phase 8: Search & Filtering
   ↓
Phases 1–7: Foundation (COMPLETE per v1.0) + Phase 7R retrofit
```

---

## Phases 1–7: Status (unchanged from v1.0)

| Phase | Scope | Status |
|-------|-------|--------|
| 1 | Project Foundation (Laravel 12, Docker, PostgreSQL, Redis, testing, CI) | Per v1.0 |
| 2 | Authentication & Authorization (RBAC, roles, policies) | Per v1.0 |
| 3 | Vendor Management (approval, documents, bank verification, tiers, `commission_rate`) | Per v1.0 |
| 4 | Categories (hierarchy, `commission_override`) | Per v1.0 |
| 5 | Products (listing, approval, inventory, SKU) | Per v1.0 |
| 6 | Vehicle Listings (vehicle fields, manual approval, search) | Per v1.0 |
| 7 | Media Management (images, optimization, storage abstraction) | Per v1.0 |

Do **not** rebuild any of the above. Apply only the retrofit tasks below.

---

## Phase 7R: Retrofit Tasks (changes to already-completed work)

**Duration**: 2–3 days
**Dependencies**: Phases 1–7
**Why**: The approved business model (dual fulfilment tracks, vendor wallet, RFQ, listing promotion, rider deliveries) requires small additive changes to existing schema and roles. All changes are additive migrations — no destructive rework.

### 7R.1 Roles (touches Phase 2)

- [ ] Add `RIDER` to the role enum (`super_admin, admin, vendor, private_seller, customer, rider`)
- [ ] Create rider permissions set (view assigned deliveries, update delivery status, record COD collection)
- [ ] Add policies/middleware for rider routes

### 7R.2 Platform Settings (new shared foundation)

- [ ] Create `platform_settings` table (key, value, type, description, updated_by, timestamps)
- [ ] Create settings service with cache + invalidation
- [ ] Create admin settings UI (edit fees/thresholds per `BUSINESS_MODEL.md` §9)
- [ ] Seed defaults from `BUSINESS_MODEL.md` §9
- [ ] Rule: **no monetary value is ever hardcoded** — all fee logic reads this service

### 7R.3 Vendors (touches Phase 3)

- [ ] Add `default_fulfilment` (enum: `fbs`, `vendor`, `both`) to vendors
- [ ] Add `cod_eligible` (boolean, derived from wallet standing — see Phase 13) to vendors
- [ ] Confirm `commission_rate` participates in commission resolution order (vendor → category → platform default)
- [x] **List-while-unverified (R4 remediation)**: a `pending` vendor/private seller can build & publish listings, badged "Unverified seller". Listings are display-only until approval — server-side gating via `Vendor::canTransact()` / `Vehicle::ownerIsVerified()` + kill-switch setting `sellers.unverified_can_transact` (default `0`). Product/Vehicle create policies dropped the `isApproved()` requirement; transaction blocking moved to cart/checkout/RFQ-accept. See BUSINESS_MODEL.md §11.

### 7R.4 Products (touches Phase 5)

- [ ] Add `fulfilment_type` (enum: `fbs`, `vendor`, `both`) to products
- [ ] Add `cod_allowed` (boolean) to products
- [ ] Index for FBS placement boost in search

### 7R.5 Vehicles (touches Phase 6)

- [ ] Add promotion fields to vehicles: `featured_until` (timestamp, nullable), `bumped_at` (timestamp, nullable), `listing_package_id` (nullable)
- [ ] Add `seller_verified_badge` (boolean) leveraging Phase 3 document verification
- [ ] Confirm vehicle listings are excluded from all checkout/commission paths (lead-gen only)

### 7R.6 Currency & money handling audit (touches Phases 5–6)

- [ ] Audit all money columns: store in minor units (integer cents) or exact decimals — never floats
- [ ] Every future money table records currency explicitly

### Validation Criteria

- Migrations run cleanly on existing data
- Existing tests still pass after retrofit
- Settings service returns seeded defaults; admin can edit and cache invalidates
- Rider role can authenticate but reach only rider routes

---

## Phase 8: Search & Filtering

**Duration**: 3–5 days
**Dependencies**: Phases 5, 6, 7R

### Tasks

- [ ] Advanced search with filters (category, price, brand, vehicle compatibility)
- [ ] Search result ranking — include **FBS placement boost** (config-weighted, per `BUSINESS_MODEL.md` §3) and **featured vehicle priority** (per §8)
- [ ] Autocomplete suggestions
- [ ] Saved searches
- [ ] **Zero-results → RFQ entry point**: "Can't find it? Request it" call-to-action stub (links into Phase 15)

### Validation Criteria

- FBS boost and featured priority are settings-driven and can be set to zero
- Zero-results page renders RFQ call-to-action

---

## Phase 9: Cart

**Duration**: 3–4 days
**Dependencies**: Phase 8

### Tasks

- [ ] Add to cart / cart management
- [ ] **Vendor-split cart**: group cart items by vendor and fulfilment track (an order is per-vendor; a checkout may produce multiple orders)
- [ ] Per-group delivery estimate display (FBS fee from settings; VF set by vendor)
- [ ] COD eligibility indicator per group (product `cod_allowed` × vendor `cod_eligible` × track rules)
- [ ] Coupons hook (deferred to Phase 20)

### Validation Criteria

- Mixed-vendor cart correctly splits into per-vendor order groups
- COD shown only where the fulfilment matrix (`BUSINESS_MODEL.md` §3) allows it

---

## Phase 10: Checkout & Fulfilment Selection

**Duration**: 4–5 days
**Dependencies**: Phase 9

### Tasks

- [ ] Guest checkout support
- [ ] Shipping address selection
- [ ] **Fulfilment selection per order group**: FBS (platform delivery) vs vendor-fulfilled, constrained by product/vendor settings
- [ ] **Payment method selection per the COD matrix**: prepaid (Pesepay) always available; COD only where eligible
- [ ] Delivery fee calculation (zone-based flat fee from settings for FBS)
- [ ] Order summary with itemised commission-exclusive pricing for buyer (buyer sees price + delivery; commission is seller-side)
- [ ] Payment initiation handoff to Phase 11

### Validation Criteria

- Ineligible COD combinations are impossible to submit (server-side enforced, not just hidden in UI)
- Delivery fees read from `platform_settings`

---

## Phase 11: Pesepay Integration & Commission Engine

**Duration**: 5–7 days
**Dependencies**: Phase 10
**Risk note (carried from v1.0)**: start early in sandbox.

### Tasks

#### 11.1 Pesepay

- [ ] Payment initiation (card + EcoCash/mobile money rails)
- [ ] **Webhook handling — signed, verified, idempotent** (duplicate webhooks must not double-process)
- [ ] Payment status tracking; never trust client redirects alone
- [ ] Refund processing
- [ ] **Collect-then-distribute model**: all funds land in the platform account; vendor settlement happens via wallet payouts (Phase 13) — no split payments at the gateway

#### 11.2 Commission Engine

- [ ] Implement commission resolution order: vendor override → category override → platform default (all existing fields from Phases 3–4 + settings)
- [ ] **Snapshot commission rate and computed amounts onto the order at order time** (immutable thereafter)
- [ ] Commission applies identically to standard orders and RFQ-converted orders
- [ ] Unit-test the resolution order exhaustively

### Database Changes (sketch)

```
payments: id, order_id, gateway, gateway_ref (unique), method, amount, currency,
          status, webhook_payload_hash, timestamps
order financial snapshot fields: commission_rate_applied, commission_amount,
          delivery_fee, net_to_vendor
```

### Validation Criteria

- Replayed webhook produces no duplicate state change or money movement
- Historical orders retain original commission after rate changes

---

## Phase 12: Orders & Fulfilment Tracking

**Duration**: 5–7 days
**Dependencies**: Phase 11

### Tasks

- [ ] Order creation (one order per vendor group) and tracking
- [ ] Order status state machine covering **both tracks**:
  - Common: `pending_payment → paid/cod_pending → processing → ...`
  - FBS: `... → awaiting_pickup → out_for_delivery → delivered → completed`
  - VF: `... → vendor_shipping → delivered → completed`
- [ ] COD orders: `cod_pending` until cash collection confirmed (rider for FBS in Phase 14; vendor confirmation for VF)
- [ ] **Completion event emits a settlement event** consumed by the wallet (Phase 13)
- [ ] Order history (buyer + vendor views)
- [ ] Invoice generation
- [ ] Cancellation/refund flows tied to Phase 11 refunds

### Validation Criteria

- Illegal state transitions rejected
- Every completed order emits exactly one settlement event (idempotent)

---

## Phase 13: Vendor Wallet & Ledger ★ NEW — heart of the money system

**Duration**: 5–7 days
**Dependencies**: Phases 11, 12
**Authoritative spec**: `BUSINESS_MODEL.md` §4

### Objectives

- Double-entry, append-only vendor ledger; derived balances
- Settlement engine: order completion → wallet movements
- Wallet-floor enforcement; payout cycle; top-ups

### Tasks

#### 13.1 Ledger Core

- [ ] Create `vendor_wallets` and `wallet_ledger_entries` (append-only; entries never updated/deleted; corrections are new entries)
- [ ] Entry types per `BUSINESS_MODEL.md` §4: `SALE_CREDIT, COMMISSION_DEBIT, DELIVERY_FEE_DEBIT, TOP_UP, PAYOUT, REFUND_ADJUSTMENT, MANUAL_ADJUSTMENT`
- [ ] Every entry references its source record (order, payout, gateway transaction, admin user)
- [ ] Balance always computed from entries (materialised/cached with reconciliation job, never authoritative as a mutable column)

#### 13.2 Settlement Engine

- [ ] Consume order-completion events:
  - Platform-collected order (FBS or prepaid VF) → `SALE_CREDIT` of net proceeds
  - VF COD order → `COMMISSION_DEBIT`
- [ ] Idempotent processing (event id de-duplication)
- [ ] Refund adjustments mirror original movements

#### 13.3 Enforcement & Payouts

- [ ] Wallet floor check (settings-driven, default 0): below floor → auto-suspend new listings + revoke `cod_eligible`; auto-reinstate on top-up
- [ ] Vendor top-up via Pesepay
- [ ] **Weekly payout cycle** to verified bank accounts (Phase 3 prerequisite), minimum payout from settings, roll-over below minimum
- [ ] Payout batch generation + admin approval step + export/record of payout references
- [ ] Vendor wallet dashboard (balance, ledger history, next payout)
- [ ] Admin manual adjustment (reason required, fully audited)

### Database Changes (sketch)

```
vendor_wallets: id, vendor_id (unique), currency, cached_balance, reconciled_at
wallet_ledger_entries: id, wallet_id, type, direction, amount, currency,
        source_type, source_id, idempotency_key (unique), created_by, created_at
payouts: id, vendor_id, amount, period_start, period_end, bank_account_id,
        status, reference, timestamps
```

### Validation Criteria

- Sum of ledger entries always equals cached balance (reconciliation job proves it)
- Duplicate settlement events cause no double movement
- Vendor below floor cannot create listings or take COD; top-up restores within one request cycle

---

## Phase 14: Delivery & Rider Management (FBS + COD) ★ NEW

**Duration**: 5–7 days
**Dependencies**: Phases 12, 13, 7R (rider role)

### Objectives

- Operationalise Fulfilled-by-Salma: rider assignment, delivery lifecycle, cash collection
- Close the COD loop so commission capture is structurally guaranteed on FBS

### Tasks

#### 14.1 Delivery Core

- [ ] Delivery model linked to FBS orders; zones table (zone → flat fee, settings-driven)
- [ ] Rider assignment (manual dispatch by admin at MVP; auto-assignment later)
- [ ] Rider mobile-friendly web views: assigned deliveries, status updates, proof of delivery (photo/signature)
- [ ] Delivery status feeds order state machine (Phase 12)

#### 14.2 COD Cash Handling

- [ ] Rider records cash collected per order
- [ ] **Cash reconciliation**: end-of-day rider cash-in session; collected COD reconciled against delivered orders before settlement events fire
- [ ] Discrepancy flagging + admin resolution workflow

#### 14.3 VF Delivery (light)

- [ ] Vendor-fulfilled orders: vendor marks shipped/delivered; buyer confirmation or auto-complete after N days (settings)

### Validation Criteria

- FBS COD order only settles (wallet credit) after rider cash-in reconciliation
- Delivery fee retained by platform is visible in order financial snapshot

---

## Phase 15: RFQ — Public Requests ★ NEW (differentiator)

**Duration**: 5–7 days
**Dependencies**: Phases 11, 12 (conversion to orders), Phase 8 (entry point)
**Authoritative spec**: `BUSINESS_MODEL.md` §6 Tier 1

### Tasks

#### 15.1 Request Lifecycle

- [ ] Buyer posts structured request: vehicle make/model/year, part description, photos (Phase 7 media), budget range, location
- [ ] Request states: `open → quoted → accepted → converted → closed/expired`
- [ ] Matching/routing: notify sellers by category + brand/vehicle coverage
- [ ] Sellers submit quotes (price, condition, delivery estimate, expiry)
- [ ] Buyer compares quotes side-by-side; accepting a quote **converts to a normal order** → standard commission engine applies

#### 15.2 Fair-Use Thresholds (settings-driven, launch OFF — see rollout §10 of business model)

- [ ] Count threshold: N free requests/buyer/month; overage fee via Pesepay beyond it
- [ ] Value threshold: requests above configured estimated value require a **refundable commitment deposit** (Pesepay) — credited in full against the converted order; forfeiture/partial-retention rule on abandonment from settings
- [ ] Deposit ledgering: deposits recorded immutably and reconciled (extend ledger pattern or buyer-side deposit records)

#### 15.3 Quality Controls

- [ ] Request moderation queue (spam/abuse)
- [ ] Quote expiry handling; request auto-expiry

### Validation Criteria

- Accepted quote produces an order indistinguishable from a normal order to the settlement engine
- Thresholds can be toggled entirely off via settings (launch configuration)
- Deposit refund path proven end-to-end in sandbox

---

## Phase 16: Concierge Service ★ NEW (manual-first)

**Duration**: 3–4 days (deliberately small — operations over code)
**Dependencies**: Phases 14, 15
**Authoritative spec**: `BUSINESS_MODEL.md` §6 Tier 2

### Tasks

- [ ] Concierge request form (separate from public RFQ; positions the trust promise: "we find it, verify it, deliver it")
- [ ] Admin workflow queue: new → sourcing → quoted-to-buyer → paid → fulfilling → delivered → closed
- [ ] Service fee calculation: flat minimum + value percentage, tiered table from settings
- [ ] Payment via Pesepay (platform collects part price + service fee + delivery)
- [ ] If sourced from an on-platform vendor → settle via wallet like an FBS order
- [ ] **Explicitly out of scope**: sourcing automation, vendor bidding automation — one ops person runs this until volume justifies more

### Validation Criteria

- A concierge request can be run end-to-end by an admin with no developer involvement
- Fees read from settings; financials appear in reporting (Phase 24)

---

## Phase 17: Vehicle Listing Promotion ★ NEW (lead-gen revenue)

**Duration**: 3–4 days
**Dependencies**: Phases 6, 7R.5, 11
**Authoritative spec**: `BUSINESS_MODEL.md` §8

### Tasks

- [ ] Featured listing purchase (Pesepay) → sets `featured_until`; surfaced by Phase 8 ranking
- [ ] Listing bump purchase → sets `bumped_at`
- [ ] Dealer packages: monthly bundles (X listings + Y features/bumps) with entitlement tracking
- [ ] Verified seller badge purchase (gated on Phase 3 document verification)
- [ ] Expiry jobs (featured/packages lapse automatically)
- [ ] Promotion revenue recorded for reporting

### Validation Criteria

- Expired promotions demote automatically without intervention
- A buyer can never check out a vehicle — promotion is the only paid path on this side

---

## Phase 18: Notifications

**Duration**: 4–5 days
**Dependencies**: Phases 12–17 (event sources)

- [ ] Email notifications (orders, payouts, wallet floor warnings, RFQ matches/quotes, delivery updates, promotion expiry)
- [ ] In-app notifications
- [ ] Notification preferences
- [ ] SMS (future)

---

## Phase 19: Reviews & Ratings

**Duration**: 4–5 days
**Dependencies**: Phase 12

- [ ] Product reviews (verified-purchase only)
- [ ] Seller ratings; **rider/delivery rating** on FBS orders
- [ ] Review moderation and responses

---

## Phase 20: Promotions & Coupons

**Duration**: 3–4 days
**Dependencies**: Phases 10, 11

- [ ] Admin promotions management; coupon generation/validation
- [ ] Discount application at checkout — **commission computed on post-discount price; discount cost attribution (platform-funded vs vendor-funded) recorded on the order snapshot**

---

## Phase 21: CMS Pages

**Duration**: 2–3 days

- [ ] Static pages, CMS editor, publishing, SEO
- [ ] Include: How FBS works, COD policy, RFQ guide, Concierge service page, fee schedule page (rendered from settings)

---

## Phase 22: Audit Logging & Monitoring

**Duration**: 4–5 days
**Dependencies**: all previous

As v1.0 Phase 17, plus:

- [ ] **Money-trail auditing**: every wallet entry, payout, deposit, manual adjustment, and settings change logged with actor
- [ ] Reconciliation alarms (ledger vs cached balances; gateway records vs payments table)
- [ ] Queue failure monitoring for settlement jobs

---

## Phase 23: Production Hardening & Security

**Duration**: 5–7 days

As v1.0 Phase 18, plus business-model-specific fraud surface:

- [ ] COD abuse controls (buyer COD failure rate limits; vendor self-buying detection)
- [ ] RFQ spam/scraping rate limits
- [ ] Wallet attack surface review (top-up → payout laundering pattern checks; payout bank account change re-verification)
- [ ] Webhook signature verification penetration test

---

## Phase 24: Reporting & Analytics

**Duration**: 5–7 days
**Dependencies**: Phases 12–17

As v1.0 Phase 19, plus:

- [ ] **Revenue by stream**: parts commission, delivery margin, concierge fees, RFQ fees, vehicle promotion (the 5 streams of `BUSINESS_MODEL.md` §2)
- [ ] Wallet/payout reports; COD reconciliation reports
- [ ] RFQ funnel: requests → quotes → conversion rate
- [ ] FBS vs VF mix; rider performance

---

## Phase 25: Deployment & DevOps

**Duration**: 5–7 days

As v1.0 Phase 20 (DigitalOcean, CI/CD, monitoring, backups), plus:

- [ ] Backup priority: ledger and payments tables (point-in-time recovery)
- [ ] Pesepay production credentials rotation procedure
- [ ] Settlement queue worker supervision (Supervisor/systemd) and dead-letter handling

---

## Parallel Development Opportunities (v2.0)

- **Sequential money spine** (cannot parallelise): 10 → 11 → 12 → 13 → 14
- After Phase 13: Phases 15, 16, 17 can run in parallel
- Phases 18–21 parallel after their event sources exist
- Phase 25 groundwork can start any time

---

## Milestone Timeline (v2.0)

| Milestone | Phases | Notes |
|-----------|--------|-------|
| Retrofit complete | 7R | Settings service live — prerequisite for all money work |
| Browsable marketplace | 8–9 | Search + cart |
| **Money spine complete** | 10–13 | Prepaid checkout, commission, orders, wallet |
| FBS operational | 14 | Riders + COD reconciliation |
| **MVP Launch** | 1–14 + 15 (free tier) | Public RFQ free tier ON, thresholds OFF |
| Differentiators live | 16–17 | Concierge (manual) + vehicle promotion |
| Feature complete | 18–21 | |
| Production ready | 22–25 | |

Monetization switch-on order at/after launch follows `BUSINESS_MODEL.md` §10.

---

## Risk Mitigation (v2.0 additions)

| Risk | Mitigation |
|------|-----------|
| Pesepay integration delays | Start Phase 11 early in sandbox (carried from v1.0) |
| COD commission leakage | Wallet-floor enforcement (Phase 13) shipped **before** VF-COD is enabled |
| Rider cash discrepancies | End-of-day reconciliation gate before settlement (Phase 14) |
| Ledger corruption | Append-only entries, idempotency keys, reconciliation job + alarms |
| Charging too early kills liquidity | All thresholds/fees settings-driven and launch-OFF per rollout plan |
| Scope creep in concierge | Manual-first; automation explicitly out of scope until volume |

---

## Phase Completion Checklist

Unchanged from v1.0, with one addition for any phase touching money:

- ✅ All money operations proven idempotent (duplicate events/webhooks tested)
- ✅ All fees/thresholds read from `platform_settings` (no hardcoded values)

---

*Document Version: 2.0*
*Supersedes: v1.0 from Phase 8 onward; Phases 1–7 unchanged + Phase 7R retrofit*
*Companion: BUSINESS_MODEL.md v1.0*
*Last Updated: June 2026*
*Status: Approved*
