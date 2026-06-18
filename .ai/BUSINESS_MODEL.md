# Salma Drive Marketplace — Business & Monetization Model

**Document purpose**: Authoritative reference for how the platform makes money and how money moves. All features in `task_execution_order.md` Phase 8+ must conform to this document. Written code-free and tool-agnostic; intended as context for AI-assisted development sessions.

**Golden rule**: Every rate, fee, threshold, and limit defined here is **configuration, never a hardcoded value**. All monetary rules live in a `platform_settings` store (database-backed, admin-editable, cached) so they can be tuned without deployment.

---

## 1. Platform Positioning

Salma Drive is a **two-sided automotive marketplace** with two structurally different businesses under one roof:

| Side | What it is | How money is made |
|------|-----------|-------------------|
| **Parts & accessories** | Transactional marketplace — payment flows through the platform (or platform-controlled COD) | Commission per sale + delivery fees |
| **Vehicle listings** | Lead-generation marketplace — cars CANNOT be bought online; the transaction completes offline | Paid listing promotion (featured, bumps, packages) |

Sellers are of two types: **Vendors** (registered businesses, multi-user accounts) and **Private Sellers** (individuals). Both can list; commission/fee treatment may differ by seller type (config-driven).

---

## 2. Revenue Streams (5)

| # | Stream | Who pays | When |
|---|--------|----------|------|
| 1 | **Parts commission** | Seller | On every completed parts order (prepaid or COD) |
| 2 | **Delivery fees** | Buyer (margin retained by platform on FBS deliveries) | On platform-delivered orders |
| 3 | **Concierge service fees** | Buyer | On concierge (platform-sourced) requests |
| 4 | **RFQ fees** | Buyer | Overage fee past free monthly limit; refundable commitment deposit on high-value requests |
| 5 | **Vehicle listing promotion** | Seller | Featured placement, listing bumps, dealer packages, verified badges |

**Launch principle**: all streams are usage-based. No seller pays anything before earning. Vendor subscriptions / quote credits are deliberately **deferred** until marketplace liquidity exists (revisit post-launch).

---

## 3. Fulfilment Tracks (Amazon-style dual model)

Every parts order is fulfilled on exactly one of two tracks. Products declare which track(s) they support.

### Track A — Fulfilled by Salma (FBS)

- Vendor sells; **platform riders deliver** and collect payment (cash, or buyer prepaid via gateway).
- Money lands with the platform first → commission + delivery fee deducted at source → net amount **credited to vendor wallet**.
- Commission capture is structurally guaranteed.
- **FBS incentives** (drive voluntary adoption): better search placement, "Salma Delivered" trust badge, COD eligibility by default.

### Track B — Vendor-Fulfilled (VF)

- Vendor handles their own delivery.
- **Prepaid VF order**: gateway collects → commission deducted → net credited to vendor wallet. Clean.
- **COD VF order**: buyer pays the vendor directly in cash → platform never touches the money → commission is **debited from the vendor's wallet** when the order is marked completed.

### COD / payment matrix

| | Prepaid (gateway) | Cash on Delivery |
|---|---|---|
| **FBS** | Platform collects → deduct → credit wallet | Rider collects cash → deduct → credit wallet |
| **VF** | Platform collects → deduct → credit wallet | Vendor collects cash → **wallet debited for commission** |

**COD enforcement rule**: VF COD is a privilege gated on wallet standing. A vendor whose wallet balance falls below the configured floor loses VF-COD eligibility (and listing activity — see §4) until topped up.

---

## 4. Vendor Wallet & Ledger (heart of the system)

A single per-vendor wallet, backed by a **double-entry, append-only ledger**. Balance is always *derived* from ledger entries, never stored as a mutable field that gets overwritten.

### Ledger entry types

| Type | Direction | Trigger |
|------|-----------|---------|
| `SALE_CREDIT` | + | Net proceeds of platform-collected order (FBS or prepaid VF) |
| `COMMISSION_DEBIT` | − | Commission on VF COD order completion |
| `DELIVERY_FEE_DEBIT` | − | Delivery fee adjustments where applicable |
| `TOP_UP` | + | Vendor tops up via payment gateway |
| `PAYOUT` | − | Weekly payout to vendor bank account |
| `REFUND_ADJUSTMENT` | ± | Order refunds / disputes |
| `MANUAL_ADJUSTMENT` | ± | Admin correction (requires reason + audit log) |

