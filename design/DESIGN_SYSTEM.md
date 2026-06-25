# Salma Drive — Design System (v1.0)

**Target stack:** Laravel Blade + Tailwind CSS + Alpine.js
**Theme:** Light + Dark, **system-aware** (`prefers-color-scheme`) with a user toggle that can return to "follow system"
**Scope:** Public site · Customer portal · Dealer (vendor) portal · Private-seller portal · Admin portal (no loan/insurance)
**Purpose:** Implementation-ready spec for an AI coding agent to build the UI without a designer. Companion files: `SCREEN_SPECS.md`, `tokens/design-tokens.json`, `tokens/theme.css`, `tokens/tailwind.config.js`.

---

## 0. Brand thesis & signature

Salma Drive is a Zimbabwe-first automotive marketplace — **parts you can buy + cars you enquire on**, mobile-first, WhatsApp-native. The visual language is **confident, high-contrast, and instrument-like**: a **gold (#F0A820) primary** reads as premium-yet-energetic against deep near-black surfaces, echoing a car's lit dashboard at night.

**Signature element:** the **instrument-cluster stat tiles** — seller analytics (views, enquiries, calls, WhatsApp clicks) presented as gauge-style cards with a gold accent arc and tabular figures, like a dashboard gauge cluster. This is the one place we spend boldness; everything else stays quiet and disciplined. It's grounded in the product (the H5 seller-analytics dashboard), not decoration.

**Restraint rule:** gold is an accent and a fill, never wallpaper. One bold element per screen; the rest is calm neutrals and clear type.

---

## 1. Reference audit (what to copy / improve / avoid)

**AutoTrader (UK/ZA) & mobile.de — discovery & detail**
- *Copy:* live result count in the search CTA; free-text make/model + separate Filters; dense-but-scannable spec chips; sticky contact card on detail.
- *Improve:* make WhatsApp the primary contact (local norm), not a secondary link.
- *Avoid:* cookie-banner clutter pushing content below the fold.

**Carvana / CarGurus / Cars24 — trust & conversion**
- *Copy:* price-context signals (good/fair), photo-count badge, "Compare" affordance on cards, masked phone reveal.
- *Avoid:* US-financing-heavy layouts; your market is largely USD cash.

**Be Forward — breadth (Images 2)**
- *Copy:* shop-by-make with counts, top-sellers ranking, parts-alongside-cars cross-sell, save-search/notify quick actions.
- *Avoid:* maximalist banner overload, points-everywhere noise — it reads cluttered and erodes trust.

**CoreUI / Modernize admin templates (Images 4) — portals**
- *Copy:* left sidebar + topbar shell, KPI stat-card row, charts in cards, generous card spacing, dark-mode discipline.
- *Improve:* replace generic gradient KPI tiles with the instrument-cluster signature.
- *Avoid:* template "sameness" — derive every colour/type choice from these tokens, not the template defaults.

**E-commerce shop & rental/comparison cards (Images 5–8)**
- *Copy:* category filter rail + product grid for the **parts** marketplace; clear "Compare" checkbox overlay; status ribbons (New/Used/Sold).
- *Avoid:* discount-strikethrough styling applied to cars (cars are lead-gen, not priced-to-cart).

---

## 2. Colour system

### 2.1 Brand & palette (your inputs, extended to scales)

**Primary — Salma Gold** (base `#F0A820`)
`50 #FDF6E7 · 100 #FBEAC2 · 200 #F7D98A · 300 #F4C95C · 400 #F2B93C · 500 #F0A820 · 600 #D08D12 · 700 #A66E0E · 800 #7D530C · 900 #5C3D0A`

**Neutral — Slate/Ink** (your `#C8CDD6 · #5A6070 · #1A1A24 · #080810` anchored)
`0 #FFFFFF · 50 #F6F7F9 · 100 #ECEEF1 · 200 #DCDFE5 · 300 #C8CDD6 · 400 #9CA3B0 · 500 #5A6070 · 600 #454B59 · 700 #313643 · 800 #1A1A24 · 900 #0F0F16 · 950 #080810`

**Semantic**
- Success — `#2EBD7A` (light surface `#E7F8F0`, dark `#1E8A58`)
- Info / Accent — `#3DB8E8` (light `#E6F6FD`, dark `#1F8FBE`)
- Danger / Error — `#D4295A` (light `#FBE7ED`, dark `#A81D47`)
- Warning — `#EA7A1C` (deliberately more orange than the gold primary so the two never read as the same signal)

### 2.2 Accessibility rules (non-negotiable — gold is light)

Gold on white is ~1.9:1 → **fails** for text. Therefore:
- **Gold is a fill**, used with **dark text** (`--text-on-brand` = ink `#0F0F16`) on buttons/badges. Never gold text on white, never white text on gold.
- **Body links / inline emphasis** use Info-700 (`#1F8FBE`) in light, Info-300 in dark — not gold.
- All text meets **WCAG 2.2 AA** (≥4.5:1 body, ≥3:1 large/UI). Focus rings ≥3:1 against adjacent colours.
- Status never relies on colour alone — pair with icon/label (e.g. "Verified ✓", "Sold").

### 2.3 Semantic theme tokens (drive all theming)

Components reference **semantic tokens**, never raw palette. Defined as CSS variables (RGB triplets for alpha support) in `tokens/theme.css`, switched by `.dark`:

| Token | Light | Dark | Use |
|---|---|---|---|
| `--bg-base` | neutral-50 | neutral-950 | page background |
| `--bg-surface` | white | neutral-800 | cards, sheets |
| `--bg-surface-2` | neutral-100 | neutral-700 | subtle/nested |
| `--bg-sidebar` | neutral-800 | neutral-950 | left nav (dark in both modes) |
| `--border` | neutral-200 | neutral-700 | hairlines |
| `--border-strong` | neutral-300 | neutral-600 | inputs, dividers |
| `--text-strong` | neutral-900 | neutral-50 | headings |
| `--text` | neutral-700 | neutral-200 | body |
| `--text-muted` | neutral-500 | neutral-400 | captions, hints |
| `--brand` | gold-500 | gold-500 | primary fills/accents |
| `--brand-hover` | gold-600 | gold-400 | hover |
| `--text-on-brand` | neutral-900 | neutral-900 | text on gold |
| `--ring` | gold-500 | gold-400 | focus |
| `--success/-info/-danger/-warning` | base | dark variant | status |

The **sidebar stays dark in both themes** (your established pattern) — it's `--bg-sidebar`, independent of mode.

### 2.4 Theming mechanism (system-aware + toggle, no FOUC)

`darkMode: 'class'` in Tailwind. Inline head script (before paint) resolves theme from stored choice → else system. Toggle offers **System / Light / Dark** so users can hand control back to the OS.

```html
<!-- In <head>, before stylesheets, to avoid flash -->
<script>
  (function () {
    const stored = localStorage.getItem('theme'); // 'light' | 'dark' | null(system)
    const sysDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const dark = stored ? stored === 'dark' : sysDark;
    document.documentElement.classList.toggle('dark', dark);
  })();
</script>
```

```html
<!-- Alpine toggle: resources/views/components/theme-toggle.blade.php -->
<div x-data="themeToggle()" class="inline-flex rounded-full border border-[rgb(var(--border-strong))] p-0.5">
  <template x-for="opt in ['system','light','dark']" :key="opt">
    <button type="button" @click="set(opt)"
      :class="choice===opt ? 'bg-[rgb(var(--brand))] text-[rgb(var(--text-on-brand))]' : 'text-[rgb(var(--text-muted))]'"
      class="px-3 py-1 text-sm rounded-full capitalize" x-text="opt"></button>
  </template>
</div>
<script>
function themeToggle(){return{
  choice: localStorage.getItem('theme') || 'system',
  apply(){const sys=matchMedia('(prefers-color-scheme: dark)').matches;
    const dark=this.choice==='system'?sys:this.choice==='dark';
    document.documentElement.classList.toggle('dark',dark);},
  set(v){this.choice=v; v==='system'?localStorage.removeItem('theme'):localStorage.setItem('theme',v); this.apply();},
  init(){this.apply(); matchMedia('(prefers-color-scheme: dark)').addEventListener('change',()=>{if(this.choice==='system')this.apply();});}
}}
</script>
```

---

## 3. Typography

**Pairing** (self-host via Google Fonts for performance on mobile networks):
- **Display / headings — Sora** (geometric, technical confidence). Weights 600, 700.
- **Body / UI — Plus Jakarta Sans** (legible, friendlier than Inter at small sizes). Weights 400, 500, 600.
- **Numerics** — body face with `font-variant-numeric: tabular-nums` for all prices, mileage, specs, and stat figures (so columns align). Optional **IBM Plex Mono** for ref codes / VIN / chassis only.
- Fallback stack: `system-ui, -apple-system, Segoe UI, Roboto, sans-serif`.

**Type scale** (rem / line-height / weight / family):

| Token | Size | LH | Weight | Family | Use |
|---|---|---|---|---|---|
| display-2xl | 3.5 | 1.05 | 700 | Sora | hero |
| display-xl | 2.75 | 1.10 | 700 | Sora | section hero |
| display-lg | 2.25 | 1.10 | 700 | Sora | page title (lg) |
| h1 | 1.875 | 1.20 | 600 | Sora | page title |
| h2 | 1.5 | 1.25 | 600 | Sora | section |
| h3 | 1.25 | 1.30 | 600 | Sora | card title |
| h4 | 1.125 | 1.40 | 600 | Jakarta | sub-head |
| body-lg | 1.0625 | 1.6 | 400 | Jakarta | lead |
| body | 1.0 | 1.6 | 400 | Jakarta | default |
| body-sm | 0.875 | 1.55 | 400 | Jakarta | secondary |
| caption | 0.75 | 1.5 | 500 | Jakarta | meta |
| overline | 0.6875 | 1.4 | 600 | Jakarta | labels, UPPERCASE, tracking +0.08em |
| price | 1.5 | 1.1 | 700 | Jakarta | tabular-nums |

Headings use `--text-strong`; body `--text`; meta `--text-muted`.

---

## 4. Spacing, grid, radius, elevation, motion

**Spacing** — 4px base (Tailwind scale). Section rhythm: 16/24/32/48/64. Card padding: 16 (sm) / 20 (md) / 24 (lg).

**Grid** — container `max-w-screen-xl` (1280) default, `3xl` 1440 for wide dashboards; 12-col; gutter 24 desktop / 16 mobile. Listing grids: 1-col mobile → 2 (sm) → 3 (lg) → 4 (xl).

**Radius** — `sm 6 · md 10 · lg 14 · xl 20 · 2xl 28 · full`. Buttons/inputs `md`–`lg`; cards `lg`–`xl`; pills/chips `full`.

**Elevation** (light / dark variants in tokens):
- e0 none (flat on `--bg-base`)
- e1 card: `0 1px 2px rgba(8,8,16,.06), 0 1px 3px rgba(8,8,16,.10)` (dark: deeper, lower-opacity)
- e2 raised/hover; e3 dropdown/popover; e4 modal/drawer.
Dark mode leans on **borders + subtle elevation**, not heavy shadows.

**Motion** — durations 150 (micro) / 200 (default) / 300 (overlays); easing `cubic-bezier(.2,0,0,1)`. **Respect `prefers-reduced-motion`** (disable transforms/parallax, keep opacity). Hover lifts ≤2px; no bouncy springs.

**Icons** — single set (e.g. Lucide/Heroicons), 1.5–2px stroke, 20–24px. Currency/spec icons consistent across cards.

**Breakpoints** — mobile `<640` · sm `640` · md `768` · lg `1024` · xl `1280` · 2xl `1536` · 3xl `1920` (ultra-wide dashboards). **Mobile-first always** — this market is majority mobile.

---

## 5. Component library (Blade + Alpine + Tailwind)

Convention: build as Blade components in `resources/views/components/…`; interactive behaviour via Alpine; **all colours via semantic tokens** (`bg-[rgb(var(--bg-surface))]`, `text-[rgb(var(--text))]`, etc.) so light/dark "just works." Map tokens to named Tailwind utilities in `tailwind.config.js` to keep markup clean (e.g. `bg-surface`, `text-muted`, `border-base`, `text-brand`).

Each component below: **anatomy → variants → states → a11y**.

### Buttons `<x-button>`
- Variants: **primary** (gold fill, ink text), **secondary** (surface-2 fill, text), **outline** (border-strong, text), **ghost** (text only), **danger** (danger fill, white text), **whatsapp** (success-green fill, white text + WA icon).
- Sizes: sm (h-9) / md (h-11) / lg (h-12). Radius md–lg. Icon-leading/trailing supported.
- States: hover (`--brand-hover`), active (translate-y-px), focus-visible (2px ring `--ring` + offset), disabled (50% + no-pointer), **loading** (spinner, label retained, aria-busy).
- a11y: real `<button>`/`<a>`; min 44×44 touch target; label says the action ("Publish", "Show number").

### Icon button `<x-icon-button>` — square, tooltip on hover/focus, aria-label required.

### Inputs `<x-input>` / `<x-textarea>` / `<x-select>`
- Anatomy: label (overline/`--text`) · control · hint (`--text-muted`) · error (`--danger` + icon).
- Control: h-11, radius md, `bg-surface`, `border-strong`; focus → brand ring; invalid → danger border + ring.
- States: default/focus/filled/disabled/readonly/error. Error message tied via `aria-describedby`; `aria-invalid`.
- Selects: native on mobile; Alpine combobox for searchable make/model (typeahead, keyboard nav, `role=listbox`).

### Checkbox / Radio / Toggle — 20px, brand when checked, visible focus, label clickable. Toggle for Yes/No spec fields and Show-Price/Duty-Paid.

### Badges / Chips / Tags `<x-badge>`
- Status set (icon + label, never colour-only): **Featured** (gold), **Verified ✓** (success), **Unverified** (warning/outline), **New** (info), **Used** (neutral), **Sold** (danger, ribbon), **Recent Import** (info-subtle), **POA** (neutral outline), **Duty Paid** (success-subtle).
- Filter chips: removable (×), used in active-filter bar.

### Cards
- **Vehicle/listing card `<x-vehicle-card>`**: image (16:10) with photo-count badge, status ribbon, compare checkbox (overlay), wishlist heart; title (h3, make+model+year), price (price token, tabular), location, spec row (fuel · transmission · mileage · engine) with icons; primary CTA (WhatsApp) + secondary (View). Hover: e2 + 2px lift. Watermarked image.
- **Part card `<x-part-card>`**: image, title, price, vendor + verified badge, stock/fulfilment chip (FBS/COD), add-to-cart.
- **Dealer card `<x-dealer-card>`**: logo/banner, name + verified tier, listing count, location, "Visit storefront".
- **Stat / gauge tile `<x-stat-tile>`** (signature): big tabular figure, label (overline), delta (▲/▼ % with success/danger colour), thin gold accent arc/sparkline. Used across all dashboards.

### Tabs / Segmented control `<x-tabs>` — for vehicle-type tabs (Cars/Bikes/Boats/Trailers), portal sections, and "Sponsored/Recently viewed/Saved". Active = brand underline or filled pill; `role=tablist`, arrow-key nav.

### Filters
- **Desktop:** right/left rail of collapsible groups (type, make, model, year, price, fuel, transmission, body, location, dynamic features). Active-filter chip bar above results; result count live.
- **Mobile:** **bottom-sheet drawer** ("Filters" button → sheet), apply/reset, sticky footer. Never a long inline scroll.

### Pagination `<x-pagination>` — numbered + prev/next; "X–Y of N"; mobile = load-more or compact.

### Breadcrumbs `<x-breadcrumbs>` — on detail/portal deep pages; truncates on mobile.

### Modal `<x-modal>` (Alpine) — overlay e4, focus-trap, Esc to close, `role=dialog aria-modal`, returns focus to trigger. For confirm/delete, quick contact form.

### Drawer / Sheet `<x-drawer>` — right (filters/cart) or bottom (mobile actions); same a11y as modal.

### Toast `<x-toast>` — top-right (desktop) / top (mobile), success/info/danger/warning, auto-dismiss 4–6s, `role=status` (polite) / `alert` for errors. Action label matches verb ("Published").

### Tooltip `<x-tooltip>` — hover + focus, 200ms delay, `aria-describedby`; never the only source of essential info.

### Table `<x-table>` — admin/dealer data; sticky header, zebra optional, right-aligned tabular numbers, row actions menu; **collapses to stacked cards on mobile**.

### Gallery `<x-gallery>` — thumbnail strip + main image, count badge, fullscreen lightbox (Alpine), swipe on touch, keyboard arrows, download-all + WhatsApp/social share.

### Sticky contact bar `<x-contact-bar>` (detail pages, mobile) — WhatsApp (primary), Call, Show Number (masked reveal). Thumb-reachable; each action fires a tracked lead.

### Pricing widget `<x-price>` — price token + currency (USD), POA fallback ("Price on application"), optional condition/duty chips.

### Comparison table `<x-compare-table>` — sticky first column (attribute), columns per vehicle, highlight diffs, remove-column; horizontal scroll on mobile with frozen attribute column.

### Empty state `<x-empty>` — illustration/icon + one-line cause + primary action ("No listings yet — Add your first vehicle"). Active voice, never a blank table.

### Skeleton `<x-skeleton>` — shimmer placeholders for cards/tables/gallery during async; respect reduced-motion (static).

### App shell
- **Sidebar `<x-sidebar>`** — dark in both themes (`--bg-sidebar`), brand mark, role-aware nav (from one config source), active highlight, collapsible, count badges (e.g. Expiring). Mobile → off-canvas drawer.
- **Topbar `<x-topbar>`** — page title/breadcrumb, search, notifications bell, theme toggle, user menu, primary action ("+ Add Listing").
- **Wizard / stepper `<x-stepper>`** — listing editor & apply flows: step rail or progress bar, per-step validation, Draft/Publish/Delete actions in a sticky bar. Not a long scroll; **not mandatorily tabbed**.

---

## 6. Content & voice

- Sentence case everywhere; plain verbs. A control names its action and keeps that name through the flow ("Publish" → toast "Published").
- Name things the user controls ("Saved searches", "My listings"), not system internals.
- Errors state what happened + how to fix, in the interface's voice, no apologies.
- Empty screens invite action. Microcopy is design material — specific over clever.

---

## 7. Accessibility floor (WCAG 2.2, enforced)

- Colour contrast AA (see §2.2); never colour-only status.
- Full keyboard operability; visible focus (brand ring + offset) on every interactive element; logical tab order; focus-trap in overlays; skip-to-content link.
- Semantic HTML + ARIA only where needed; form labels + `aria-describedby` for hints/errors; live regions for toasts/async.
- Touch targets ≥44px; mobile-first; content reflows to 320px without horizontal scroll.
- `prefers-reduced-motion` respected; `prefers-color-scheme` honoured by default.
- Images have alt text; decorative images `alt=""`; gallery controls labelled.

---

## 8. AI coding-agent implementation guide

1. **Install tokens first:** copy `tokens/tailwind.config.js` (merge into the project config), `tokens/theme.css` (import in the main CSS, before utilities), and add the no-FOUC head script (§2.4). Wire Sora + Plus Jakarta Sans (self-hosted).
2. **Name the utilities:** the config maps semantic tokens to `bg-surface`, `bg-base`, `text-strong/text/muted`, `border-base/strong`, `text-brand`, `bg-brand`, etc. Use these in markup, not raw hex — this is what makes light/dark automatic.
3. **Build base components** in `resources/views/components/` per §5, smallest-first (button, input, badge, card), then composites (vehicle-card, stat-tile, filters, gallery, contact-bar), then shell (sidebar, topbar, stepper).
4. **Interactivity = Alpine**, server state = Blade/Livewire as already used; no React.
5. **Honour the floor** (§7) on every component — focus states, reduced-motion, touch size — don't bolt on later.
6. **Verify by rendering** in both themes and at mobile/desktop widths; check contrast; this is a UI system, so confirm the actual rendered view, not just that it compiles.
7. **Do-not list:** no gold text on light; no colour-only status; no long-scroll forms; no localStorage-less theme (must persist + follow system); no hardcoded hex outside the token files.

Screen-level composition lives in `SCREEN_SPECS.md`.

---

*Version 1.0 · Companion: SCREEN_SPECS.md, tokens/design-tokens.json, tokens/theme.css, tokens/tailwind.config.js · Status: Ready for implementation*
