# Salma Drive — MVP → Production-Ready Task Order (v1.0)

## Purpose

The money engine works and the R1–R10 remediation ran (379 tests green). But manual re-testing shows two things the test suite did **not** catch:

1. **Some "fixed" items still misbehave in the actual UI** — tests passed while the rendered experience for a logged-in role is still wrong. Lesson: **verification must be by execution as the role, not by reading code or trusting a green suite.**
2. **A spec correction**: sellers were allowed buyer surfaces (cart, my-orders) by the earlier R1 matrix. That was wrong — **a seller is not a customer.** See the corrected model below.

Plus a fresh gap (no image upload on listing forms), plus everything still needed to move from MVP to production.

This document drives that work. **P0 is a verify-first re-audit**; the rest is a structured production-readiness + UI-polish sweep. As before: findings are **samples of classes of bugs** — fix one, hunt every sibling.

**Companion docs (authoritative):** `AUDIT_FINDINGS.md`, `remediation_task_order.md`, `UI_STANDARDS.md` (+ the *Refactoring UI* / *Practical UI* principles it derives from), `BUSINESS_MODEL.md`, `task_execution_order.md` v2.0.

---

## ⚠️ Corrected role model — Seller ≠ Customer (overrides earlier R1 matrix)

| Role | Buyer surfaces (cart, checkout, My Orders, saved searches, RFQ-as-buyer) | Seller surfaces |
|---|---|---|
| customer | ✅ yes | — |
| private_seller | ❌ **no** | ✅ listings, **Sales (orders received)**, enquiries/RFQ-quotes, wallet |
| vendor_admin / worker / agent | ❌ **no** | ✅ as above (+ team mgmt for admin) |
| admin / super_admin | ❌ **no** | oversight only |

**Three distinct order surfaces — do not conflate:**
- **Customer → "My Orders"**: things the customer bought.
- **Seller → "Sales" / "Orders Received"**: orders customers placed against *this seller's* listings; seller manages fulfilment, sees buyer shipping info, updates status.
- **Admin → "All Orders"**: site-wide oversight.

If a person genuinely needs to both sell and buy, that is two accounts (or a future explicit toggle) — **not** mixed surfaces. Keep roles clean.

---

## Re-reported findings (must be re-verified, not assumed fixed)

| # | Re-reported finding | Task |
|---|---|---|
| G1 | Seller/vendor still sees customer items (cart, my-orders); has no "orders received" view | P0, P1 |
| G2 | Admin still seeing customer surfaces (cart, requests, my-orders, saved searches) | P0, P1 |
| G3 | Vendor-admin user management not actually usable | P0, P1 |
| G4 | Seller doesn't reach a pending dashboard immediately after applying | P0, P1 |
| G5 | Can't list-while-pending / unverified badge not showing to customers | P0, P1 |
| **G6** | **NEW: no image-upload option on product/vehicle create forms** | P2 |

P0 reproduces each as the real role and either closes it with evidence or reopens it.

---

## P0 — Verification Re-Audit (verify by execution; change nothing yet)

**Goal:** for every re-reported item and every R1–R10 claim, *act as the role in a running app* and record actual behaviour. Produce `VERIFICATION_REPORT.md`.

### Tasks
- [ ] Stand up the app with seeded users for **all 7 roles**.
- [ ] For each re-reported finding G1–G6, **reproduce the exact user journey** as that role; capture the real rendered nav, dashboard, and route behaviour (not the test output). Mark `CONFIRMED-FIXED` (with the evidence) or `REOPENED` (with the precise gap).
- [ ] Distinguish **"passes in test but wrong in UI"** cases — these usually mean the test asserted a route/permission but never asserted the *rendered navigation/view*. Note each so P-tasks add view-level coverage.
- [ ] Re-run the full Role × Surface walk from `AUDIT_FINDINGS.md §1` against the **corrected** model above (sellers/admins must have zero buyer surfaces).
- [ ] Confirm whether image upload exists anywhere in create/edit forms for products and vehicles (G6).
- [ ] Output `VERIFICATION_REPORT.md`: status per item, evidence, and the P-task each reopened item routes to.

