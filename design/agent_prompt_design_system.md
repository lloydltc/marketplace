# Agent Prompt — Salma Drive: Implement the Design System

> Paste into Claude Code at the project root. Stack is **Laravel Blade + Tailwind + Alpine** (no React). Build the design system, then apply it. Verify by **rendering in both themes at mobile + desktop**, not just compiling.

## Context

Implement the Salma Drive design system. **Read first:** `design/DESIGN_SYSTEM.md` (foundations + component library + implementation guide), `design/SCREEN_SPECS.md` (screen layouts), and the token files `design/tokens/design-tokens.json`, `design/tokens/theme.css`, `design/tokens/tailwind.config.js`. Also honour `UI_STANDARDS.md`.

Brand: **gold (#F0A820) primary** on deep near-black surfaces; **light + dark, system-aware** (`prefers-color-scheme`) with a toggle that can return to "follow system"; the **dark sidebar persists in both themes**; signature element is the **instrument-cluster stat tiles** on dashboards.

## Build order

1. **Tokens & theming first.**
   - Merge `tokens/tailwind.config.js` `theme.extend` into the project config; set `darkMode: 'class'`.
   - Import `tokens/theme.css` before Tailwind utilities.
   - Add the **no-FOUC head script** and the **Alpine theme toggle** (System/Light/Dark) from `DESIGN_SYSTEM.md §2.4`.
   - Self-host **Sora** (display) + **Plus Jakarta Sans** (body); enable `tabular-nums` for prices/specs.
2. **Base components** in `resources/views/components/` (smallest first): button, icon-button, input/textarea/select(searchable combobox), checkbox/radio/toggle, badge/chip, card, stat-tile, tabs, modal, drawer, toast, tooltip, pagination, breadcrumbs, table, skeleton, empty.
3. **Composite components:** vehicle-card, part-card, dealer-card, gallery(+lightbox/share/download), filters(rail + mobile bottom-sheet), contact-bar(WhatsApp/Call/Show-Number), price, compare-table, stepper/wizard.
4. **App shell:** sidebar (dark, role-aware, from one config source), topbar, dashboard layout.
5. **Screens** per `SCREEN_SPECS.md`: public (home, results, vehicle detail, dealer storefront, parts, part detail, compare) → customer portal → dealer portal → private-seller portal → admin portal.

## Rules (non-negotiable)

- **Use semantic utilities** (`bg-surface`, `bg-base`, `text-strong/text/muted`, `border-base/strong`, `text-brand`, `bg-brand`, `on-brand`) — **no raw hex in markup**; raw hex lives only in the token files. This is what makes light/dark automatic.
- **Gold is a fill with dark text**, never gold text on light, never white on gold. Body links use info-blue. (Accessibility §2.2.)
- **Accessibility floor (WCAG 2.2):** visible focus ring on every interactive element, full keyboard operability, focus-trap in overlays, 44px touch targets, `aria-*` on inputs/dialogs/toasts, reflow to 320px, **respect `prefers-reduced-motion` and `prefers-color-scheme`**.
- **No long-scroll forms** — listing editor and apply flows are steppers/wizards with Draft/Publish/Delete and per-step validation (not necessarily tabbed).
- **Status never colour-only** — pair icon + label (Verified ✓, Sold, Unverified…).
- **Interactivity = Alpine**, server state = Blade/Livewire as already used. **No React.**
- **No placeholder stats** — dashboard tiles use real, scoped, deduped queries.
- **Microcopy:** sentence case, active voice, an action keeps its name through the flow ("Publish" → "Published"); empty states invite action.

## Verify

- Render each component and screen in **light AND dark**, at **mobile and desktop** widths; confirm contrast, focus, and reduced-motion behaviour.
- Confirm the theme toggle persists and that removing the choice returns to system preference with no flash on reload.
- Don't mark a component done without showing it rendered in both themes.

## Start

Begin with **step 1 (tokens & theming)** and confirm the toggle + no-FOUC behaviour before building components.
