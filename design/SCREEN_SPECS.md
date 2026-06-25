# Salma Drive — Screen Specifications (v1.0)

Implementation-ready screen specs for the five core surfaces. Each screen lists **layout (responsive)**, **components used** (from `DESIGN_SYSTEM.md §5`), and **behaviour/states**. All colours via semantic tokens; mobile-first; both themes; WCAG 2.2 floor. Wireframes are schematic.

Notation: `[ ]` = component/region · `→` = responsive change.

---

## A. Public website

### A1. Homepage
```
[Topbar: logo · Buy · Sell · Find a Dealer · theme-toggle · account]
[Hero: headline + type-tabs(Products|Cars|Bikes|Boats|Trailers) + search(make,model,min,max) + Search N vehicles]
[Browse by Body Type  — icon grid]
[Browse by Make — logo grid]
[Featured Dealers — dealer-card carousel]
[Featured Listings — vehicle-card grid (1→2→3→4)]
[Recent Uploads — vehicle-card carousel]
[Parts spotlight — part-card row + "Shop parts"]
[Footer: links · legal · social]
```
- Components: `x-topbar, x-tabs, x-input/select(searchable), x-button(primary), x-dealer-card, x-vehicle-card, x-part-card`.
- Behaviour: type-tab switches available search facets (H0); **live count** in the Search button (cached); body-type/make tiles deep-link to filtered results; cards lazy-load images; hero collapses to stacked search on mobile.

### A2. Search results
```
desktop: [Result bar: "1–12 of N" · Sort ▾]   [Active-filter chips]
         [Results grid (3-col)]                [Filter rail →]
mobile:  [Sticky: Filters button · Sort]  [Results (1-col)]  → Filters open bottom-sheet
```
- Components: `x-vehicle-card (with Compare checkbox, wishlist), x-badge(status), filter groups, x-pagination, x-empty, x-skeleton`.
- Behaviour: filters compose with type+search+sort; URL-encoded state (shareable/back-safe); compare selection persists into compare tray; **Sold** ribbon on sold; empty state → "Request it" (RFQ) CTA; skeletons while loading.

### A3. Vehicle detail
```
[Breadcrumbs]
[Gallery: main + thumb-strip + count badge + fullscreen + share/download]   [Sticky contact card →]
[Title + price + status chips (Year·Fuel·Trans·Drive·Condition·Recent Import·Duty Paid)]   [ WhatsApp (primary) ]
[Tabs/sections: Overview · Specifications(table) · Features(grouped) · Description]   [ Call · Show Number(masked) ]
[Seller/dealer mini-card + Visit storefront]   [ short enquiry form ]
[Compatible parts — part-card row]            [ Track Price · Compare · Wishlist ]
[Similar vehicles — vehicle-card row]
```
- Components: `x-gallery, x-price, x-badge, x-table(specs), x-contact-bar, x-dealer-card, x-part-card, x-vehicle-card, x-button(whatsapp/outline)`.
- Behaviour: every contact action (WhatsApp/Call/Show-Number/enquiry) **fires a tracked lead** (H4); phone masked until reveal (rate-limited); contact card sticky on desktop, sticky bottom bar on mobile; specs table is type-aware; unverified seller shows badge.

### A4. Dealer storefront
```
[Banner + logo + name + Verified tier + location + stats]
[Tabs: Listings · About · Contact]
[Listing grid (filterable)]
```
- Components: `x-dealer-card(header), x-tabs, x-vehicle-card, x-empty`. Storefront views log analytics events.

### A5. Parts marketplace + A6. Part detail
```
A5: [Category rail] [Search + sort] [part-card grid]   (mobile: category drawer)
A6: [gallery] [title·price·vendor+verified·fulfilment chip(FBS/COD)] [Add to cart] [specs/compatibility] [compatible vehicles]
```
- Components: `x-part-card, filter rail, x-price, x-badge, x-button(primary/whatsapp), x-table`.
- Behaviour: parts ARE transactional → cart/checkout enabled; fulfilment + COD eligibility per rules; compatibility links to vehicles (H10).