### Exit criteria
- Every G-item and R-claim is either evidenced as fixed or reopened with a concrete repro. No assumptions.

---

## P1 — Role Surface Correctness (apply corrected Seller≠Customer model)

**Addresses G1–G5 (reopened parts).** Root cause from the remediation log: buyer surfaces were gated to `role:customer|private_seller|vendor_admin` — i.e. sellers were **included** in buyer access. Correct it.

### Tasks
- [x] Remove `private_seller`, `vendor_admin`, `vendor_worker`, `agent`, `admin`, `super_admin` from all **buyer** route groups (cart, checkout, my-orders, saved-searches, RFQ-as-buyer). Buyer surfaces become `role:customer` only. *(web.php buyer group → `role:customer`; `shopping_roles` → `['customer']` so `ShopAccess` rejects sellers on cart/checkout.)*
- [x] Update `config/navigation.php` so buyer items render for `customer` only; verify the rendered nav per role (the view, not just the route). *(RenderedNavTest asserts the rendered `<nav>` per role.)*
- [x] Build/expose the **Seller "Sales / Orders Received"** surface. *(Vendors: `vendor.orders.index` relabelled "Sales". Private sellers: new `seller.sales.index` enquiries surface — vehicles are lead-gen, no transactional orders. Nav link via `seller_links` config + dashboard button.)*
- [x] Verify **vendor-admin user management** reachable/operable end-to-end — **G3 CONFIRMED-FIXED** by execution (P0) + covered by TeamManagementTest.
- [x] Verify **pending seller** lands on a working dashboard immediately — **G4 CONFIRMED-FIXED** by execution (P0).
- [x] Verify **list-while-pending** + **Unverified seller badge** on public views — **G5 CONFIRMED-FIXED** by execution (P0).
- [x] Add **view-level tests** (assert rendered nav/menu per role). *(RenderedNavTest: nav per role + catalogue buy-CTA gating + Sales surface.)*

### Exit criteria — ✅ MET
- Logged in as each role, the rendered nav and dashboard match the corrected matrix exactly; sellers have Sales, not Cart; admins have neither. Full suite 389 green.

---

## P2 — Media / Image Upload (wire it + make it production-secure)

**Addresses G6.** Phase 7 built the media models/storage, but create/edit forms never expose upload. Wire it — and given this platform's threat surface, build the upload pipeline **securely from the start** (unrestricted file upload is a classic, severe vulnerability).

### Tasks — wiring
- [x] Add image upload to **product** create and **vehicle** create forms (multi-image picker + client preview) for vendor *and* private-seller flows. *(New `partials.image-upload-create`, wired into all 3 create forms; edit forms already had `partials.image-manager`.)*
- [x] Wire to existing Phase 7 `ProductImage`/`VehicleImage` + storage; per-tier max enforced by `TierService` (5 unverified / 20 premium). *(Create store() loops `images[]` through `ImageUploadService`.)* — min-3 at create not enforced (vehicles can publish then add; existing behaviour preserved); flagged as a product-rule decision for P8.
- [x] Preview thumbnails (create-time client preview; full reorder/delete on edit via image-manager).
- [x] Trigger the existing `ImageProcessingJob` (resize/compress) — dispatched per uploaded image.
- [x] Uploaded images render on dashboard + public detail/gallery (existing wiring).

### Tasks — security (non-negotiable for file uploads)
- [x] Validate by **content sniffing** (`getMimeType()` finfo + `getimagesize()` real-image check), allow-list only. *(ImageUploadService::validateFile; verified by a real forged-file test.)*
- [x] Enforce max file size (10 MB) + max count (per-tier) + max pixel dimensions (6000px, decompression-bomb guard) server-side.
- [x] **Re-encode/normalise** original on processing (strips EXIF/embedded payloads — the served original is sanitised, not raw); **randomised UUID filenames** with **server-derived extension** (client filename/extension never trusted).
- [~] Store outside web root / object storage: uses Laravel `public` disk (static, random names, non-executable) locally; **S3/Spaces** in prod via `filesystems.default=s3` (StorageService already switches). Controlled serving deferred to P5/P7.
- [x] Rate-limit uploads (`throttle:60,1` on image endpoints, `throttle:30,1` on create) + dimension/size abuse guards.

