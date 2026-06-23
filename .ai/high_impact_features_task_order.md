# Salma Drive — High-Impact Features Task Order (v1.0)

## Purpose

Implements the highest-impact features from the competitive gap analysis (`competitive_review_gap_analysis.md`), with these scope decisions applied:

- **Phase 4 (AI) is PAUSED** — no AI features in this cycle.
- **Removed** from the Next-20 list: **#11 (SMS OTP)** and **#19 (AI listing-description generator)**.
- **Added:** a **Vehicle-Type chooser** (Vehicle / Motorbike / Boat / Trailer) — treated as a foundation task because it changes the data model and ripples through the editor, dynamic features, and specs.
- **Listing editor**: keep the **Details / Description / Features / Images** structure and the **Save (draft) / Publish / Delete** state model, but the layout follows **`UI_STANDARDS.md` best practice — not necessarily tabbed** (a stepped wizard or well-sectioned single page is acceptable; a long continuous scroll is not).

Several tasks **extend earlier specs** rather than re-build — reuse, don't duplicate. Overlaps are noted per task.

**Companion docs (authoritative):** `competitive_review_gap_analysis.md`, `UI_STANDARDS.md`, `BUSINESS_MODEL.md`, `task_execution_order.md` v2.0, `listings_discovery_increment.md` (D-series), `production_readiness_task_order.md` (P-series).

