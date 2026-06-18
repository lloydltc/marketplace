# UI and UX Standards

> **AI AGENT INSTRUCTION:** Apply these standards to every UI component you create or modify. Do not redesign working interfaces without explicit business justification. Do not use generic AI-generated aesthetics — every interface should feel intentional and considered.

> **Sources:** These standards synthesise principles from *Refactoring UI* by Adam Wathan & Steve Schoger and *Practical UI* by Adham Dannaway.

---

## Core Philosophy

- **Design is not decoration.** Every visual decision must serve the user.
- **Hierarchy communicates meaning.** What is most important must look most important.
- **Constraints produce consistency.** Use a defined system — never arbitrary values.
- **Practical over perfect.** Ship a good interface rather than endlessly refine a perfect one.
- **Don't solve problems you don't have.** Build for real use cases, not imaginary edge cases.

---

## Visual Hierarchy

### The Single Most Important Rule

> Every page must have one dominant element. If everything is emphasised, nothing is.

Use a combination of: **size**, **weight**, **colour**, **contrast**, and **spacing** to establish hierarchy — not all at once.

```
Primary action    → Solid filled button, high contrast, larger size
Secondary action  → Outlined or ghost button, lower contrast
Tertiary action   → Text link or minimal button
Destructive       → Red / warning variant, never the primary CTA position
```

### De-emphasise to Create Hierarchy

> Rather than making important things louder, make unimportant things quieter.

```
Page title        → text-gray-900, text-2xl, font-semibold
Section heading   → text-gray-700, text-lg, font-medium
Body text         → text-gray-600, text-base, font-normal
Supporting text   → text-gray-400, text-sm, font-normal
Disabled text     → text-gray-300
```

### Hierarchy in Data Tables

- Use bold only for the primary identifier column (e.g. name, ID)
- Use muted text for secondary metadata (e.g. date, status label)
- Align numbers right, text left, status badges centred

---

## Spacing System

> Never use arbitrary spacing values. All spacing comes from a defined scale.

Use Tailwind's default spacing scale or define a custom one:

| Token | Value | Use |
|---|---|---|
| `space-1` | 4px | Tight internal padding |
| `space-2` | 8px | Component internal padding |
| `space-3` | 12px | Small gaps |
| `space-4` | 16px | Standard gaps |
| `space-6` | 24px | Between related sections |
| `space-8` | 32px | Between unrelated sections |
| `space-12` | 48px | Major layout sections |
| `space-16` | 64px | Page-level separation |

### Practical Rule (from *Practical UI*): Start with too much space

> When in doubt, add more whitespace. Dense interfaces feel overwhelming. You can always tighten later.

### Spacing Consistency Checklist

- [ ] Consistent padding inside all cards and panels
- [ ] Consistent gap between form fields
- [ ] Consistent vertical rhythm between page sections
- [ ] No orphaned elements floating without clear spatial relationship

---

## Typography

### Type Scale

Never use more than 4–5 distinct type sizes per interface.

```
Display   → text-3xl / text-4xl — page heroes, empty states
Heading 1 → text-2xl            — page titles
Heading 2 → text-xl             — section headings
Heading 3 → text-lg             — card headings, subsections
Body      → text-base           — all paragraph and body text
Small     → text-sm             — captions, meta, timestamps, helper text
XSmall    → text-xs             — badges, tags, fine print only
```

### Font Weight

- Use **font-semibold (600)** for headings — not bold (700) unless strong emphasis is needed
- Use **font-medium (500)** for labels and button text
- Use **font-normal (400)** for body text

### Line Height

- Body text: `leading-relaxed` (1.625) — improves readability in paragraphs
- Headings: `leading-tight` (1.25) — tighter for display text
- Never set line-height less than 1.2 for any readable text

### Practical UI Rule: Don't rely on font size alone

> Vary weight and colour alongside size. A `text-sm font-semibold text-gray-700` label reads more clearly than `text-base font-normal text-gray-400`.

---

## Colour System

### Define a Palette — Never Use Arbitrary Colours

