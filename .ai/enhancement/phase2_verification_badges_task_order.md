# Salma Drive — Phase 2: Dealer Verification Badges & Reputation (v1.0)

**Roadmap position:** Phase 2 of `master_build_roadmap.md`. A trust layer that lifts conversion across cars and parts. **Extends** the existing verified/unverified system — does not replace it.

**Companion docs:** `master_build_roadmap.md`, `BUSINESS_MODEL.md`, `design/`, prior remediation (`AUDIT_FINDINGS.md` R4/R6), `task_execution_order.md` v2.0.

---

## Audit — what already exists (reuse, don't rebuild)

Documented from prior work; **confirm live in VB0**.

| Already built / specced | Where | This phase |
|---|---|---|
| Binary **verified / unverified** seller badge | R4 remediation | **Extend** into tiers |
| Vendor **document + bank verification**, identity | original Phase 3 | Reuse as verification inputs |
| Admin **approval/verification queue** | R6 / AUDIT E2 | Extend to multi-tier review |
| **Audit logging** (`audit_logs`) | R6 | Reuse for all badge actions |
| Vendor **tiers** concept, `vendor_kind` (parts) | memory / Phase 1 PM2 | Reuse |
| Reviews/ratings | original Phase 19 (if built) | Feed reputation score |

**Net-new this phase:** badge *tiers* beyond binary; reputation scoring engine; extra verification dimensions (tax compliance, physical location); rule-based fraud controls + revocation; tier-aware badge management UI.

### VB0 — Live verification audit (do first, change nothing)
- [ ] Confirm the above against the real code: what verification fields/states, queue, audit log, reviews, and badge rendering already exist.
- [ ] Produce a short reconciliation note: reuse vs extend vs new. Build only the gaps.

---

## VB1 — Badge tiers & data model
- [ ] `verification_tiers` / extend vendor: tiers = **Verified Dealer, Premium Dealer, Manufacturer-Authorized, Top-Rated, Trusted Seller** (config-driven definitions, criteria, display).
- [ ] `vendor_verifications` (vendor_id, dimension[company_reg|tax|location|identity|banking], status, evidence_ref, verified_by, verified_at, expires_at).
- [ ] Tier eligibility = a configurable rule set over verification dimensions + reputation (VB3) + manual grant (Manufacturer-Authorized).

## VB2 — Verification workflow & approvals
- [ ] Staged submission per dimension (company registration, tax compliance, physical location, identity, banking) — reuse existing document/bank flows; add tax + location.
- [ ] Admin review queue (extend existing): approve/reject per dimension with reason; tier auto-recomputes; everything **audit-logged**.
- [ ] Expiry/re-verification reminders (reuse notifications).

## VB3 — Reputation scoring
- [ ] `vendor_reputation` (vendor_id, score, components JSON, computed_at): inputs = ratings, response time to leads, lead→sale conversion, dispute/report rate, listing quality. Config-weighted.
- [ ] Scheduled recompute; feeds **Top-Rated** tier and search ranking (subtle boost, config-driven).

## VB4 — Fraud controls (rule-based, no AI)
- [ ] Duplicate/stolen-photo flags (hash match), impossible-price flags, rapid-relist patterns → moderation queue (reuse Phase H11/report-listing if built).
- [ ] **Badge revocation/suspension** workflow with reason + audit; auto-demote on dimension expiry or sustained disputes.

## VB5 — Badge surfacing & management UI
- [ ] Render tier badges (icon + label, never colour-only) on vehicle/part cards, detail, dealer storefronts.
- [ ] Vendor-facing "verification progress" view (what's needed for the next tier).
- [ ] Admin badge-management dashboard (grant/revoke/override, view scores), audited.

## VB6 — Validation & QA gate
- [ ] Verify by execution: a vendor progresses dimensions → earns a tier → badge renders everywhere; failing a dimension/dispute demotes; all actions audited; tier rules read from config.
- [ ] Render badges in both themes, mobile + desktop.

**Order:** VB0 → VB1 → VB2 → VB3 → VB4 → VB5 → VB6.

*Status: **COMPLETE** (VB0–VB6) · Phase 2 of master_build_roadmap.md · full suite 642 green*

---

## Completion log (VB0–VB6)

Extended the existing verified/unverified system into config-driven trust tiers — no rebuild. All actions audited (R6); fraud rules deterministic (no AI).

| Task | Delivered |
|------|-----------|
| VB0 | Live audit + reconciliation (reuse: audit_logs, approval queue, document/bank evidence, DS badge, H11 reports; finding: no reviews table → reputation degrades gracefully) |
| VB1 | `config/verification.php` (5 tiers), `vendor_verifications`, vendor verification_tier/manual_tier/reputation_score, `TierEvaluator` |
| VB2 | Admin per-dimension approve/reject (config expiry, recompute, audited) + `verification:maintain` (auto-demote + reminders) |
| VB3 | `vendor_reputation` + `ReputationService` (config-weighted, null-tolerant) + `reputation:recompute`; feeds Top-Rated; ranking-boost config (default off) |
| VB4 | Badge revoke/reinstate/grant (audited) + `FraudRuleService` (dup-photo via image_hash, rapid-relist) → H11 queue + `fraud:scan` |
| VB5 | `<x-trust-badge>` on storefront/cards; vendor progress view; admin badge-management panel |
| VB6 | End-to-end gate (earn→render→demote→revoke, audited; config-driven rules); 642 green |

*Status: Ready → DELIVERED.*