### Rules

1. **Immutability**: ledger entries are never edited or deleted; corrections are new entries.
2. **Every entry references its source** (order id, payout id, top-up transaction id, admin user id).
3. **Wallet floor**: balance below configured floor (default: 0) → automatic suspension of new listings and VF-COD eligibility. Auto-reinstated on top-up. No manual chasing.
4. **Payout cycle**: weekly, to the vendor's **verified** bank account (Phase 3 verification is a prerequisite). Configurable minimum payout amount; balances below minimum roll over.
5. **Idempotency**: all money-moving operations (webhooks, payouts, debits) must be idempotent — duplicate gateway webhooks must not double-credit.

---

## 5. Commission Engine

Commission resolution order (first match wins):

1. Per-vendor override (`vendors.commission_rate`) — exists from Phase 3
2. Per-category override (`categories.commission_override`) — exists from Phase 4
3. Platform default rate (`platform_settings`)

- Commission is computed and **snapshotted onto the order at order time** (rates change; historical orders must not).
- Applies to parts orders and converted RFQ orders identically.
- Vehicle listings carry **no commission** (offline transaction) — they are monetized via promotion only (§8).

---

## 6. RFQ — "Request a Part" (two tiers)

### Tier 1 — Public RFQ (free, fair-use limited)

Buyer posts a structured request (vehicle make/model/year, part description, photos, budget range, location). All matching sellers see it and submit quotes. Buyer accepts a quote → converts to a normal platform order → normal commission applies.

**Fair-use thresholds (combined model, both config-driven):**

| Control | Mechanism | Default (tunable) |
|---------|-----------|-------------------|
| **Count threshold** | Hard free limit per buyer per month; small fee per request beyond it | 3 free / month, then flat fee per request |
| **Value threshold** | Requests above an estimated value require a **refundable commitment deposit** — credited in full against the order if the buyer purchases through the platform; forfeited (or partially retained, config) if buyer abandons after accepting a quote | Threshold ~US$500; deposit ~5% of estimated value |

Design intent: the count limit filters serial browsers; the refundable deposit filters tyre-kickers on big-ticket requests **without taxing serious high-value buyers** (they effectively pay nothing) and nudges the transaction to stay on-platform.

**Matching**: requests are routed to sellers by category + brand/vehicle coverage; sellers receive notifications. Quotes have an expiry. Buyer sees quotes side-by-side.

### Tier 2 — Concierge RFQ (paid service)

Buyer pays Salma Drive to **source the part, verify it, handle payment, and deliver end-to-end**. Positioned as the trust product: "we find it, we check it, we bring it."

- **Pricing**: service fee = flat minimum + percentage of part value (tiered, config-driven).
- **Operations**: deliberately **manual at MVP** — a request form, an admin workflow queue, and the rider network. One ops person runs it. No sourcing automation is built until volume justifies it.
- Concierge orders settle like FBS orders: platform collects everything, vendor (if sourced on-platform) is credited via wallet.

---

## 7. Payments

- **Gateway**: Pesepay (as per Phase 11 of the execution order) — covers card and EcoCash/mobile money rails under one integration.
- **Collection model**: the gateway does not provide automatic split payments, so the platform operates **collect-then-distribute**: all gateway funds land in the platform account; vendors are paid via the weekly wallet payout cycle. This guarantees commission capture before money leaves and improves platform cash flow.
- **Webhooks**: payment status webhooks must be verified, idempotent, and drive order state transitions; never trust client-side redirects alone.
- **COD** remains available per the fulfilment matrix in §3.

---

## 8. Vehicle Listing Monetization (lead-gen side)

No online checkout for vehicles. Monetize visibility:

| Product | Description |
|---------|-------------|
| **Free basic listing** | Builds inventory liquidity; standard placement, standard duration |
| **Featured listing** | Top-of-search / homepage placement for N days |
| **Listing bump** | Re-sorts listing to top of recency feed |
| **Dealer packages** | Monthly bundle for vendors: X active listings + Y features/bumps |
| **Verified seller badge** | Paid verification (leverages Phase 3 document verification) |

All purchasable via the gateway; promotion state (featured_until, bumped_at, package) lives on the listing.

---

## 9. Default Pricing Schedule (all tunable in `platform_settings`)

