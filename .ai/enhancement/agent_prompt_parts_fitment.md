# Agent Prompt — Salma Drive: Parts Marketplace + Fitment Engine (Phase 1)

> Paste into Claude Code at the project root. Build the parts marketplace with a vehicle-fitment engine. Design-system-native, on the existing money spine. **No AI features.**

## Context

This is **Phase 1** of `master_build_roadmap.md` — Salma Drive's transactional revenue engine and the source of the **canonical vehicle taxonomy** the rest of the roadmap reuses.

**Read first:** `phase1_parts_fitment_task_order.md` (tasks PM0–PM11), `master_build_roadmap.md`, `BUSINESS_MODEL.md`, `design/DESIGN_SYSTEM.md` + `design/SCREEN_SPECS.md`, `task_execution_order.md` v2.0.

**Reuse, don't rebuild:** existing `products`/vendor model, categories, search, and the commission/wallet/Pesepay/FBS money engine. Absorb H-series #18 (vehicle⇄parts cross-sell) here.

## Core model (build it right)

A parts marketplace has **many sellers offering the same physical part**:
- **`parts`** = canonical catalog entry (specs, OEM, fitment authored once, category, guides).
- **offerings** = a vendor's sellable listing of a canonical part (price, stock, condition, warranty, fulfilment, delivery) — what goes in the cart.
- Migrate existing vendor part listings into **offerings** with a nullable `part_id` to the canonical `parts`; backfill by OEM/title or admin assignment.

## What to build (PM0–PM11)

0. **Canonical vehicle taxonomy** — make → model → generation → variant → year → engine → transmission; **unify it with vehicle listings** (one taxonomy, FK-migrate existing listing make/model strings, keep a free-text fallback + admin merge); cache the cascade.
1. **Parts catalog** — `parts`, hierarchical `part_categories` (Engine, Suspension, Brakes, Electrical, Body, Tyres, Wheels, Accessories, Performance, Service Kits), `part_oem_numbers`, alternatives, guides, warranty, secure images.
2. **Offerings & inventory** — multi-vendor offerings (price minor units, stock, condition, fulfilment, COD, delivery), participant kinds (dealer/parts store/manufacturer/importer), auditable `inventory_movements` (reserve/release/adjust, low-stock), vendor inventory UI.
3. **Fitment engine** — `part_fitments` (null dimension = applies-to-all; `is_universal` flag); the only-compatible match query (indexed); a cascading **fitment selector** component; fits-confirmed badge + mismatch warning.
4. **Search** — fitment-filtered browse + category facets + keyword (name/brand/OEM) + **basic VIN search** (decode → map to taxonomy → user confirms; no AI; deeper decode is later/data-gated); empty → RFQ "Request this part".
5. **Part detail** — specs, OEM, fitment list, alternatives, related, **seller-offers compare list**, stock/delivery/warranty, **frequently-bought-together via deterministic co-purchase counts**.
6. **Service-kit bundles** — composite sellables; component stock validated/decremented.
7. **My Garage** — saved vehicles drive fitment context everywhere (retention).
8. **Parts comparison** — side-by-side, mobile-optimized.
9. **Admin** — taxonomy + catalog CRUD, **fitment authoring with ranges**, **CSV bulk import** (validate + dry-run + error report), duplicate-merge tool, offering moderation (audited).
10. **Commerce + cross-sell** — reuse the existing cart/checkout/commission/wallet/Pesepay/FBS unchanged; **vehicle⇄parts cross-sell both ways**; cross-sell blocks log events.
11. **Validation & QA gate** — verify by execution end-to-end.

Order: **PM0 → PM1 → PM2 → PM3 → PM4 → PM5 → PM6 → PM7 → PM8 → PM9 → PM10 → PM11.** PM0 is a hard prerequisite for PM3/PM4/PM7; PM9 can parallel after PM0/PM1; PM6/PM7/PM8 after PM3/PM5. Checkpoint after each task.

## Fitment match (reference)

A part matches a selected vehicle when a fitment row has `make_id`+`model_id` equal, `:year BETWEEN year_start AND year_end`, and each of generation/variant/engine/transmission is **NULL (applies to all) or equals** the selection — OR `parts.is_universal`. Index `(make_id, model_id, year_start, year_end)` + dimension FKs.

## Rules (non-negotiable)

- **No AI** — no image-recognition search, no AI recommendations; frequently-bought-together is co-purchase counts.
- **Canonical part vs offering** split as above; fitment authored once on the canonical part.
- Reuse the existing money spine (commission snapshot, FBS/vendor tracks, COD rules, wallet, Pesepay) — don't fork it.
- Money in **integer minor units**; fees/thresholds from `platform_settings`; **no hardcoded values**.
- Authorization server-side; nav/abilities from one config source; privileged actions (catalog merges, stock adjustments, imports) **audit-logged**.
- **Inventory never goes negative**; reserve/release/adjust is auditable; bundle component stock stays consistent.
- **Design-system-native** UI (`design/` tokens + components); render every new screen in **both themes, mobile + desktop**.
- **Verify by execution as the role** — select a vehicle → only-compatible parts → compare offers → cart → checkout (commission/wallet correct) across FBS/vendor and prepaid/COD. Don't mark done on a green test alone.

## Start

Begin with **PM0 (canonical vehicle taxonomy)** and confirm it before PM1 — it underpins everything.
