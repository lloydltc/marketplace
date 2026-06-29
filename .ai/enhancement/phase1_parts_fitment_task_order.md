# Salma Drive — Phase 1: Parts Marketplace + Fitment Engine (v1.0)

**Roadmap position:** Phase 1 of `master_build_roadmap.md`. Your transactional revenue engine and the source of the **canonical vehicle taxonomy** every later phase reuses (fitment, history, trade-in, comparison, listings).

**Build design-system-native** (`design/`), config-driven (`platform_settings`), money as integer minor units, audit-logged, mobile-first. **AI is paused** — no image-recognition search, no AI recommendation engine; "frequently bought together" uses deterministic co-purchase counts.

**Reuses, don't rebuild:** the existing `products`/vendor model, categories (Phase 4), search (Phase 8), the commission/wallet/Pesepay/FBS engine (`BUSINESS_MODEL.md`), verification (later Phase 2), and absorbs H-series #18 (vehicle⇄parts cross-sell).

**Companion docs:** `master_build_roadmap.md`, `BUSINESS_MODEL.md`, `design/DESIGN_SYSTEM.md` + `SCREEN_SPECS.md`, `task_execution_order.md` v2.0.

---

## Core model decision: canonical part vs vendor offering

A parts marketplace has **many sellers offering the same physical part**. So:

- **`parts`** = *canonical catalog entry* — one per real part (specs, OEM numbers, fitment, category, guides). Fitment authored **once**.
- **offerings** = *a vendor's sellable listing* of a canonical part (price, stock, condition, warranty, fulfilment, delivery). This is what goes in the cart.

**Migration from existing `products`:** treat current vendor part listings as **offerings**; add a nullable `part_id` linking each to a canonical `parts` row; backfill by OEM/title match or admin assignment. Universal/accessory parts need no fitment.

*(Lighter alternative if you ever want to start smaller: put fitment directly on the product and dedupe later — but the canonical/offering split is the "build it right" path and is assumed below.)*

---

## PM0 — Canonical Vehicle Taxonomy ★ shared foundation

The make→model→generation→variant→year→engine→transmission tree, shared with vehicle listings.

### Schema (key columns)
- `vehicle_makes` (id, name, slug, logo, vehicle_type, active, sort)
- `vehicle_models` (id, make_id, name, slug, active)
- `vehicle_generations` (id, model_id, name, year_start, year_end)  *(e.g. Hilux "Revo" 2015–2024)*
- `vehicle_variants` (id, model_id, generation_id?, name, body_type)  *(e.g. "2.8 GD-6")*
- `vehicle_engines` (id, code, displacement, fuel_type)  *(e.g. "2.8 GD-6 Diesel")*
- `vehicle_transmissions` (id, type)  *(manual/automatic/CVT/DCT)*

### Tasks
- [ ] Create taxonomy tables + admin CRUD (PM9); seed common Zimbabwe-market makes/models (Toyota, Nissan, Honda, Mazda, Mitsubishi, Isuzu, Mercedes, VW…).
- [ ] **Unify with vehicle listings**: vehicle listings reference the same makes/models (one taxonomy, not two). Migrate existing listing make/model strings onto FKs (additive; keep a free-text fallback for unknowns + an admin "merge to canonical" tool).
- [ ] Cache the cascade (make→model→…) in Redis for fast dropdowns.

### Exit
- One canonical taxonomy powers both parts fitment and vehicle listings; admin can extend it without a deploy.

---

## PM1 — Parts Catalog & Categories (canonical)

### Schema
- `parts` (id, slug, name, brand, category_id, primary_oem, description, warranty_months, warranty_terms, is_universal, status)
- `part_categories` (id, parent_id, name, slug, sort) — hierarchical
- `part_oem_numbers` (id, part_id, number, type[oem|aftermarket|cross_ref], brand)
- `part_alternatives` (id, part_id, alternative_part_id, relation[oem_equivalent|substitute|upgrade])
- `part_guides` (id, part_id, title, type[doc|video], url|content)
- `part_media` (id, part_id, image, sort, is_primary)

