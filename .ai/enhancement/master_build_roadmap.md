# Salma Drive — Master Build Roadmap (v1.0)

**Premise:** core is stable and ready for new features; no hard deadline; goal is "build it right"; all seven new features are in scope. This document is the **single sequenced source of truth** that consolidates the design system, the existing high-impact (H-series) work, and the seven new features — **de-duplicated**, dependency-ordered, with partnership-gating flagged.

**Working method (anti-sprawl):** do **not** pre-write all seven feature specs. Build in **thin vertical slices**, generating each feature's detailed task order + agent prompt **just-in-time** when it reaches the front of the queue. One feature shipped beats seven specced.

**Companion docs:** `design/` (DESIGN_SYSTEM, SCREEN_SPECS, tokens), `high_impact_features_task_order.md` (H-series), `BUSINESS_MODEL.md`, `production_readiness_task_order.md`, `competitive_review_gap_analysis.md`.

---

## Overlap reconciliation (build once, not twice)

| New feature (this prompt) | Already specced as | Resolution |
|---|---|---|
| F5 Comparison tool | H-series #12 (compare vehicles) | **Merge** → one comparison build (up to 5, mobile, printable). |
| F3 Saved searches & alerts | H-series #13 + customer portal | **Merge** → one notification/alerts engine. |
| F2 Verification badges | R4 verified/unverified + "trusted tier" | **Extend** the existing system into full tiers. |
| F7 Parts marketplace | H-series #18 (parts⇄vehicle cross-sell) | Cross-sell is a **slice of** the parts marketplace; build under F7. |
| F1/F4/F6 (history/inspection/trade-in) | new | partnership/ops-gated — see Phase 4–5. |

---

## Shared foundations (build once, reused everywhere)

- **Canonical vehicle taxonomy** — make → model → generation → variant → year-range → engine → transmission. Powers fitment (F7), history (F1), trade-in (F6), comparison (F5), and improves listings. **Build it in Phase 1.**
- **Notification architecture** — events → channels (push/email/in-app) → preferences. Built in Phase 3, reused by price drops, dealer promos, report-ready alerts, inspection updates.
- **Trust/verification & reputation** — Phase 2; consumed by parts seller trust, history-report credibility, trade-in dealer eligibility.
- **Config-driven everything** (`platform_settings`), **audit-logged** privileged actions, **mobile-first**, **design-system-native** UI — apply across all phases.

---

## Phase 0 — Design System Foundation *(do first; low risk; compounding)*

Install tokens + theming + base components (`design/`), then build every later phase design-system-native; adopt components on any screen you touch. Not cosmetic — it's the foundation that makes all seven features consistent and faster.
**Exit:** tokens live, theme toggle (system-aware) works, base + composite components exist.

---

## Phase 1 — Parts Marketplace + Fitment Engine (F7) ★ core differentiator

Your transactional revenue engine and the source of the shared vehicle taxonomy. Pure engineering you control.

- **Canonical vehicle taxonomy** (the shared foundation above).
- **Fitment engine**: select Make → Model → Year → Variant → Engine → only-compatible parts shown; each part linked to make/model/generation/variant/year-range/engine/transmission.
- **Parts catalog**: categories (engine, suspension, brakes, electrical, body, tyres, wheels, accessories, performance, service kits); OEM numbers, alternatives, related products, install guides, stock, delivery estimates, warranty.
- **Search**: fitment-filtered + VIN-based (basic) + frequently-bought-together + service-kit bundles + parts comparison.
- **Participants**: dealers, parts stores, manufacturers, importers; **inventory management** model.
- **Absorbs** H-series #18 (vehicle⇄parts cross-sell).
- **Revenue**: transactional commission + delivery + FBS (already in `BUSINESS_MODEL.md`). *(Defer image-recognition / AI recommendation — your AI phase is paused.)*

**Exit:** a buyer narrows to their exact vehicle and sees only compatible, purchasable parts.

---

## Phase 2 — Dealer Verification Badges & Reputation (F2) ★ trust layer

Lifts conversion on everything after it. Extends the existing verified/unverified system.