| Setting | Default | Notes |
|---------|---------|-------|
| Platform commission (parts) | 10% | Matches existing `vendors.commission_rate` default |
| Delivery fee (FBS) | Zone-based flat fee | Buyer pays; platform retains margin over rider cost |
| Concierge fee | max(US$5, 10% of part value) | Tiered table by value band |
| Public RFQ free quota | 3 requests / buyer / month | Count threshold |
| RFQ overage fee | US$1 / request | Beyond free quota |
| RFQ value threshold | US$500 estimated value | Above this → commitment deposit |
| RFQ commitment deposit | 5% of estimated value, refundable | Credited against converted order |
| Wallet floor | US$0 | Below → listing + VF-COD suspension |
| Payout cycle | Weekly | Minimum payout US$10, roll-over below |
| Featured vehicle listing | US$10 / 7 days | Placeholder — market-test |
| Listing bump | US$2 | Placeholder — market-test |

These numbers are **starting hypotheses**, not commitments. Expect monthly tuning post-launch — hence config-driven everything.

---

## 10. Monetization Rollout Sequence

1. **Switch on at launch**: parts commission (prepaid orders), delivery fees, free public RFQ, free vehicle listings.
2. **Weeks after launch**: VF-COD with wallet debits, vehicle listing promotion, RFQ thresholds (once request volume proves abuse exists — don't add friction pre-emptively).
3. **When volume justifies**: concierge service (manual ops), then automation.
4. **Post-liquidity (revisit later)**: vendor subscriptions, quote credits, advertising.

**Anti-pattern to avoid**: charging sellers before the platform sends them business. Marketplaces die from charging too early, not too late.

---

## 11. Cross-Cutting Requirements

- **Config-driven fees**: single `platform_settings` service; admin UI for editing; cached with invalidation; every fee computation reads from it.
- **Money as integers**: store amounts in minor units (cents) or use exact decimal types; never floats.
- **Multi-currency awareness**: USD-primary with ZWL display consideration (existing `price_usd`/`price_zwl` fields); ledger entries record currency explicitly.
- **Auditability**: every fee charged, deposit taken, or balance change must be reconstructable from immutable records (feeds Phase: Audit Logging).
- **Idempotent money operations**: webhooks, payouts, wallet debits.
- **New role required**: `RIDER` (delivery personnel) — affects the Phase 2 role enum (see retrofit tasks in `task_execution_order.md`).
- **A seller is not a customer (clean role separation)**: buyer surfaces (cart, checkout, My Orders, saved searches, RFQ-as-buyer) are for `customer` accounts (and guests) **only**. `private_seller`, `vendor_admin/worker`, `agent`, `admin`, `super_admin`, `rider` get **zero** buyer surfaces — not in nav, rejected server-side by URL, and the public catalogue hides buy CTAs from them. Sellers instead get a **"Sales / Orders Received"** surface: vendors see orders customers placed against their listings (`vendor.orders.index`); private sellers (lead-gen vehicles, no checkout) get an **enquiries** surface (`seller.sales.index`). Three distinct order views — customer *My Orders*, seller *Sales*, admin *All Orders* — are never conflated. Enforced via `config/navigation.php` (`shopping_roles` = `['customer']`) + `ShopAccess` middleware + `role:customer` route group.
- **List-while-unverified (onboarding liquidity)**: a seller (vendor or private) may build and publish listings while their account/documents are still pending. Such listings are shown publicly with an **"Unverified seller"** badge and are **display-only** — they cannot be transacted (no add-to-cart, no RFQ-quote conversion) until the seller is approved, unless the kill-switch setting `sellers.unverified_can_transact` (default `0`) is enabled. Gated server-side via `Vendor::canTransact()` / `Vehicle::ownerIsVerified()`. Rationale: lets sellers stage inventory immediately (reduces onboarding drop-off) without exposing buyers to unverified counterparties. Approval lifts the gate automatically.
- **Privileged-action audit log**: user-management and team-management actions (create/suspend/role-change/password-reset/email-verify-bypass, vendor member role-change/remove) write append-only `audit_logs` rows via `AuditLog::record()`. Never edited/deleted; corrections are new rows.

---

*Document Version: 1.0*
*Owner: Salma Technology / Salma Drive*
*Status: Approved — supersedes informal monetization notes*
