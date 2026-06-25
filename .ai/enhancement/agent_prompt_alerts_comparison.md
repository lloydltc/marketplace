# Agent Prompt — Salma Drive: Saved Searches, Smart Alerts & Comparison (Phase 3)

> Paste into Claude Code at the project root. Build the engagement engine. **Consolidate with the H-series** so nothing is built twice. Audit first; design-system-native; no AI.

## Context
Phase 3 of `master_build_roadmap.md`. **Read:** `phase3_alerts_comparison_task_order.md` (AC0–AC5), `high_impact_features_task_order.md` (H7 #12/#13/#15), `design/`, `BUSINESS_MODEL.md`.

Heavy overlap to reconcile: saved searches/favorites/recently-viewed (customer portal), **price alerts + saved-search notifications (H7 #13)**, **compare vehicles (H7 #12)**, notifications (Phase 18). Build these **once**.

## Step 1 — AC0 live audit (change nothing)
Confirm what saved searches, favorites, recently-viewed, comparison, and notification plumbing already exist, and whether H7 #12/#13/#15 shipped. Write a reconciliation (reuse vs merge vs new). **Show it, then build only the gaps.**

## Step 2 — Build AC1–AC5
- **Notification/event architecture** (shared, reused by later phases): events → channels (in-app/email/**push** if feasible) → user preferences, queued fan-out.
- **Saved searches + smart alerts**: new-match, **price drop** (from analytics events), similar vehicles, dealer promotions; per-alert channel prefs; instant vs digest.
- **Comparison** up to **5**: specs/features/pricing/economy/ownership cost, diffs highlighted, **mobile-optimized + printable**, reuse `x-compare-table`.
- **Recently viewed + sponsored** surfaces.

## Rules
- Merge with H7 items — don't duplicate; reuse existing notifications + analytics events.
- **No AI** ("similar vehicles" = deterministic similarity on taxonomy/price/body).
- Config-driven (channels, digest cadence) via `platform_settings`; efficient/indexed match jobs; no duplicate alerts.
- Design-system-native; verify by execution across channels, both themes, mobile + desktop.

## Start
Begin with **AC0**, show the reconciliation, then AC1.