### Tasks
- [ ] Create catalog tables; seed categories: Engine, Suspension, Brakes, Electrical, Body, Tyres, Wheels, Accessories, Performance, Service Kits.
- [ ] OEM numbers + cross-references (drives OEM search and alternatives).
- [ ] Alternatives/related links (manual + derivable via shared OEM numbers).
- [ ] Installation guides + warranty fields.
- [ ] Secure image pipeline reused from prior work (content-sniff, re-encode, randomised names, watermark optional).

### Exit
- A canonical part carries category, brand, OEM numbers, alternatives, guides, warranty, images.

---

## PM2 — Vendor Offerings & Inventory

### Schema
- `part_offerings` (id, part_id, vendor_id, sku, price_minor, currency, condition[new|used|refurb], stock_qty, low_stock_threshold, warranty_months, fulfilment_type[fbs|vendor], cod_allowed, delivery_estimate, status)
- `inventory_movements` (id, offering_id, type[restock|sale|reserve|release|adjustment], qty, ref, created_by) — auditable stock trail

### Tasks
- [ ] Map existing vendor product listings → offerings (link to canonical `parts` via `part_id`).
- [ ] Participant kinds: dealer / parts store / manufacturer / importer (reuse vendor model + a `vendor_kind`); verification handled in Phase 2.
- [ ] **Inventory model**: stock per offering, reserve-on-order, release-on-cancel, low-stock flag/alert, manual adjustments (audited via `inventory_movements`).
- [ ] Vendor **parts inventory UI**: CRUD offerings, stock, condition, fulfilment; price in minor units; bulk edit.

### Exit
- Multiple vendors can offer the same canonical part with independent price/stock/condition; stock changes are auditable.

---

## PM3 — Fitment Engine ★ the differentiator

### Schema
- `part_fitments` (id, part_id, make_id, model_id, generation_id?, variant_id?, engine_id?, transmission_id?, year_start, year_end, notes)
- A part has many fitment rows. **Null in a dimension = applies to all** values of that dimension. `parts.is_universal = true` ⇒ no fitment rows needed.

### Fitment query (only-compatible logic)
Given a selected vehicle (make, model, year, optional variant/engine/transmission), a part matches when it has a fitment row where:
```
make_id = :make AND model_id = :model
AND :year BETWEEN year_start AND year_end
AND (generation_id IS NULL OR generation_id = :generation)
AND (variant_id     IS NULL OR variant_id     = :variant)
AND (engine_id      IS NULL OR engine_id      = :engine)
AND (transmission_id IS NULL OR transmission_id = :transmission)
```
…OR `parts.is_universal = true`. Index `(make_id, model_id, year_start, year_end)` and the dimension FKs.

### Tasks
- [ ] Build the compatibility model + the fitment match query (as a reusable scope/service).
- [ ] **Fitment selector component** (design-system-native, cascading): Make → Model → Year → Variant → Engine → Transmission, each step narrowing options (server-driven, Redis-cached, Alpine combobox).
- [ ] "Fits your vehicle" confirmation badge on parts once a vehicle is selected; mismatch warning if a user opens a part that doesn't fit their selected vehicle.
- [ ] Fitment authoring UI for admins/large sellers (PM9), incl. ranges.

### Exit
- A buyer narrows to their exact vehicle and the catalog shows **only compatible parts**, with a fits-confirmed badge.

---

## PM4 — Parts Search & Discovery