### A7. Comparison page
```
[Sticky attribute column | Vehicle 1 | Vehicle 2 | Vehicle 3 (+add)]
[rows: price, year, mileage, fuel, transmission, drive, features… diffs highlighted]
```
- Component: `x-compare-table`. Mobile: frozen attribute column + horizontal scroll; remove-column; "add vehicle" slot.

---

## B. Customer portal (buyer)

Shell: `x-sidebar` (Dashboard, Saved searches, Alerts, Favorites, Recently viewed, My orders) + `x-topbar`. **No seller surfaces.**

- **B1 Dashboard:** `x-stat-tile` row (saved searches, alerts, favorites, orders) + recently-viewed row + recommended row.
- **B2 Saved searches:** list with match counts + "notify me" toggle + run/edit/delete.
- **B3 Alerts:** price-drop / new-match alerts list; toggle channels (email/in-app).
- **B4 Favorites / wishlist:** vehicle-card grid with remove; price-change indicator.
- **B5 My orders (parts):** order list/table → order detail (status timeline, items, delivery, support).
- States: every list has an empty state + skeletons.

---

## C. Dealer (vendor) portal

Shell: role-aware `x-sidebar` (Dashboard, Listings, Leads, Parts inventory, Wallet, Team, Analytics) + `x-topbar` ("+ Add Listing"). **No buyer surfaces.**

- **C1 Dashboard (signature):** **instrument-cluster** `x-stat-tile` grid — Active Listings, Featured, Views, Detailed Views, Enquiries, Phone Reveals, Calls, WhatsApp clicks, each with period delta; traffic chart in a card; expiring-soon + renew prompts.
- **C2 Listing management:** table/grid of listings (status: draft/published/expired/sold), bulk actions, per-listing "View stats"; "+ Add Listing" → C7 editor.
- **C3 Leads:** lead table (type, listing, contact, date, status) + status update + notes (light CRM); filter by type/listing; export.
- **C4 Parts inventory:** table CRUD, stock, fulfilment type (FBS/vendor), COD flag.
- **C5 Wallet:** balance, ledger history, payouts, top-up; floor warning banner if below threshold.
- **C6 Analytics:** per-listing trends, lead funnel (views→contacts→converted), FBS vs vendor mix.
- **C7 Listing editor (wizard, not forced tabs):** steps **Details → Description → Features → Images**; type-aware fields (H0); Zimbabwe fields (POA/Duty Paid/Recent Import/Ref Code/steering); **Save(draft) · Publish · Delete** in sticky bar; per-step validation; image upload with reorder/primary/watermark.

---

## D. Private-seller portal

Lighter version of C (individuals): Dashboard (stat tiles + leads), My listings, Leads, Wallet (if transacting), Profile/status (pending/verified badge). Uses the **same** components, fewer nav items, listing-cap aware. Pending sellers reach the dashboard immediately and may create badged (unverified) listings.

---

## E. Admin portal

Shell: `x-sidebar` (Users, Verification, Moderation, Analytics, Settings) + `x-topbar`. **No buyer/seller surfaces.**

- **E1 User management:** searchable table (role, status, verified); actions — create, suspend/reactivate, role change, password reset, email-verify bypass; all audit-logged; super-admin vs admin scope enforced.
- **E2 Dealer/seller verification:** approval queue (documents, bank), approve/reject with reason; sets verified badge/tier.
- **E3 Listing moderation:** reported-listing queue + flags; hide/remove/warn/dismiss; audited.
- **E4 Marketplace analytics:** KPI `x-stat-tile` row (users, vendors, listings, leads, revenue by stream) + charts; date range.
- **E5 Settings:** `platform_settings` editor (fees, thresholds, durations, promotion prices) — the config that powers everything; no hardcoded values.

---

## Cross-cutting screen rules

- Every authenticated role lands on its dashboard; persistent dashboard/home control in nav.
- Top nav + sidebar render from one role→capability config; out-of-scope routes rejected server-side.
- Each data list ships **empty + loading + error** states.
- Forms: inline validation, preserved input, sticky action bar, active-voice labels ("Publish" → "Published").
- All stat tiles use real, scoped, deduped queries (no placeholders).
- Verify each screen rendered in **both themes** and at **mobile + desktop** widths before sign-off.

---

*Version 1.0 · Companion: DESIGN_SYSTEM.md, tokens/* · Status: Ready for implementation*
