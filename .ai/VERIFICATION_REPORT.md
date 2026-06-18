# Salma Drive — VERIFICATION_REPORT.md (P0 output)

> **Mode:** Verify-only. No application behaviour changed. Findings are by **execution as the role** — pages were actually rendered as each of the 7 roles and the real `<nav>` markup + route status codes were captured (not test assertions, not code reading). Evidence harness: a temporary read-only test rendered `/`, the buyer routes, and the G3/G4/G5 journeys for every role and dumped a JSON evidence file. Raw evidence is reproduced inline below.

**Verdict headline:** The big spec correction (**Seller ≠ Customer**) is **NOT applied** — `private_seller` and `vendor_admin` still get the full buyer nav + reachable buyer routes, and there is no "Sales / Orders Received" surface. That's **G1 REOPENED** and it is the dominant defect. **G2 is mostly fixed at the nav/route layer but leaks at the view layer** (catalogue still shows buy CTAs to everyone). **G3, G4, G5 are CONFIRMED-FIXED by execution** (the re-reports appear stale). **G6 REOPENED** — create forms have no image upload.

---

## Method

- Seeded all 7 roles (`customer, private_seller, vendor_admin, vendor_worker, agent, admin, super_admin, rider`), each `active`/verified, vendor roles attached to a vendor so `vendor.scope` resolves.
- For each role: rendered `GET /` and extracted the **`<nav>` block only**, testing for the presence of each menu item; then hit each buyer route and recorded the **HTTP status**.
- Extra journeys: pending `private_seller` → `seller.dashboard` (G4); `vendor_admin` → `vendor.team.index` and whether a worker is listed (G3); guest → public product detail of a **pending** vendor's product, checking for the "Unverified seller" badge (G5).
- G6 (image upload) verified by inspecting the actual create/edit Blade templates.

---

## Rendered-nav evidence (per role)

`✅` = item present in rendered nav · `—` = absent. Buyer-route column shows actual HTTP status.

| Role | Shop | Vehicles | Cart | Dashboard | My orders | Requests | Saved | **Sales** | cart/orders/rfq/saved routes |
|---|---|---|---|---|---|---|---|---|---|
| customer | ✅ | ✅ | ✅ | — | ✅ | ✅ | ✅ | — | 200 / 200 / 200 / 200 |
| **private_seller** | ✅ | ✅ | **✅** | ✅ | **✅** | **✅** | **✅** | **—** | **200 / 200 / 200 / 200** |
| **vendor_admin** | ✅ | ✅ | **✅** | ✅ | **✅** | **✅** | **✅** | **—** | **200 / 200 / 200 / 200** |
| vendor_worker | — | — | — | ✅ | — | — | — | — | 403 / 403 / 403 / 403 |
| agent | — | — | — | ✅ | — | — | — | — | 403 / 403 / 403 / 403 |
| admin | — | — | — | ✅ | — | — | — | — | 403 / 403 / 403 / 403 |
| super_admin | — | — | — | ✅ | — | — | — | — | 403 / 403 / 403 / 403 |
| rider | — | — | — | ✅ | — | — | — | — | 403 / 403 / 403 / 403 |

**Corrected-model target:** the `customer` row should be the *only* one with Shop/Cart/My orders/Requests/Saved + 200s; `private_seller`/`vendor_admin` should look like the staff rows for buyer items, **plus** a **Sales** surface (which no role has today).

---

## Finding-by-finding

### G1 — Seller/vendor still sees customer items; no "orders received" view → **REOPENED** 🔴
**Evidence:** `private_seller` and `vendor_admin` rendered nav contains `Cart`, `My orders`, `Requests`, `Saved searches`; all four buyer routes return **200**. Neither role has a **Sales** nav item.
**Root cause:** `config/navigation.php` → `'shopping_roles' => ['customer', 'private_seller', 'vendor_admin']` (sellers included), and `routes/web.php` buyer group is `role:customer|private_seller|vendor_admin`. `App\Support\Navigation::canShop()` keys off `shopping_roles`, so the nav renders buyer items for sellers.
**Sales surface status:** `vendor_admin` *does* have `vendor.orders.index` ("orders received") but it is reached only via the dashboard and is **not** labelled/surfaced as "Sales". `private_seller` has **no** orders-received route at all (`routes/seller.php` has dashboard + vehicles only). Note vehicles are lead-gen (never checkout), so a private seller's "sales" may legitimately be empty/enquiry-only — to be decided in P1.
**Routes to:** **P1.** Make buyer surfaces `customer`-only (nav + routes), build/surface a seller **Sales** view, add per-role rendered-nav tests.