- **Tiers**: Verified Dealer, Premium Dealer, Manufacturer-Authorized, Top-Rated, Trusted Seller.
- **Verification workflow**: company registration, tax compliance, physical location, identity, banking — staged approval, audited.
- **Reputation scoring** (ratings, response time, lead conversion, disputes) feeding Top-Rated.
- **Fraud controls**: document checks, duplicate/stolen-photo flags (rule-based), badge revocation.
- **Badge management** admin UI; badges surface on cards, detail, storefronts.

**Exit:** dealers progress through verification tiers; badges render with trust signals; admins manage and revoke.

---

## Phase 3 — Engagement Engine: Saved Searches & Smart Alerts (F3) + Comparison (F5)

- **Notification architecture** (the shared foundation): event processing → push/email/in-app → user preferences.
- **Saved searches** (make/model/price/year/mileage/dealer-type/location) with **alerts**: new listings, price drops, similar vehicles, dealer promotions. *(Merges H-series #13.)*
- **Comparison tool** (merges H-series #12): up to **5** vehicles side-by-side — specs, features, pricing, fuel economy, ownership cost; **mobile-optimized**; **printable** report.

**Exit:** users save searches and receive timely alerts; can compare up to five vehicles on any device and print.

---

## Phase 4 — Vehicle History Reports (F1) ⚠ data/partnership-gated

Build the module; bootstrap from data you own; add external sources as partnerships land.

- **Module**: report schema, **premium purchase flow** (Pesepay), preview vs full report, admin management, revenue model.
- **Sources, staged by availability**: start with **import records + your own listing/ownership data + dealer-supplied service history**; add **registration, police-clearance, accident/insurance, roadworthiness, odometer** as data partnerships are secured.
- Surface a **"history available" badge** + purchase CTA on the detail page.

**Reality check:** the engineering is the easy 30%; the data partnerships are the hard 70%. Start partnership conversations in parallel; don't block the build on them.

**Exit:** buyers can preview and purchase a history report assembled from whatever sources are live, with more added over time.

---

## Phase 5 — Services Marketplaces: Trade-In (F6) + Inspection Booking (F4) ⚠ ops-heavy, manual-first

Two-sided marketplaces — run **manual-first** (like your concierge model), automate when volume justifies.

- **Trade-In Valuation (F6)**: submit vehicle + photos → **estimated valuation bootstrapped from your own listing price data** (the taxonomy from Phase 1 makes this possible) → **dealer bidding** workflow → accept offer. Dealer portal + valuation engine + DB model.
- **Inspection Booking (F4)**: buyers schedule + pay online → inspector/mechanic portal → standardized **inspection report template** → rate inspectors. Start with a small vetted inspector panel and an admin-run queue; automate scheduling later.

**Exit:** a buyer gets trade-in offers and can book a paid inspection with a returned report — even if ops are partly manual at first.

---

## Where production hardening fits

Since there's no deadline, fold the relevant **`production_readiness_task_order.md`** items (security headers, idempotent webhooks + reconciliation, performance/N+1, backups/PITR for money tables, observability) in **before public launch** — ideally hardening each phase as it ships rather than one big pass at the end.

---

## Revenue sequencing (so effort tracks income)

- **Now / Phase 1–2**: parts commission + delivery + FBS; promotion/featured (cars); verification could carry a small fee.
- **Phase 3**: engagement drives retention → more leads → more promotion spend.
- **Phase 4**: history-report sales (per-report).
- **Phase 5**: inspection fees + trade-in lead value.
Keep launch usage-based; layer paid tiers as liquidity grows (`BUSINESS_MODEL.md §10`).

---

## Recommended next action

Generate the **Phase 1 (Parts + Fitment Engine)** detailed task order + agent prompt, and build that slice end-to-end before speccing Phase 2. Repeat just-in-time per phase. This keeps you shipping, not accumulating plans.

---

*Version 1.0 · Single source of truth for sequencing · Companion: design/, high_impact_features_task_order.md, BUSINESS_MODEL.md, production_readiness_task_order.md · Status: Approved sequence*
