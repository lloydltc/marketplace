# Salma Drive — Listings & Discovery Increment (v1.0)

## Purpose

A focused increment covering six findings from continued testing. They split into two themes:

- **Discovery** (D1–D3): images must actually render, and the landing page must let buyers *search and filter* both products and vehicles.
- **Vehicle lead-gen lifecycle** (D4–D6): dynamic vehicle specs, listing expiry/renewal, and capturing every lead/contact. Because vehicles are **lead-generation, not online sale**, these three are where the car side of the business is measured and monetised — treat them as first-class, not nice-to-haves.

This slots into the production track. Overlaps are called out per task. As always: **investigate root cause before fixing, and treat each finding as a class** — fix the landing image and you almost certainly fix the same bug on detail, dashboard, and search views.

**Companion docs:** `production_readiness_task_order.md`, `UI_STANDARDS.md`, `BUSINESS_MODEL.md` (§8 listing promotion, §10 rollout), `task_execution_order.md` v2.0 (Phase 6 vehicles, Phase 7 media, Phase 8 search).

---

## Findings → tasks

| # | Finding | Task |
|---|---------|------|
| 1 | Vehicle/product images show a **placeholder icon** on homepage/landing | D1 |
| 2 | Landing **search** must cover **both products and vehicles** | D2 |
| 3 | Landing must offer **vehicle filtering** | D3 |
| 4 | Vehicle listings need **dynamic "other features"** (e.g. Parking Sensors = yes, Doors = 5) | D4 |
| 5 | Listings should **expire** (e.g. 2 months) with a **renew** option | D5 |
| 6 | **Track every lead / contact** generated from the website | D6 |

---

## Open decisions (confirm before the relevant task; sensible defaults given)

- **D5 — renewal: free or paid?** Default: **free renewal at launch**, expiry duration config-driven (`platform_settings`, default 60 days). Renewal is a natural future monetisation point (paid renew, or upsell featured/bump at renewal) — wire the hook but keep it free now, consistent with `BUSINESS_MODEL.md §10` ("don't charge too early"). Flip via config later.
- **D5 — expiry scope.** Default: **expiry ON for vehicle listings** (lead-gen, naturally time-bound); **OFF (or long) for parts/products** (stock-based). Make it per-listing-type config so it can change.
- **D4 — feature storage.** Default: **structured attribute definitions + values** (admin-managed, filterable), not free-form JSON, because D3 needs to filter on them. Rationale in D4.

---

## D1 — Fix image rendering on landing/catalogue (root-cause first)

**Finding 1.** Placeholder icons mean the view's fallback is firing — the image URL is null, empty, or 404ing. Don't patch the symptom; find which.

### Investigate (likely causes — confirm which)
- [ ] **No primary image selected** → query returns no image and the view falls back. (Ensure every listing has a primary; default to first image.)
- [ ] **Relationship not eager-loaded** on the landing query → image is null in the view (also an N+1 risk). Eager-load `images`/`primaryImage`.
- [ ] **Storage URL/symlink misconfigured** (`storage:link` missing, wrong `APP_URL`/disk, or object-storage base URL) → URL resolves to a 404.
- [ ] **Processing variant missing** → the landing view references a thumbnail/WebP variant the job never generated, so the variant path 404s while the original exists.
- [ ] **Upload didn't persist** for older listings (pre-P2 wiring) → backfill/migration needed.

### Fix
- [ ] Resolve the actual root cause(s) above; ensure the landing query selects the correct primary-image URL with eager loading.
- [ ] Apply the same fix to **every surface** showing listing thumbnails: catalogue/search results, detail/gallery, seller dashboard "my listings", admin listings, related items.
- [ ] Provide a clean branded fallback only when a listing genuinely has no image (not as a mask for a wiring bug).
- [ ] Add responsive `srcset` + lazy-load while here (overlaps production P5).

### Exit criteria
- Real listing images render on landing and everywhere else; placeholder appears only for genuinely image-less listings; no N+1 on the landing image query.

---

## D2 — Unified Landing Search (products + vehicles)

**Finding 2.** Extends Phase 8 search to the landing page across both entity types.

### Tasks
- [ ] Prominent landing search bar; one query searches **both** products and vehicles (clear typed results, or a tabbed/sectioned result set: Vehicles | Parts).
- [ ] Search relevant fields per type (vehicles: make/model/year/title/description; products: title/brand/SKU/description) plus the dynamic vehicle features from D4 where sensible.
- [ ] Respect the existing search ranking rules (FBS boost for parts, featured priority for vehicles — `BUSINESS_MODEL.md §3/§8`).
- [ ] Empty-results state offers the **RFQ "Request it"** entry point (existing Phase 8/15 hook).
- [ ] Autocomplete/suggestions if cheap; otherwise defer.

### Exit criteria
- A single landing search returns both vehicles and parts, ranked correctly, with a useful empty state.

---

## D3 — Vehicle Filtering on Landing

**Finding 3.** Faceted filters for vehicles. Depends on D4 for feature-based facets.

### Tasks
- [ ] Filter panel for vehicles: make, model, year range, price range, body type, transmission, fuel, condition, location — plus **dynamic feature facets** from D4 (e.g. doors, parking sensors).
- [ ] Filters compose with search (D2) and with sort (price, recency, featured-first).
- [ ] URL-encoded filter state (shareable/back-button safe); paginated results.
- [ ] Mobile: filters in a drawer/sheet, not a long inline scroll (per `UI_STANDARDS.md`).
- [ ] Index the columns/attributes used by filters (overlaps production P5).