### G2 — Admin still sees customer surfaces → **MOSTLY FIXED, view-level leak REOPENED** 🟡
**Evidence (fixed parts):** `admin` and `super_admin` rendered nav has **no** Cart/My orders/Requests/Saved searches; all four buyer routes return **403**. So at the nav + route layer, admins are clean — the re-report appears **stale** (pre-R1 behaviour).
**Evidence (still leaking):** the **public catalogue** renders buy CTAs unconditionally. `resources/views/products/show.blade.php` shows the **Add to cart** form whenever the product is in stock with **no role gate** — so a logged-in seller/admin browsing `/products/{id}` sees a buyer CTA (the POST would 403 via `shop.access`, but the surface is shown). This is the classic "passes in test but wrong in UI": routes/nav asserted, rendered page body not.
**Routes to:** **P1** (gate catalogue buy CTAs to `canShop` = customers/guests only), plus **P8** polish.

### G3 — Vendor-admin user management not usable → **CONFIRMED-FIXED** ✅
**Evidence:** `vendor_admin` → `GET vendor.team.index` returns **200** and the rendered page **lists the vendor's worker** by name. Role-change/remove/last-admin guards + cross-vendor 404 were exercised by `TeamManagementTest` (R5). Reachable from the vendor dashboard "Team" quick link.
**Note:** operable end-to-end; the re-report appears stale. (Will still get a rendered-nav assertion in P1 so it can't regress.)

### G4 — Pending seller doesn't reach a working dashboard → **CONFIRMED-FIXED** ✅
**Evidence:** a `private_seller` with `status = pending` → `GET seller.dashboard` returns **200** (no bounce). `CheckUserStatus` only blocks `suspended`/`rejected`, not `pending` (R4). Dashboard renders the pending banner + stat cards.

### G5 — Can't list-while-pending / unverified badge not showing → **CONFIRMED-FIXED** ✅
**Evidence:** a **pending** vendor's active product → guest `GET products.show` returns **200** and the rendered HTML **contains "Unverified seller"**. Badge component is also wired into products index, vehicles index, and vehicle detail (R4).

### G6 — No image upload on product/vehicle create forms → **REOPENED (confirmed gap)** 🔴
**Evidence (template inspection):**
- `vendor/products/create.blade.php`, `vendor/vehicles/create.blade.php`, `seller/vehicles/create.blade.php` — **no** file input / no `partials.image-manager`.
- The image manager (`@include('partials.image-manager', …)`) is present **only** on the **edit** forms (`vendor/products/edit`, `vendor/vehicles/edit`, `seller/vehicles/edit`), i.e. images can only be added **after** the listing is saved.
- Phase 7 media plumbing exists and works: `App\Modules\Media\Controllers\{Vendor,Seller}` image controllers + `*.images.store/destroy/reorder` routes.
**Consequence:** a seller creating a listing cannot attach photos in one flow; a brand-new listing publishes image-less until they return to edit. For vehicles this also collides with the Phase 7 "min 3 images" rule, which is therefore not enforceable at create time.
**Routes to:** **P2** (wire upload into create forms for both vendor + private-seller; harden the pipeline).

---

## Re-walk of AUDIT_FINDINGS §1 against the **corrected** model

| Surface | Correct target | Actual (by execution) | Status |
|---|---|---|---|
| Cart (nav + route) | customer + guest only | customer, **private_seller, vendor_admin**, guest | 🔴 G1 |
| My orders / Requests / Saved | customer only | customer, **private_seller, vendor_admin** | 🔴 G1 |
| Buyer routes 403 for staff | all non-customer staff | vendor_worker/agent/admin/super_admin/rider ✅; **private_seller/vendor_admin ❌ (200)** | 🔴 G1 |
| Seller **Sales / Orders received** | private_seller + vendor roles | none surfaced (vendor has unlabelled `vendor.orders.index`; seller has nothing) | 🔴 G1 |
| Catalogue buy CTA | customers/guests only | shown to all viewers incl. sellers/admins | 🟡 G2 |
| Admin buyer nav/routes | none | none (403) | ✅ |
| Vendor team mgmt | vendor_admin, own vendor | reachable, scoped, lists members | ✅ G3 |
| Pending seller dashboard | reachable immediately | 200 | ✅ G4 |
| Unverified badge (public) | visible to buyers | visible | ✅ G5 |
| Image upload on **create** | present (vendor + seller) | absent (edit-only) | 🔴 G6 |

---

## "Passes in test but wrong in UI" cases (add view-level coverage in P1)

1. **Buyer nav for sellers** — R1's `ShopAccessTest` asserted that *staff* roles get 403 on buyer routes, but it **never asserted the rendered nav**, and it deliberately treated `private_seller`/`vendor_admin` as allowed (the now-corrected matrix). So the suite was green while the UI was wrong for sellers. → P1 adds per-role rendered-nav tests.
2. **Catalogue buy CTA leak** — no test asserts the product detail body hides "Add to cart" for non-shoppers. → P1/P8.

---

## Status summary & routing

| Item | Status | Routes to |
|---|---|---|
| G1 seller buyer surfaces + no Sales view | 🔴 REOPENED | P1 |
| G2 admin buyer surfaces | 🟡 nav/routes fixed; catalogue CTA leak | P1 (+P8) |
| G3 vendor team mgmt | ✅ CONFIRMED-FIXED | (P1 regression test) |
| G4 pending seller dashboard | ✅ CONFIRMED-FIXED | (P1 regression test) |
| G5 list-while-pending + badge | ✅ CONFIRMED-FIXED | (P1 regression test) |
| G6 image upload on create | 🔴 REOPENED | P2 |

**Exit criteria met:** every G-item and the relevant R-claims are either evidenced as fixed or reopened with a concrete repro. No assumptions.

---

## Open question before P1 (please confirm)

The corrected model says sellers have **no** buyer surfaces. For a **private_seller** specifically, vehicles are lead-gen (never checkout) and private sellers can't sell products — so a private seller currently has **nothing to "sell" transactionally**, meaning their "Sales / Orders Received" surface would be empty by design (only buyer **enquiries/RFQ-quotes** apply). 

**My recommended default:** private_seller "Sales" surface shows **enquiries on their vehicle listings** (and RFQ quotes they've sent), not transactional orders; full transactional **Sales / Orders Received** applies to **vendor** roles (reusing `vendor.orders.index`, relabelled "Sales"). Confirm, or tell me you want private sellers to have transactional sales too (implies giving them product-selling ability — larger scope).

---

*Generated by P0. No code changed. Next: on your confirmation, proceed P1 → P10, checkpointing after each P-task.*

---

## P10 closure — every G-item now CONFIRMED-FIXED (with test evidence)

| # | Finding | Status | Evidence |
|---|---|---|---|
| G1 | Seller saw buyer items; no Sales view | ✅ FIXED | `shopping_roles=['customer']` + `role:customer` buyer routes; seller "Sales" surface (`vendor.orders.index` relabelled / `seller.sales.index`). Tests: `ShopAccessTest`, `RenderedNavTest`. |
| G2 | Admin saw buyer surfaces / catalogue buy-CTA leak | ✅ FIXED | nav + routes clean (403); product-detail buy CTA gated on `canShop`. Tests: `RenderedNavTest::*add_to_cart*`. |
| G3 | Vendor-admin user mgmt unusable | ✅ FIXED | reachable + scoped; `TeamManagementTest`, `RenderedNavTest`. |
| G4 | Pending seller dashboard | ✅ FIXED | `CheckUserStatus` lets pending through; covered by smoke/dashboard tests. |
| G5 | List-while-pending + unverified badge | ✅ FIXED | `ListWhileUnverifiedTest`; badge on catalogue + detail. |
| G6 | No image upload on create forms | ✅ FIXED | create-form `images[]` + secure pipeline; `CreateWithImagesTest`. |

All closed by execution-level (rendered-view / HTTP) tests, not just route assertions — the specific gap P0 called out. See `PRODUCTION_READINESS.md` for the Go/No-Go gate.
