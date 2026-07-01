# Salma Drive — Phase 4: Vehicle History Reports (v1.0)

**Roadmap position:** Phase 4 of `master_build_roadmap.md`. A revenue add-on. ⚠ **Data/partnership-gated** — the engineering is ~30%, the data partnerships are ~70%. Build the module; bootstrap from data you own; add external sources as they're secured. **Start partnership conversations in parallel; do not block the build.**

**Companion docs:** `master_build_roadmap.md`, `BUSINESS_MODEL.md`, `design/`, `phase1_parts_fitment_task_order.md` (taxonomy).

---

## Audit — what already exists (reuse, don't rebuild)

Documented; **confirm live in HR0**.

| Already built / specced | Where | This phase |
|---|---|---|
| **Pesepay** payment + purchase flows | BUSINESS_MODEL / Phase 11 | Reuse for report purchase |
| **Canonical vehicle taxonomy** | Phase 1 PM0 | Key reports to vehicles |
| **Recent Import / import** field | ZW fields (H2) | A first data source |
| Wallet/commission, **audit logging** | BUSINESS_MODEL / R6 | Reuse |
| PDF generation capability | (verify) | Render full report |

**Net-new this phase:** report schema + assembly; staged data-source adapters; preview vs full; purchase flow; admin management; revenue model.

### HR0 — Live audit + data-source reality check (do first)
- [ ] Confirm Pesepay purchase flow, taxonomy, import-record data, and PDF capability exist.
- [ ] **List which data sources are actually obtainable** in Zimbabwe (registration/ZINARA, police clearance, insurance/accident, roadworthiness, odometer history, import records) — note for each: available now / partnership needed / not feasible. This list drives scope.
- [ ] Reconciliation note + a "sources live at launch" set.

---

## HR1 — Report module & schema
- [ ] `history_reports` (id, vehicle_ref|vin|plate, requested_by, status[draft|ready|purchased], price_minor, created_at)
- [ ] `history_report_sections` (id, report_id, source, type[registration|ownership|accident|insurance|service|odometer|police_clearance|import|roadworthiness], data JSON, confidence, retrieved_at)
- [ ] `history_data_sources` (id, name, type, adapter, status[live|manual|unavailable], config) — pluggable adapters.

## HR2 — Source adapters (staged by availability)
- [ ] Adapter interface; implement **available-now** sources first: **import records, your own listing/ownership data, dealer-supplied service history** (sellers attach service records).
- [ ] Stub/manual adapters for gated sources (registration, police clearance, insurance, roadworthiness) — fill via manual ops or future API; never block the build on them.
- [ ] Per-section **confidence + provenance + retrieved-at**; clear "not available" states (no fabrication).

## HR3 — Purchase & UX
- [ ] Detail-page integration: **"History available"** badge + **report preview** (free summary) → **purchase full report** (Pesepay).
- [ ] Full report view + **PDF download** (use the pdf skill/capability); buyer's purchased reports list.
- [ ] Disclaimers (data accuracy, source limitations) — explicit, no overclaiming.

## HR4 — Admin & revenue
- [ ] Admin: manage data sources/adapters, manual section entry, report moderation, refunds; audited.
- [ ] Revenue model: per-report price (config); optional dealer-bundled reports; pricing in `platform_settings`.

## HR5 — Validation & QA gate
- [ ] Verify by execution: a report assembles from live sources, previews, purchases via Pesepay, renders + downloads as PDF; unavailable sources show honestly; pricing from config; actions audited.
- [ ] Both themes, mobile + desktop.

**Order:** HR0 → HR1 → HR2 → HR3 → HR4 → HR5.
**Reality reminder:** ship with whatever sources are live; expand over time. A thin honest report beats a fake comprehensive one.

*Status: **COMPLETE** (HR0–HR5) · Phase 4 of master_build_roadmap.md · full suite 679 green*

---

## Completion log (HR0–HR5)

Built the module honestly on obtainable sources; gated sources ship registered-but-unavailable (never fabricated). No AI.

**HR0 obtainable-sources verdict:** available-now = import record (ZW fields+VIN), platform ownership/listing history (incl. AC2 price history), dealer-supplied service (manual), odometer (seller-declared). partnership-needed = registration/ZINARA, police clearance/ZRP, roadworthiness/VID. not-feasible-now = insurance/accident. **PDF:** no library present → full report is print-optimised HTML (browser Save-as-PDF); real PDF lib is a later drop-in.

| Task | Delivered |
|------|-----------|
| HR1 | `history_reports`, `history_report_sections` (source/confidence/provenance/retrieved_at), `history_data_sources` (adapters) |
| HR2 | Adapter interface + available-now adapters (import/platform/odometer/service-manual) + gated stubs; `ReportAssembler` (honest unavailable, preserves manual entries, config-priced) |
| HR3 | Detail-page badge → free preview (locked/unavailable states) → Pesepay purchase (free = instant) → full view + print-PDF + purchased list |
| HR4 | Admin source status, manual service entry, refunds (audited); price in platform_settings |
| HR5 | End-to-end gate; 679 green |

*Status: Ready → DELIVERED (with live sources; expand via partnerships).*