### Tasks
- [ ] **Fitment-filtered browse** (primary path) + category browse with facets (brand, price, condition, fulfilment, in-stock).
- [ ] **Keyword search** across part name, brand, OEM numbers (Postgres full-text or existing Phase 8 search); OEM-number exact match prioritised.
- [ ] **VIN-based search (basic, no AI)**: parse/decode the VIN to vehicle attributes, map to taxonomy, apply fitment filter; **let the user confirm/adjust** the decoded vehicle. *Flag: full VIN decode may need a data source — MVP decodes what it can and confirms with the user; deeper decoding is a later, data-gated enhancement.*
- [ ] Sort (price, relevance, popularity); pagination; result count; indexes on searchable/filterable columns; cache hot facets.
- [ ] Empty-results → RFQ "Request this part" entry point (reuse existing RFQ).

### Exit
- Buyers find parts by vehicle, category, keyword, OEM, or VIN; results are fast, paginated, and fitment-accurate.

---

## PM5 — Part Detail Page

### Tasks (design-system-native)
- [ ] Canonical specs, brand, category, **OEM numbers**, warranty, installation guides, image gallery.
- [ ] **"Fits these vehicles"** fitment list (and fits-your-vehicle confirmation when a vehicle is selected).
- [ ] **Alternatives** + **related products**.
- [ ] **Seller offers list** — all vendor offerings for this part, compare price/condition/stock/delivery/fulfilment/verified-seller; "Add to cart" per offering (lowest-price highlighted).
- [ ] **Frequently bought together** — deterministic co-purchase counts (no AI), as a bundle add.
- [ ] Stock availability + delivery estimate per offering; warranty surfaced.

### Exit
- A part page shows what it is, what it fits, who sells it (compare offers), and what's bought with it — and converts to cart.

---

## PM6 — Service Kit Bundles

### Schema
- `part_bundles` (id, vendor_id?, name, slug, description, price_minor?, status, is_service_kit)
- `part_bundle_items` (id, bundle_id, part_id|offering_id, qty)

### Tasks
- [ ] Bundles as sellable composites (e.g. service kit = oil + oil filter + air filter); price = sum of offerings or a set bundle price.
- [ ] Stock: validate component availability at checkout; decrement components on sale.
- [ ] Surface bundles on relevant part/vehicle pages ("Service kit for your Hilux").

### Exit
- Buyers can purchase a curated service kit in one action; component stock stays consistent.

---

## PM7 — My Garage (saved vehicles) — retention

### Schema
- `user_garage_vehicles` (id, user_id, make_id, model_id, year, variant_id?, engine_id?, transmission_id?, nickname, is_default)

### Tasks
- [ ] Let buyers save vehicles; quick-switch active vehicle drives fitment filtering everywhere ("Shop parts for [my Hilux]").
- [ ] Pre-fill fitment selector from garage; one-tap fits-confirmed browsing.
- [ ] Optional: tie alerts (Phase 3) to garage vehicles ("new parts for your Hilux").

### Exit
- Returning buyers shop in fitment context instantly; strong retention hook.

---

## PM8 — Parts Comparison

### Tasks
- [ ] Side-by-side comparison of parts (specs, OEM, price-from across sellers, warranty, condition). Reuse the comparison pattern (shared with Phase 3 vehicle comparison where practical).
- [ ] Mobile-optimized; up to 4–5 parts; remove/add.

### Exit
- Buyers compare candidate parts before buying.

---

## PM9 — Admin: Taxonomy, Catalog, Bulk Import, Moderation

### Tasks
- [ ] Admin CRUD for the full taxonomy (PM0) and canonical catalog (PM1) incl. fitment authoring with ranges.
- [ ] **Bulk import (CSV)** of parts + OEM numbers + fitment + offerings — essential for parts stores with large catalogs; validation + dry-run + error report.
- [ ] **Merge tool** for duplicate canonical parts and unify offerings.
- [ ] Moderate vendor offerings; flag suspicious price/stock; all privileged actions audit-logged.

### Exit
- Admins manage the catalog at scale; large sellers onboard via bulk import; duplicates are mergeable.

---

## PM10 — Commerce Integration + Cross-Sell

