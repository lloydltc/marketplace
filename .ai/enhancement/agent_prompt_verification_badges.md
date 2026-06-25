# Agent Prompt — Salma Drive: Verification Badges & Reputation (Phase 2)

> Paste into Claude Code at the project root. **Extend** the existing verified/unverified system into trust tiers + reputation. Audit first; design-system-native; no AI.

## Context
Phase 2 of `master_build_roadmap.md`. **Read:** `phase2_verification_badges_task_order.md` (VB0–VB6), `BUSINESS_MODEL.md`, `design/`, prior remediation (`AUDIT_FINDINGS.md` R4/R6).

This **extends** existing work — do not rebuild: binary verified/unverified badges (R4), document/bank/identity verification (original Phase 3), the admin approval queue + `audit_logs` (R6), vendor tiers/`vendor_kind`, and reviews (if present).

## Step 1 — VB0 live audit (change nothing)
Confirm what verification fields/states, approval queue, audit log, reviews, and badge rendering already exist in the code. Write a short reconciliation (reuse vs extend vs new). **Show it, then build only the gaps.**

## Step 2 — Build VB1–VB6
- Badge **tiers** (Verified, Premium, Manufacturer-Authorized, Top-Rated, Trusted Seller) with config-driven criteria.
- Verification **dimensions** (company reg, tax, location, identity, banking) — reuse existing flows, add tax + location; staged admin approval, audited; expiry/re-verification.
- **Reputation scoring** (ratings, response time, conversion, disputes) — config-weighted, scheduled recompute, feeds Top-Rated + a subtle ranking boost.
- **Fraud controls (rule-based)**: duplicate/stolen-photo + impossible-price flags → moderation; badge **revocation/demotion** with reason + audit.
- **Surface** tier badges (icon+label) on cards/detail/storefronts; vendor "progress to next tier" view; admin badge-management dashboard.

## Rules
- Extend, don't replace; reuse `audit_logs`, notifications, existing verification flows.
- **No AI** (fraud checks are deterministic rules).
- Config-driven tier rules + weights (`platform_settings`); authorization server-side; all badge/verification actions **audit-logged**.
- Status never colour-only; design-system-native; verify by execution in both themes, mobile + desktop.

## Start
Begin with **VB0** and show the reconciliation before building.
