# Agent Prompt — Salma Drive: Vehicle History Reports (Phase 4)

> Paste into Claude Code at the project root. Build the report module with pluggable data-source adapters. ⚠ Data-gated — ship with sources that are live; never fabricate. Audit first; design-system-native; no AI.

## Context
Phase 4 of `master_build_roadmap.md`. **Read:** `phase4_history_reports_task_order.md` (HR0–HR5), `BUSINESS_MODEL.md`, `design/`, `phase1_parts_fitment_task_order.md` (taxonomy).

Reuse: Pesepay purchase flow, canonical vehicle taxonomy (PM0), import-record/ZW fields, wallet + `audit_logs`, PDF generation.

## Step 1 — HR0 live audit + data reality check (change nothing)
Confirm Pesepay flow, taxonomy, import data, and PDF capability exist. **List which history data sources are actually obtainable** (registration/ZINARA, police clearance, insurance/accident, roadworthiness, odometer, import) — mark each available-now / partnership-needed / not-feasible. This list defines scope. **Show it before building.**

## Step 2 — Build HR1–HR5
- **Schema**: `history_reports`, `history_report_sections` (with source, confidence, provenance, retrieved-at), `history_data_sources` (pluggable adapters).
- **Adapters, staged**: implement available-now sources first (import records, your own ownership/listing data, dealer-supplied service history); stub/manual adapters for gated sources — never block the build on a partnership.
- **UX**: detail-page "History available" badge → free preview → purchase full (Pesepay) → view + **PDF download**; purchased-reports list; explicit disclaimers; honest "not available" states (no fabrication).
- **Admin & revenue**: manage sources/adapters, manual entry, moderation, refunds (audited); per-report pricing in `platform_settings`.

## Rules
- **Never fabricate data** — show provenance, confidence, and "unavailable" clearly.
- **No AI**; reuse Pesepay, taxonomy, PDF, audit log.
- Config-driven pricing; authorization server-side; actions audit-logged.
- Design-system-native; verify by execution; both themes, mobile + desktop.
- Ship with whatever sources are live; expand later — a thin honest report beats a fake comprehensive one.

## Start
Begin with **HR0** (audit + data-source reality check) and show the obtainable-sources list before building.