### Exit criteria
- Buyers can filter vehicles by standard attributes and dynamic features; filter state is shareable and mobile-friendly; queries are indexed.

---

## D4 — Dynamic Vehicle Features / Specifications

**Finding 4.** Admin-managed, dynamic spec system so listings can declare arbitrary features (Parking Sensors = yes, Doors = 5, Radio = yes) without code changes.

### Why structured (not free-form JSON)
D3 must **filter** on these, admins must manage them consistently, and buyers compare them — so use defined attributes with typed values, not a free-text blob. (A JSONB column is fine as an *additional* free-form "extra notes/specs" field, but the filterable features are structured.)

### Tasks — data model
- [ ] `feature_definitions`: name, key, **type** (`boolean` | `number` | `enum` | `text`), unit (nullable, e.g. "doors"), enum options (nullable), `is_filterable` (bool), display group/order, active flag.
- [ ] `vehicle_feature_values`: vehicle_id, feature_definition_id, value (typed/cast). Unique per (vehicle, feature).
- [ ] Seed a sensible starter set (doors, transmission, fuel, parking sensors, sunroof, A/C, drivetrain, seats, etc.).

### Tasks — UX
- [ ] **Admin**: CRUD for feature definitions (add/edit/retire features, mark filterable, set type/options/units).
- [ ] **Seller (vehicle create/edit)**: dynamic form section that renders inputs by feature type (toggle for boolean, number input with unit, select for enum) — driven by the definitions, no hardcoding.
- [ ] **Buyer (detail page)**: grouped, scannable spec/feature display; boolean features shown as present/absent clearly.

### Exit criteria
- An admin can add a new feature without a deploy; sellers set it on a listing; it shows on detail and can power a D3 filter when marked filterable.

---

## D5 — Listing Expiry & Renewal

**Finding 5.** Listings (vehicles by default) expire after a configurable period and can be renewed.

### Tasks
- [ ] Add lifecycle fields to vehicle listings: `published_at`, `expires_at`, `renewed_at`, `expiry_count`; statuses extend to include `expired`. (Coexists with existing promotion fields `featured_until`/`bumped_at` from §7R.5.)
- [ ] Config-driven duration (`platform_settings`, default 60 days); expiry scope per `Open decisions`.
- [ ] Scheduled job: on `expires_at`, transition to `expired` → removed from public catalogue/search, retained in seller dashboard with a clear status and a **Renew** action.
- [ ] **Renew** resets `expires_at` (+ duration), sets `renewed_at`, increments `expiry_count`, returns listing to `active`. Free at launch behind the renewal-cost config hook (see decision).
- [ ] **Reminder notifications** before expiry (e.g. 7 days, 1 day) and on expiry — via Phase 18 notifications.
- [ ] Reflect any monetisation hook back into `BUSINESS_MODEL.md §8`.

### Exit criteria
- A listing auto-expires, drops from public view, is renewable from the seller dashboard, and the seller is warned before it lapses.

---

## D6 — Lead & Contact Tracking (the car-side metric that matters)

**Finding 6.** Vehicles are lead-gen — the platform's value on the car side is *connecting* buyers and sellers. So every contact event is the product's core outcome and must be captured.

### Tasks — capture
- [ ] `leads` table: `type` (e.g. `contact_reveal`, `call_click`, `whatsapp_click`, `message`, `enquiry_form`, `rfq`, `concierge`, `test_drive_request`), polymorphic `subject` (vehicle or product), `seller_id`, `buyer_id` (nullable — capture anonymous too), captured contact info, `message`, `source`/UTM, `status` (`new` | `contacted` | `converted` | `lost`), notes, timestamps.
- [ ] **Instrument every contact surface**: reveal-phone, call/WhatsApp click, "message seller", enquiry form, plus RFQ and concierge submissions — each writes a lead. (This must hook into surfaces built in other tasks/phases; treat as cross-cutting.)
- [ ] Capture anonymous contacts (no account) with whatever info is provided, flagged as guest leads.

### Tasks — use & manage
- [ ] **Seller dashboard**: their leads (who contacted, on which listing, when, status), with status updates and notes — a light per-seller CRM view.
- [ ] **Admin dashboard**: all leads across the site; conversion funnel (views → contacts → marked converted); lead volume by listing/seller/channel (feeds production P-track reporting / Phase 24).
- [ ] **Privacy/consent**: capturing PII — add consent where required, a retention/deletion path, and never log raw contact PII (ties to production P3/P9 data-protection).

### Exit criteria
- Every buyer-to-seller contact (logged-in or guest) creates a tracked lead; sellers and admins can see and manage leads; the car-side funnel is measurable.

---

## Suggested order

`D1 → D4 → D2 → D3 → D5 → D6`
(images first since they undermine everything; features before filters since filters consume them; expiry and leads last.) D6 instrumentation should also be added to contact surfaces as they're touched in other work.

## Execution principles (unchanged)

1. Root-cause before fixing; treat findings as classes — fix every sibling surface.
2. Authorization server-side; navigation/abilities from one config source.
3. No hardcoded data or fees — queries + `platform_settings`; dynamic features admin-managed, never hardcoded.
4. Verify by execution as the role (render the landing/detail/dashboard and confirm).
5. Checkpoint after each D-task.

---

*Document Version: 1.0*
*Companion: production_readiness_task_order.md, UI_STANDARDS.md, BUSINESS_MODEL.md, task_execution_order.md v2.0*
*Status: Ready for execution*
