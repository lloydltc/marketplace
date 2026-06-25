# Agent Prompt — Salma Drive: Trade-In Valuation + Inspection Booking (Phase 5)

> Paste into Claude Code at the project root. Build two two-sided marketplaces **manual-first** (concierge pattern). Audit first; design-system-native; no AI.

## Context
Phase 5 of `master_build_roadmap.md`. **Read:** `phase5_trade_in_inspection_task_order.md` (TI0–TI5), `BUSINESS_MODEL.md`, `design/`, `phase1_parts_fitment_task_order.md` (taxonomy).

Reuse: the **concierge manual-ops pattern**, canonical taxonomy (PM0), your own listing-price data (valuation bootstrap), dealer model + verification, Pesepay/wallet/commission, notifications, leads, `audit_logs`.

## Step 1 — TI0 live audit (change nothing)
Confirm concierge pattern, taxonomy, listing-price data, dealer model, Pesepay, notifications, leads, and audit log exist. Reconciliation note — **reuse the concierge admin-queue pattern**, don't invent new ops tooling. Show it, then build.

## Step 2 — Build TI1–TI5
**Trade-in:**
- Submission (`trade_ins`) + photos; **valuation engine bootstrapped from your comparable listings** → transparent estimate range (config adjustments for mileage/condition); "estimate, not an offer".
- **Dealer bidding** (`trade_in_offers`): notify eligible/verified dealers, dealer portal to bid, buyer compares + accepts; manual-first ops queue.

**Inspection:**
- Vetted `inspectors` + `inspections` + Pesepay payment; booking flow (vehicle → inspector/slot → pay → confirm); reschedule/cancel.
- Inspector portal + **standardized report template** (checklist + photos + verdict) → buyer; buyer **rates** inspector (feeds reputation); report PDF.
- Manual dispatch/admin queue first; automate later.

**Commerce:** inspection fees + trade-in lead value via the existing money spine; pricing in `platform_settings`.

## Rules
- **Manual-first** — build shells + ops queues, prove demand, then automate. Don't over-build scheduling/matching automation up front.
- **No AI**; valuation is comparable-listing math, not ML.
- Reuse concierge pattern, Pesepay, notifications, audit log; secure photo pipeline.
- Config-driven fees; authorization server-side; actions audit-logged.
- Design-system-native; verify by execution; both themes, mobile + desktop.

## Start
Begin with **TI0**, show the reconciliation, then TI1.