Every project must define:

```
Primary:    5–9 shades (50 through 900) — main brand colour
Neutral:    5–9 shades (50 through 900) — greys for text, borders, backgrounds
Success:    3 shades — light (bg), medium (text/icon), dark (text on light bg)
Warning:    3 shades — light, medium, dark
Danger:     3 shades — light, medium, dark
Info:       3 shades — light, medium, dark
```

### Semantic Colour Use

| Context | Colour |
|---|---|
| Page background | `neutral-50` or `white` |
| Card / panel background | `white` or `neutral-100` |
| Input background | `white` |
| Border / divider | `neutral-200` |
| Primary text | `neutral-900` |
| Secondary text | `neutral-600` |
| Placeholder / muted | `neutral-400` |
| Primary action | `primary-600` |
| Hover state | `primary-700` |
| Success state | `green-100` bg / `green-700` text |
| Warning state | `yellow-100` bg / `yellow-700` text |
| Danger state | `red-100` bg / `red-700` text |

### Practical UI: The 60-30-10 Rule

- **60%** — neutral (backgrounds, surfaces)
- **30%** — supporting (text, borders, secondary elements)
- **10%** — accent / primary (CTAs, highlights, key indicators)

Violating this makes an interface feel visually noisy.

### Accessibility

- Text on background must meet **WCAG AA** minimum: 4.5:1 contrast ratio for normal text
- Large text (18px+ or 14px+ bold): minimum 3:1 contrast ratio
- Never use colour as the only indicator of state — always pair with text or icon

---

## Responsive and Mobile-First Design

### Breakpoints (Tailwind defaults)

| Name | Min Width | Use |
|---|---|---|
| `sm` | 640px | Large phones |
| `md` | 768px | Tablets |
| `lg` | 1024px | Laptops |
| `xl` | 1280px | Desktops |
| `2xl` | 1536px | Large screens |

### Mobile-First Rules

- Start with the mobile layout. Add complexity at larger breakpoints.
- Touch targets must be at minimum **44×44px** on mobile
- Navigation collapses to a hamburger or bottom bar on mobile
- Tables become scrollable or reflow to cards on small screens
- Font size must not be smaller than 14px on mobile

### Practical UI: Design for thumbs

> On mobile, the bottom half of the screen is the primary interaction zone. Primary actions belong there.

---

## Component Standards

### Buttons

```html
<!-- Primary — one per screen section, maximum -->
<button class="bg-primary-600 hover:bg-primary-700 text-white font-medium px-4 py-2 rounded-lg transition-colors">
  Submit Application
</button>

<!-- Secondary -->
<button class="border border-neutral-300 hover:bg-neutral-50 text-neutral-700 font-medium px-4 py-2 rounded-lg transition-colors">
  Save Draft
</button>

<!-- Destructive -->
<button class="bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2 rounded-lg transition-colors">
  Delete Record
</button>

<!-- Disabled state — always visually distinct -->
<button class="bg-neutral-200 text-neutral-400 font-medium px-4 py-2 rounded-lg cursor-not-allowed" disabled>
  Not Available
</button>
```

**Button rules:**
- Never more than one primary button per visual section
- Destructive buttons must require confirmation (modal or explicit two-step)
- Icon buttons must have `aria-label` or visible tooltip
- Loading states must provide visual feedback (`spinner + "Saving..."`)

### Forms

#### Label and Field Pairing

```html
<div class="space-y-1">
  <label for="applicant_name" class="block text-sm font-medium text-neutral-700">
    Applicant Name
  </label>
  <input
    id="applicant_name"
    type="text"
    class="block w-full border border-neutral-300 rounded-lg px-3 py-2 text-neutral-900
           placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-primary-500
           focus:border-primary-500"
    placeholder="Full legal name"
  >
  <p class="text-xs text-neutral-500">Enter the name as it appears on the national ID.</p>
</div>
```