### Tasks
- [ ] Cart/checkout/commission/wallet/Pesepay/FBS — **reuse the existing engine** (`BUSINESS_MODEL.md`); offerings and bundles flow through it unchanged (commission snapshot, fulfilment tracks, COD rules, payouts).
- [ ] **Vehicle ⇄ parts cross-sell** (absorbs H-series #18): on a vehicle detail page show compatible parts (via fitment + the vehicle's taxonomy); on a part page link to compatible vehicles.
- [ ] Cross-sell + offers blocks write analytics/lead events (feed the seller analytics dashboard).

### Exit
- Buying a part uses the existing money spine; vehicles and parts cross-sell both ways.

---

## PM11 — Validation & QA Gate

### Tasks
- [ ] **Verify by execution:** select a vehicle via the fitment selector → see only compatible parts → open a part → compare seller offers → add to cart → checkout (commission/wallet correct) across FBS and vendor-fulfilled, prepaid and COD.
- [ ] Fitment correctness tests: null-dimension (applies-to-all), year-range edges, universal parts, mismatch warnings.
- [ ] Bulk-import a sample catalog; verify fitment authored at scale resolves correctly.
- [ ] Inventory integrity: reserve/release/adjust never goes negative; bundle component stock consistent.
- [ ] All fees/thresholds from `platform_settings`; no hardcoded values; privileged actions audited.
- [ ] Render all new screens in both themes, mobile + desktop.

### Exit
- The fitment-accurate, multi-seller parts marketplace works end-to-end on the existing money spine, verified per role.

---

## Suggested order & parallelism

`PM0 → PM1 → PM2 → PM3 → PM4 → PM5 → PM6 → PM7 → PM8 → PM9 → PM10 → PM11`

- PM0 is a hard prerequisite for PM3/PM4/PM7.
- PM9 (admin/import) can develop in parallel once PM0/PM1 exist.
- PM6/PM7/PM8 can parallelise after PM3/PM5.
- PM10 reuses existing commerce — light if the money spine is stable.

## Principles (carried over)

Root-cause before fixing; treat findings as classes; authorization server-side; nav/abilities + fees from one config source; **AI paused**; verify by execution; checkpoint after each task.

---

*Version 1.0 · Phase 1 of master_build_roadmap.md · Companion: BUSINESS_MODEL.md, design/, task_execution_order.md v2.0 · Status: **COMPLETE** (PM0–PM11), full suite 612 green*

---

## Completion log (PM0–PM11)

All tasks delivered, committed, and verified. **Key model decision honoured:** the
existing `products` table is the vendor **offering** (nullable `part_id` → canonical
`parts`); money stays **decimal** (existing spine reused, not forked to minor units);
fitment authored once on the canonical part with H10 reconciled (its product_fitments
kept as a fallback); categories reused.

| Task | Delivered |
|------|-----------|
| PM0 | Canonical taxonomy (generations/variants/engines/transmissions, additive) + TaxonomyService cache |
| PM1 | Canonical parts catalog (parts, OEM, alternatives, guides, media) reusing `categories` |
| PM2 | Offerings on `products` + auditable `inventory_movements` (never negative) |
| PM3 | Fitment engine (`part_fitments`, null-dimension = applies-to-all, year ranges) + cascading selector + session context |
| PM4 | Catalog browse (fitment-filtered, facets, keyword/OEM) + deterministic VIN decode + empty→RFQ |
| PM5 | Part detail: offers compare, alternatives, FBT (co-purchase counts), warranty, guides |
| PM6 | Service-kit bundles · PM7 My Garage · PM8 parts comparison |
| PM9 | Admin catalog CRUD + fitment authoring + CSV import (dry-run/errors) + duplicate merge, all audited |
| PM10 | Offerings ride the existing money spine; reserve-on-order/release-on-cancel (guarded to part offerings); part⇄vehicle cross-sell |
| PM11 | End-to-end QA gate + invariants; full regression 612 green |

*Status: Ready for execution → DELIVERED.*