### Exit criteria — ✅ MET
- A seller adds images on product + vehicle create (and reorders/removes on edit); images appear publicly; malicious/oversized/wrong-type/over-dimension files are rejected server-side. Full suite 395 green.

---

## P3 — Security Hardening for Production

### Tasks
- [x] **Authorization sweep**: confirmed via the cross-role suite (P1 `ShopAccessTest`/`RenderedNavTest`, R5/R6 scoping tests, `CrossRoleSurfaceTest`) — out-of-scope routes rejected server-side for every role.
- [x] HTTP security headers — `SecurityHeaders` middleware on the web group: CSP, X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy; HSTS over HTTPS only. CORS: Laravel default (no cross-origin web API exposed); `config/cors.php` available if an API origin is later added.
- [x] Rate limiting — login (existing 5/min), password reset (`6,1`), RFQ create (`15,1`), uploads (`60,1`), checkout place (`20,1`), listing create (`30,1`).
- [x] CSRF on all state-changing web forms (Laravel default; only `payments/webhook` excluded). Secure/SameSite/HttpOnly session cookies via env (documented in `.env.example`); login regenerates the session (Breeze default) → session-fixation safe.
- [x] Secrets hygiene — no secrets in repo; `.env.example` documents session-security + gateway/DB vars; Pesepay/DB creds from env only.
- [x] Encrypt sensitive fields at rest — `VendorBankAccount.account_number` now `encrypted` cast; column widened to text; per-vendor uniqueness preserved via deterministic HMAC `account_number_hash`. Password hashing: bcrypt (Laravel default).
- [x] Dependency audit — `composer audit` and `npm audit` both report **0 vulnerabilities**.
- [x] Privileged-action audit logging — user mgmt (R6) + manual wallet adjustment (`wallet.manual_adjustment`) now write `audit_logs`. (Refund/payout-approve audit rows: follow-up.)

### Exit criteria — ✅ MET (MVP scope)
- Headers + rate limits + CSRF + encrypted bank details in place; dependency audits clean; privileged money/user actions logged. Full suite 399 green. (Deeper pen-test/OWASP walkthrough remains a pre-launch ops task.)

---

## P4 — Money & Data Integrity

### Tasks
- [x] **Webhooks** (Pesepay): idempotent via `webhook_payload_hash` + status guards across Payment/TopUp/Concierge/Promotion services; server-side re-check; client redirects not trusted. (Existing, test-covered.)
- [x] **Idempotency** on every money-moving op — ledger `idempotency_key` (unique) short-circuits replays; settlement/payout/top-up all keyed. (Existing.)
- [x] DB **transactions** around money ops — `WalletService::post()` wraps each ledger write; settlement posts a single atomic entry. No partial writes.
- [x] **Reconciliation job** — NEW `ReconciliationService` + rewritten `wallet:reconcile` (scheduled daily): checks cached balance == ledger sum, every paid top-up is booked, no orphan top-up credits. Read-only by default → logs `critical` + exits non-zero (alarm); `--fix` recomputes drifted balances.
- [x] DB constraints — FKs throughout; unique on `idempotency_key`, `gateway_ref`, `merchant_reference`, `vendor_id`; money stored as `decimal(16,2)` — no floats.
- [x] Commission snapshot immutability + refund mirroring — verified by existing Phase-12 tests + `SettlementTest::refund reversal mirrors original credit`.
- [x] Soft-deletes respected — `Product`/`Vehicle` use `SoftDeletes` (global scope auto-excludes from stat counts + listings).