**Form rules (Refactoring UI):**
- Labels always above the field — never placeholder-as-label
- Supporting text below the field in muted colour
- Error messages below the field in `red-600`
- Required fields marked explicitly — not just an asterisk with no explanation
- Group related fields visually (same card, same indentation level)
- Logical tab order

**Form rules (Practical UI):**
- Remove optional fields where possible — simplify the form
- If a field is optional, label it "(optional)" explicitly
- Inline validation on blur, not on every keystroke
- The submit button must reflect what happens: "Submit Application" not just "Submit"
- Place the primary CTA at the bottom right — secondary action bottom left or as a text link

#### Input States

```
Default   → border-neutral-300
Focus     → ring-2 ring-primary-500, border-primary-500
Error     → border-red-500, ring-red-500 (with error message below)
Disabled  → bg-neutral-100, text-neutral-400, cursor-not-allowed
Success   → border-green-500 (use sparingly — only on critical fields)
```

### Cards

```html
<div class="bg-white border border-neutral-200 rounded-xl p-6 shadow-sm">
  <h3 class="text-base font-semibold text-neutral-900">Card Title</h3>
  <p class="mt-1 text-sm text-neutral-600">Supporting description text here.</p>
</div>
```

**Card rules:**
- Consistent padding across all cards — pick one value and stick to it
- Use `shadow-sm` — not `shadow-lg` for standard cards (save elevation for modals)
- Rounded corners: `rounded-xl` (12px) for cards, `rounded-lg` (8px) for inputs
- Never nest cards more than two levels deep

### Tables

```html
<div class="overflow-x-auto">
  <table class="w-full text-sm">
    <thead>
      <tr class="border-b border-neutral-200">
        <th class="text-left font-medium text-neutral-500 px-4 py-3">Name</th>
        <th class="text-left font-medium text-neutral-500 px-4 py-3">Status</th>
        <th class="text-right font-medium text-neutral-500 px-4 py-3">Amount</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-neutral-100">
      <tr class="hover:bg-neutral-50 transition-colors">
        <td class="px-4 py-3 font-medium text-neutral-900">John Doe</td>
        <td class="px-4 py-3 text-neutral-600">
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
            Approved
          </span>
        </td>
        <td class="px-4 py-3 text-neutral-900 text-right tabular-nums">$12,500.00</td>
      </tr>
    </tbody>
  </table>
</div>
```

**Table rules:**
- `tabular-nums` on numeric columns — prevents layout shift on scroll
- Right-align numbers, left-align text, centre-align status badges
- Zebra striping or row hover — not both at once
- Column headers in `text-neutral-500 font-medium` — clearly distinguishable from data

### Badges / Status Chips

```html
<!-- Pattern: bg-[colour]-100 text-[colour]-700 -->
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Pending</span>
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Rejected</span>
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-neutral-100 text-neutral-600">Draft</span>
```

### Modals / Dialogs

```
Structure:
  Backdrop (semi-transparent overlay)
  Dialog container (max-w-md to max-w-lg, centred, rounded-2xl, shadow-xl)
    Header (title + optional close button)
    Body (content — scrollable if long)
    Footer (actions — primary right, cancel left)
```

**Modal rules (Practical UI):**
- Modals are for actions requiring user decision — not for displaying information
- Always include a close/dismiss path (button AND clicking backdrop)
- Destructive confirmation modals must restate what will be deleted
- Never open a modal from inside another modal

---

## Feedback and Empty States

### Loading States

- Skeleton screens preferred over spinners for content areas
- Spinners are acceptable for button actions and small loading indicators
- Never show a loading spinner for more than 10 seconds without feedback

### Empty States

```html
<div class="flex flex-col items-center justify-center py-16 text-center">
  <!-- Icon or illustration -->
  <svg class="w-12 h-12 text-neutral-300 mb-4" ...></svg>
  <h3 class="text-base font-semibold text-neutral-700">No applications yet</h3>
  <p class="mt-1 text-sm text-neutral-500 max-w-xs">
    Applications submitted by staff will appear here. Start by creating a new application.
  </p>
  <button class="mt-4 ...">Create Application</button>
</div>
```