**Carried-over principles:** root-cause before fixing; treat each finding as a class (fix every sibling surface); authorization server-side; navigation/abilities + all fees/thresholds config-driven (no hardcoding); **verify by execution as the real role** (render the page, don't trust a green test); checkpoint after each task.

---

## Scope → task map (18 features + 1 addition)

| Source item | Feature | Task |
|---|---|---|
| (new) | Vehicle-type chooser (Vehicle/Motorbike/Boat/Trailer) | **H0** |
| #6 | Listing editor: Details/Description/Features/Images + Draft/Publish/Delete | **H1** |
| #7, #9 | Dynamic features + structured specs + Zimbabwe fields (type-aware) | **H2** |
| #1, #5, #10 | Image rendering fix · watermarking · gallery UX | **H3** |
| #2, #3 | WhatsApp CTA · click-to-call · masked phone reveal (tracked) | **H4** |
| #4 | Per-listing seller analytics dashboard | **H5** |
| #8, #14 | Live inventory count · browse by body-type/make · type tabs | **H6** |
| #12, #13, #15 | Compare vehicles · price alerts/saved-search notify · recently-viewed/sponsored | **H7** |
| #16 | Public dealer/vendor storefronts + featured-dealer carousel | **H8** |
| #17 | Expiry countdown (buyer) + renew prompts (seller) | **H9** |
| #18 | Parts ⇄ vehicle cross-sell | **H10** |
| #20 | Report-listing / flag + moderation queue | **H11** |
| — | Validation & QA gate | **H12** |

---

## H0 — Vehicle-Type Foundation (Vehicle / Motorbike / Boat / Trailer)

**Why first:** type determines which fields, specs, and features apply (a boat has no "No of Doors"; a trailer differs again). Building the editor (H1) and features/specs (H2) before this means rework.

### Tasks
- [ ] Add `vehicle_type` (enum: `vehicle`, `motorbike`, `boat`, `trailer`) to the listing model; **migrate existing rows to `vehicle`** (additive, non-destructive).
- [ ] **Type chooser at listing creation** — a clear first step / modal (per `UI_STANDARDS.md`) before the editor opens (mirrors the incumbent's "Select Vehicle Type" pattern).
- [ ] Make **feature definitions and spec fields type-scoped** (each `feature_definition`/spec declares which types it applies to) so H2's forms render only relevant inputs.
- [ ] Expose `vehicle_type` as **search/browse tabs** on the landing page (Cars / Bikes / Boats / Trailers) — wired in H6.
- [ ] Type-appropriate body-type sets and icons (cars: SUV/Sedan/etc.; bikes/boats/trailers: their own sets), admin-managed.

### Exit criteria
- A seller picks a type, and the editor + specs + features adapt to it; existing listings default cleanly to `vehicle`; landing exposes the four type tabs.

---

## H1 — Listing Editor (Draft / Publish / Delete; best-practice layout)

**Item #6.** Keep the **Details / Description / Features / Images** content grouping and the explicit state model. Layout per `UI_STANDARDS.md` — stepped wizard or sectioned page with a persistent action bar; **not a long scroll, not mandatorily tabbed.**

### Tasks
- [ ] Editor with four content groups: **Details** (make, model, variant, body type, year, price, **Show Price/POA**, **Duty Paid**, location, ref code, main features), **Description**, **Features** (dynamic, type-aware — H2), **Images** (H3).
- [ ] **State model**: `draft` (Save), `published` (Publish), and **Delete**; only published listings appear publicly. Draft persists partial input.
- [ ] Per-step / per-section validation; preserve data on error; clear progress indication (reuse the apply-vendor wizard pattern from R9).
- [ ] **Type-aware rendering** from H0.
- [ ] Available for **vendor and private-seller** flows; respects pending/unverified gating (R4) — drafts allowed while pending per the confirmed transaction rule.

### Exit criteria
- A seller can save a draft, return to it, publish it, and delete it; published goes live, draft does not; layout conforms to `UI_STANDARDS.md`.

---

## H2 — Dynamic Features + Structured Specs (type-aware)

**Items #7, #9. Extends `listings_discovery_increment.md` D4 — reuse it.**

### Tasks
- [ ] Confirm/extend D4 `feature_definitions` (+ `applies_to_types` from H0) and `*_feature_values`; admin CRUD; seller form renders inputs by feature type (boolean toggle / number+unit / enum / text).
- [ ] **Structured specs** (type-aware) on the detail page: e.g. mileage, engine capacity, transmission, drive type, **steering (LHD/RHD)**, seats, doors, dimensions, condition — grouped and scannable.
- [ ] **Zimbabwe-specific fields**: `show_price`/POA, `duty_paid`, `is_recent_import` (badge), `ref_code`, USD pricing; surface as badges/labels on cards and detail.
- [ ] Filterable features feed H6/D3 facets.

### Exit criteria
- Admin can add a feature without a deploy; it appears (type-appropriately) in the editor, on the detail specs, and as a filter where marked filterable.

---

## H3 — Images: Fix Rendering · Gallery UX · Watermarking

**Items #1, #5, #10. Extends D1 (fix) and P2 (secure pipeline) — reuse.**

### Tasks
- [ ] **Root-cause the placeholder bug** (D1): missing primary image, relationship not eager-loaded (N+1), `storage:link`/disk/CDN URL misconfig, missing processed variant, or unpersisted older uploads. Fix the cause; apply to **every** thumbnail surface (landing, search, detail, dashboards, related).
- [ ] **Gallery UX**: photo-count badge, thumbnail strip, fullscreen/lightbox, **download all images**, **WhatsApp + social share**.
- [ ] **Watermarking**: auto-apply platform mark (+ dealer/ref where applicable) on processed images; keep an unwatermarked original private.
- [ ] **Secure pipeline (P2)**: content-sniff validation, allow-list types, size/count limits, **re-encode + strip metadata**, **randomised filenames**, off-webroot storage, CDN delivery, rate-limited uploads.

### Exit criteria
- Real images render everywhere; gallery supports count/thumbnails/fullscreen/share/download; outputs are watermarked; malicious/oversized/wrong-type uploads are rejected.

---

## H4 — Contact & Lead Capture: WhatsApp · Call · Masked Phone Reveal

**Items #2, #3. Feeds D6 lead tracking.**

### Tasks
- [ ] **WhatsApp chat CTA** (primary, prominent) on every listing — deep link / click-to-chat with a prefilled listing reference.
- [ ] **Click-to-call** action.
- [ ] **Masked phone number** with a **"Show Number"** reveal (privacy + intent signal).
- [ ] Each action writes a **tracked lead/event** (type: `whatsapp_click`, `call_click`, `phone_reveal`, `enquiry`) tied to listing + seller + buyer (nullable for guests) — extends the D6 `leads`/events model.
- [ ] **Rate-limit and log** phone reveals (abuse control); capture guest contacts where provided.
- [ ] Mobile: contact actions are thumb-reachable / sticky.

### Exit criteria
- Buyers can WhatsApp, call, or reveal the number; each creates a tracked lead (including guests); reveals are rate-limited and logged.

---

## H5 — Per-Listing Seller Analytics Dashboard ★ the retention/monetisation engine

**Item #4. The single highest-value car-side feature. Depends on H4 events + D6.**

### Tasks
- [ ] **Event ingestion**: `view`, `detail_view`, `phone_reveal`, `call_click`, `whatsapp_click`, `email_enquiry` — debounced client beacons; **dedupe per session/IP and bot-filter** so counts can't be gamed (analytics integrity is the product's core value).
- [ ] **Pre-aggregate** into `listing_stats_daily` (never COUNT raw events live on dashboards).
- [ ] **Seller dashboard**: per-listing and total metrics with **period deltas** (this week vs last) — Total Listings, Active, Featured, Views, Detailed Views, Enquiries, Phone Reveals, Calls, WhatsApp clicks.
- [ ] Scope strictly to the seller's own listings (admin sees site-wide); reuse the config-driven nav/role model.
- [ ] Per-listing "View Stats" drill-down.

### Exit criteria
- A seller sees real, deduped engagement metrics per listing with deltas; figures match verifiable aggregates; dashboards don't run unbounded queries.

---

## H6 — Discovery: Live Count · Browse by Body-Type/Make · Type Tabs

**Items #8, #14. Extends D2/D3 search.**

### Tasks
- [ ] **Live inventory count in the search CTA** (e.g. "Search 1,240 vehicles") — accurate to active+published, cached.
- [ ] **Browse by body type** (icon grid) and **by make** (logo grid) entry points on landing → pre-filtered results.
- [ ] **Vehicle-type tabs** (Cars / Bikes / Boats / Trailers) from H0 wired into search/filter; type switches available facets.
- [ ] Filters compose with type + search + sort; URL-encoded state; mobile filter drawer (not long scroll).

### Exit criteria
- Landing offers type tabs + body-type/make browse + a search button showing a live, accurate count; filters adapt to the chosen type.

---

## H7 — Buyer Engagement & Retention

**Items #12, #13, #15.**

### Tasks
- [ ] **Compare vehicles** side-by-side (specs/features grid; add-to-compare from cards/detail).
- [ ] **Price alerts** ("Track Price") on a listing and **saved-search notifications** ("notify me of new matches / price drops") — via existing notification channels (Phase 18).
- [ ] **Recently viewed** row (per user/session) and **Sponsored** row (paid placement) on landing — sponsored ties to promotion revenue (`BUSINESS_MODEL.md §8`).

### Exit criteria
- Buyers can compare, set price/search alerts and receive them, and see recently-viewed + sponsored rows.

---

## H8 — Dealer / Vendor Storefronts + Featured-Dealer Carousel

**Item #16.**

### Tasks
- [ ] Public **vendor storefront** page: slug, banner, logo, about, contact, and that vendor's active listings (reuses vendor profile + verified/trusted badge).
- [ ] **"Find a Dealer"** directory/listing.
- [ ] **Featured-dealer carousel** on the homepage — a paid placement slot (config-driven; revenue per `BUSINESS_MODEL.md §8`).
- [ ] Storefront views write `view` events (feeds H5 where relevant).

### Exit criteria
- Each vendor has a shareable public storefront; buyers can browse dealers; featured slots are purchasable and surfaced.

---

## H9 — Listing Lifecycle Surfacing: Expiry Countdown + Renew

**Item #17. Extends D5 — reuse the expiry/renewal engine.**

### Tasks
- [ ] **Buyer-facing countdown** on listings/cards ("Expiring in 8 days") for urgency.
- [ ] **Seller renew prompts** + pre-expiry reminders (reuse D5 jobs/notifications).
- [ ] Expired listings drop from public view but remain renewable from the dashboard.

### Exit criteria
- Buyers see urgency countdowns; sellers are warned and can renew in one action; expiry/renew uses the D5 engine, not a parallel one.

---

## H10 — Parts ⇄ Vehicle Cross-Sell (your unique wedge)

**Item #18.**

### Tasks
- [ ] Compatibility model linking parts to vehicles (by make/model/year, or explicit tags).
- [ ] On a **vehicle detail** page, surface **compatible/related parts**; on a **part detail** page, surface compatible vehicles.
- [ ] Cross-sell blocks write events (feeds H5/D6) and can be sponsored later.

### Exit criteria
- Buyers move between a vehicle and its compatible parts (and back) within the marketplace.

---

## H11 — Trust & Safety: Report-Listing + Moderation Queue

**Item #20.**

### Tasks
- [ ] Buyer **report/flag** action on listings (reasons: scam, wrong info, sold, duplicate, offensive).
- [ ] **Admin moderation queue**: review, action (hide/remove/warn/dismiss), all **audit-logged** (reuse R6 `audit_logs`).
- [ ] Auto-flag heuristics where cheap (duplicate photos/text, impossible price) — rules only, **no AI this cycle**.

### Exit criteria
- Buyers can report; admins triage from a queue; actions are audited.

---

## H12 — Validation & QA Gate

### Tasks
- [ ] **Verify by execution** as each role: seller creates a typed listing (each of the 4 types) → draft → publish → appears with images, specs, features, contact CTAs; buyer searches/filters/compares/alerts; analytics increment correctly and are deduped.
- [ ] View-level + key E2E tests for the new surfaces (assert rendered views, not just routes).
- [ ] Confirm all new fees/placements/durations read from `platform_settings`; no hardcoded values.
- [ ] Confirm analytics integrity (replayed/bot events don't inflate counts).
- [ ] Reconcile any rule changes back into `BUSINESS_MODEL.md` / `task_execution_order.md`.

### Exit criteria
- All 18 features (+ vehicle-type) demonstrably work per role across the four listing types; tests cover rendered views; analytics are trustworthy.

---

## Suggested order & parallelism

Sequence: **H0 → H1 → H2 → H3 → H4 → H5 → H6 → H7 → H8 → H9 → H10 → H11 → H12.**

- **H0 is a hard prerequisite** for H1/H2/H6.
- After H2: **H3 can run in parallel.**
- **H5 depends on H4** (events).
- **H7/H8/H9/H10/H11 can parallelise** once H1–H6 land.

---

*Document Version: 1.0*
*Scope: highest-impact features minus #11 & #19, Phase-4 AI paused, vehicle-type chooser added.*
*Companion: competitive_review_gap_analysis.md, UI_STANDARDS.md, BUSINESS_MODEL.md, task_execution_order.md v2.0, listings_discovery_increment.md, production_readiness_task_order.md*
*Status: Ready for execution*