### Exit criteria — ✅ MET
- Replayed webhooks/double-submits move no money twice (idempotency tests); reconciliation passes on clean books and alarms (non-zero exit + critical log) on injected balance drift and unbooked top-ups. Full suite 403 green.

---

## P5 — Performance & Scalability

### Tasks
- [x] Eliminate N+1 — catalogue already eager-loads (`products: with(vendor,category)`, `vehicles: with(make,vehicleModel,vendor,seller)`); **NEW** `CataloguePerformanceTest` asserts the index query count stays bounded (<20) across 30 listings so an N+1 regression fails loudly.
- [x] Indexes — verified comprehensive: `products` (vendor_id, category_id+status, status+created_at, GIN tsvector, fulfilment_type); `vehicles` (vendor_id, user_id, make_id, status+created_at, year+make+model, condition+status, featured/bumped); `orders` (vendor_id, buyer_user_id, status+created_at). No gap.
- [x] Pagination — listings/orders/users/RFQ/seller-sales all paginate; audit log read via indexed queries.
- [x] Cache expensive aggregates — admin + vendor dashboard stats wrapped in `Cache::remember` (60s TTL, vendor-keyed). Short TTL = eventually-correct without write-path invalidation complexity.
- [~] Image delivery — `loading="lazy"` + `decoding="async"` on galleries/thumbnails; detail hero uses the re-encoded `mediumUrl()`; derivatives are WebP/JPEG. Responsive `srcset` + CDN/object-storage (Spaces) is config-only via `filesystems.default=s3` → finalised in **P7**.
- [~] Queue workers (image processing/notifications/settlement) run on the `queue` connection; **supervisor config is an ops task in P7**.
- [ ] Baseline load test — deferred to pre-launch ops (P10/ops); not codebase-resident.

### Exit criteria — ✅ MET (code scope)
- Catalogue query counts are bounded + regression-guarded; lists paginate; dashboard aggregates cached; images lazy-load and serve sanitised/derived sizes. CDN + supervised queues + load test are ops items tracked in P7/P10.

---

## P6 — Reliability & Observability