**Empty state rules (Practical UI):**
- Every empty state must explain WHY it's empty and WHAT to do next
- Include a CTA when there is a clear next action
- Never show a raw "No data found." message

### Error States

- **Inline form errors:** Below the field, `text-red-600 text-sm`
- **Page-level errors:** Banner at top of page with icon + message + action
- **Network / server errors:** Full-page error component with retry option
- **404 / 403:** Dedicated pages with navigation back to safety

### Toast Notifications

```
Success  → bg-green-600 / icon: check-circle
Warning  → bg-yellow-500 / icon: exclamation-triangle
Error    → bg-red-600    / icon: x-circle
Info     → bg-blue-600   / icon: information-circle
```

- Auto-dismiss after 4–6 seconds for success/info
- Do NOT auto-dismiss errors — user must acknowledge
- Position: top-right on desktop, bottom-centre on mobile

---

## Dashboard Standards

### Layout Principles

```
Full-width top: Key metrics / KPI cards (3–4 across)
Below: Primary data table or chart (full width or 2/3 + sidebar)
Below: Secondary tables, activity feeds, etc.
```

### KPI / Metric Cards

```html
<div class="bg-white border border-neutral-200 rounded-xl p-5">
  <p class="text-sm font-medium text-neutral-500">Total Applications</p>
  <p class="mt-2 text-3xl font-semibold text-neutral-900">1,284</p>
  <p class="mt-1 text-sm text-green-600 font-medium">↑ 12% from last month</p>
</div>
```

**Dashboard rules (Refactoring UI + Practical UI):**
- Metrics that matter most get the largest visual weight
- Trend indicators must always say direction AND magnitude
- Avoid chartjunk — every element in a chart must earn its presence
- Use colour to signal significance (red for decline, green for growth) — not just for decoration

---

## Navigation

### Sidebar Navigation

```
Active state  → bg-primary-50 text-primary-700 font-medium border-l-2 border-primary-600
Inactive      → text-neutral-600 hover:bg-neutral-50
Icons         → 20px, consistent stroke width, left-aligned with label
```

### Breadcrumbs

- Required for pages 3+ levels deep
- Current page is not a link
- Truncate on mobile (show only parent + current)

---

## Accessibility Checklist

- [ ] All images have `alt` text (empty `alt=""` for decorative images)
- [ ] All interactive elements are keyboard-navigable
- [ ] Focus states are visible (`focus:ring-2` never removed without replacement)
- [ ] Form fields are associated with labels via `for`/`id`
- [ ] Colour contrast meets WCAG AA
- [ ] ARIA labels on icon-only buttons
- [ ] Error messages linked to fields via `aria-describedby`
- [ ] No content conveyed by colour alone

---


  ## Colour palette 
  - #F0A820
  - #2EBD7A
  - #3DB8E8
  - #D4295A
  - #5A6070
  - #C8CDD6
  - #1A1A24
  - #080810

## logo
- logos are in src/public/logo
- same logo but with different backgrounds

## tagline 
- "Find It. Buy It. Drive It."

## Strategic foundation
- Built to own the digital-first automotive retail space in Zimbabwe, leveraging Salma Technology's brand equity and ICT capabilities as a structural competitive moat.

##  vision 
- To be Zimbabwe's most trusted virtual auto marketplace — where every Zimbabwean can buy, sell, or upgrade their vehicle and accessories confidently online.

## mission 
- To eliminate friction in the Zimbabwean automotive market by connecting buyers and sellers through a secure, transparent, technology-powered platform.

## Positioning
- Premium-accessible. Not a cheap classifieds board — a curated, trust-verified marketplace. Premium enough for new cars; accessible enough for second-hand deals and small parts orders.


## Practical UI: The "Good Enough" Rule

> A design that ships and works beats a perfect design that never leaves Figma.

Apply this test before over-refining:
1. Can the user complete their task?
2. Does the interface clearly communicate what to do next?
3. Is the visual hierarchy immediately obvious?

If yes to all three — ship it.