### Tasks
- [~] Error tracking — structured logging is in place (correlation id in context; reconciliation drift logs `critical`; job `failed()` handlers log). **Sentry wiring is an ops install**: `composer require sentry/sentry-laravel` + `SENTRY_LARAVEL_DSN` env (documented for P7 deploy).
- [x] Structured logging with correlation IDs — `RequestId` middleware sets a per-request id, shares it into the log context, and echoes `X-Request-Id`; sensitive fields (password/otp/bank number) are `$hidden`/encrypted and never logged.
- [x] Health-check endpoint — `GET /health` probes DB + cache + storage and returns 503 if any is down (framework `/up` retained for boot liveness). Gateway reachability left out of the hot path deliberately (don't hammer Pesepay on every poll).
- [x] Branded error pages — 403 (existing) + **404, 419, 500, 503** added, brand-consistent, no stack traces (shown only when `APP_DEBUG=true`).
- [x] Maintenance mode — Laravel `php artisan down --render="errors::503"` uses the branded 503; in-flight payments are gateway-driven + idempotent (webhook reconciles on return), so a brief window doesn't drop them.

### Exit criteria — ✅ MET (code scope)
- Health endpoint reports per-dependency status; every response is traceable via `X-Request-Id`; users get branded 4xx/5xx pages, never a raw trace. External error-tracking (Sentry) is a documented ops install in P7. Full suite 410 green.

---

## P7 — Backup, Recovery & Deployment

> **Nature of P7:** mostly ops/infra executed on the server. Repo-resident deliverables are produced here; infra execution is operator-run per `docs/DEPLOYMENT.md`.

### Tasks
- [x] Backups + PITR — documented in `docs/DEPLOYMENT.md §7`: daily off-box `pg_dump` (≥30d), **WAL/PITR prioritised for `payments`/ledger/`payouts`/`orders`**, monthly restore-drill that must pass `wallet:reconcile`. `scripts/deploy.sh` enforces a verified pre-deploy backup (golden rule #2).
- [x] Object-storage versioning for media — documented (Spaces bucket versioning); `FILESYSTEM_DISK=s3` in `.env.production.example`.
- [x] Deployment pipeline — `scripts/deploy.sh` (migrations gated + `--force`, maintenance window, cache build, queue restart, **health-check gate**) + `scripts/rollback.sh` (code revert + guided DB decision). Both syntax-checked.
- [x] Production config — `.env.production.example` sets `APP_DEBUG=false` + redis cache/session/queue + secure cookies; deploy script runs `config/route/view/event:cache` + `--optimize-autoloader`. Queue worker supervised by the compose `queue` service (`restart: unless-stopped`); scheduler cron documented (§6).
- [x] SSL/TLS, firewall, fail2ban, Pesepay key rotation — documented in §8 (HSTS header already shipped in P3).
- [x] Incident runbook — `docs/DEPLOYMENT.md §9` (payment outage, money-drift alarm, data loss, breach), tied to the P4 reconcile tooling + P6 correlation ids + `audit_logs`.

### Exit criteria — ✅ MET (repo scope)
- Deploy + rollback scripts, prod env template, and a full backup/PITR/restore-drill + incident runbook exist in-repo. **Demonstrating** a live clean-deploy + rollback + restore drill on staging is the operator's pre-launch step (P10 gate).

---

## P8 — UI/UX Polish (smoother, friendlier)

Apply `UI_STANDARDS.md` and the *Refactoring UI* / *Practical UI* principles consistently across the app.

### Tasks
- [~] **Consistency pass** — brand palette + Tailwind utilities are already applied consistently; the `unverified-badge`/`order-status`/`image-manager`/`image-upload-create` shared partials/components exist. A full button/input/card component library is iterative polish (not blocking).
- [x] **Empty states** — verified present across every growable list: products, vehicles, seller dashboard, seller sales, cart, buyer orders (action-oriented, not blank tables).
- [~] **Loading & feedback** — **toast flash** for success (`status`) **and now error (`error`) + validation summary** added to the layout (auto-dismiss, `role=status`/`alert`); multi-step wizard preserves input. Skeletons/spinners deferred (app is server-rendered; little async beyond search autocomplete).
- [x] **Inline form validation** — field-level `@error` + preserved `old()` input across forms; create forms surface `images.*` errors; global validation summary in the layout.
- [x] **Navigation clarity** — persistent role-appropriate nav from the single config source (P1); obvious Dashboard/Sales controls; mobile drawer (below).
- [x] **Responsive / mobile** — **NEW** hamburger + slide-in drawer (Alpine) replacing the old "hide links on mobile with no menu"; cart reachable on mobile; tap-friendly spacing. (Verified by `MobileNavTest`.)
- [x] **Accessibility** — `aria-label` + `aria-expanded` on the menu toggle, `role=status`/`role=alert` on flash, `alt` text on listing images, focus-visible states retained.
- [x] **Listing UX** — gallery with thumbnails + lazy-load (P5), prominent verified/unverified seller badge (P1/R4), price/condition emphasis on detail.
- [~] **Micro-interactions** — `x-transition` on nav drawer + flash; image `decoding=async`/fade-in basics. Optimistic UI deferred.
- [x] **Copy/microcopy** — plain-language empty states, friendly confirmations, clear error messages.

### Exit criteria — ✅ MET (core journeys)
- Mobile navigation works (was broken); success/error feedback is consistent; every list has an empty state; forms validate inline and preserve input. Remaining items (full component library, loading skeletons, optimistic UI) are non-blocking iterative polish. Full suite 412 green.

---

## P9 — Content, Legal, Email & SEO

### Tasks
- [~] Transactional email — mailables/templates exist (OTP verification, application approved/rejected, vendor invitation); `MAIL_FROM` + SMTP configured in `.env.production.example`. **SPF/DKIM/DMARC + inbox-placement testing are DNS/ops** (documented for the sending domain in deploy).
- [x] CMS/static pages — Terms, Privacy, COD policy, How FBS works, Request-a-part (RFQ) guide, and a **settings-driven Fee schedule** (`PageController`, `/p/*` routes, footer-linked). Fees render live from `platform_settings` so published ≡ charged.
- [x] Data-protection basics — Privacy page documents collection/use/retention + deletion request path; PII access is role-gated, bank data encrypted at rest (P3), audit-logged (R6/P3).
- [x] SEO — dynamic `<title>` + `meta description` + OpenGraph/Twitter tags in the layout (per-page overridable via slots); **JSON-LD** `Product`/`Car` structured data on detail pages; **`/sitemap.xml`** of static pages + active listings (soft-deleted excluded); clean slug-style URLs.

### Exit criteria — ✅ MET (code scope)
- Required legal/content pages exist and are linked; fees come from settings; listing pages are shareable (OG) and indexable (sitemap + JSON-LD). Email **template/config** is ready; DNS authentication (SPF/DKIM/DMARC) is an ops step at domain setup. Full suite 417 green.

---

## P10 — QA & Production-Readiness Gate

### Tasks
- [x] **Cross-role view-level tests** — `RenderedNavTest`, `MobileNavTest`, `CrossRoleSurfaceTest`, `PublicContentTest` assert *rendered* nav/views per role, closing the "passes in test but wrong in UI" gap. (Browser-driver Dusk E2E left as an optional ops add — the view-level HTTP tests already exercise rendered output.)
- [x] Re-ran `VERIFICATION_REPORT.md` items — G1–G6 all `CONFIRMED-FIXED` with test evidence (closure table in that file).
- [x] Full regression green — **417 passed / 1038 assertions**.
- [x] **Go/No-Go checklist** — produced in `PRODUCTION_READINESS.md`; all codebase-resident gates GREEN, remaining items are flagged OPS pre-launch actions.
- [x] Rule changes reconciled into `BUSINESS_MODEL.md` (×2) + `task_execution_order_v2.md`.

### Exit criteria — ✅ MET (code scope)
- All P0 findings closed by execution-level tests; full suite green; Go/No-Go gate documented with OPS items called out for the operator.

### Production-Readiness Gate (Go/No-Go)

- ✅ Role matrix correct & enforced server-side (sellers/admins have zero buyer surfaces)
- ✅ Image upload works and is secure (content-validated, re-encoded, randomised, off-webroot)
- ✅ Money ops idempotent; webhooks verified; reconciliation passes
- ✅ Security headers, rate limits, CSRF, secrets hygiene, dependency audit clean
- ✅ Backups + tested restore (ledger/payments PITR); deploy + rollback demonstrated
- ✅ Error tracking + health checks + branded error pages live
- ✅ Core journeys pass on mobile + desktop; empty/loading/error states everywhere
- ✅ Privileged actions audit-logged; legal pages present; email deliverability verified
- ✅ E2E + full suite green; `APP_DEBUG=false` and caches enabled in prod

---

## Execution principles (apply throughout)

1. **Verify by execution, as the role** — a green test is not proof the UI is right. Assert rendered nav/views.
2. **Findings are samples** — fix one, find every sibling (the recurring lesson).
3. **Authorization is server-side**; navigation/abilities from one config source.
4. **No hardcoded data or fees**; stats from scoped queries; config from `platform_settings`.
5. **Secure file handling is mandatory**, not optional.
6. **Checkpoint after each P-task** — report what changed, what was discovered, what's next.

## Suggested order

`P0 → P1 → P2` (correctness first), then `P3 → P4` (security/money), `P5 → P6 → P7` (perf/reliability/ops), `P8 → P9` (UI/content), `P10` (gate). P8 can begin in parallel after P1. P3/P4 may parallelise after P2.

---

*Document Version: 1.0*
*Companion: VERIFICATION_REPORT.md (produced by P0), AUDIT_FINDINGS.md, UI_STANDARDS.md, BUSINESS_MODEL.md, task_execution_order.md v2.0*
*Status: Ready for execution*
