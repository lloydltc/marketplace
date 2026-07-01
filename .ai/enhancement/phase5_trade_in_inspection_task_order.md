# Salma Drive — Phase 5: Trade-In Valuation + Inspection Booking (v1.0)

**Roadmap position:** Phase 5 of `master_build_roadmap.md`. Two **two-sided, ops-heavy** marketplaces. ⚠ Run **manual-first** (like your concierge model); automate when volume justifies. Don't over-build automation before there's supply on both sides.

**Companion docs:** `master_build_roadmap.md`, `BUSINESS_MODEL.md`, `design/`, `phase1_parts_fitment_task_order.md` (taxonomy), concierge precedent.

---

## Audit — what already exists (reuse, don't rebuild)

Documented; **confirm live in TI0**.

| Already built / specced | Where | This phase |
|---|---|---|
| **Concierge** manual-ops pattern | BUSINESS_MODEL §6 | Template for both marketplaces |
| **Canonical vehicle taxonomy** | Phase 1 PM0 | Valuation keying |
| **Own listing price data** | listings | Bootstrap valuation engine |
| Vendor/**dealer** model + verification | core / Phase 2 | Dealer bidding eligibility |
| **Pesepay**, wallet, commission | BUSINESS_MODEL | Inspection payment, fees |
| **Notifications**, leads, **audit_logs** | Phase 18 / H4 / R6 | Reuse |

**Net-new:** trade-in submission + valuation engine + dealer bidding; inspection booking + inspector portal + report template + ratings.

### TI0 — Live audit (do first)
- [ ] Confirm concierge pattern, taxonomy, listing-price data, dealer model, Pesepay, notifications, leads, audit log exist.
- [ ] Reconciliation note; reuse the concierge admin-queue pattern rather than inventing new ops tooling.

---

## TRADE-IN VALUATION (F6)

### TI1 — Submission & valuation engine
- [ ] `trade_ins` (id, user_id, vehicle taxonomy refs, year, mileage, condition, photos, status)
- [ ] **Valuation engine** = bootstrap from your own comparable listings (same make/model/generation/year band) → estimated range; config-driven adjustments (mileage, condition). Transparent "estimate, not an offer."
- [ ] Photo upload (secure pipeline reuse).

### TI2 — Dealer bidding
- [ ] `trade_in_offers` (id, trade_in_id, dealer_id, amount_minor, notes, status, expires_at).
- [ ] Notify eligible/verified dealers (reuse notifications); dealer portal to view submissions + bid; buyer compares offers + accepts; lead/handoff recorded.
- [ ] Manual-first ops queue (admin can shepherd) before any automation.

## INSPECTION BOOKING (F4)

### TI3 — Inspection marketplace & booking
- [ ] `inspectors` (vetted panel: company/mechanic/expert, coverage area, rating), `inspections` (id, vehicle_ref, buyer_id, inspector_id, slot, status, price_minor), payment via Pesepay.
- [ ] Booking flow: pick vehicle/listing → choose inspector/slot → pay → confirmation; reschedule/cancel rules.

### TI4 — Inspector portal & report
- [ ] Inspector portal: assigned inspections, status updates, **standardized inspection report template** (structured checklist + photos + verdict), submit to buyer.
- [ ] Buyer **rates** inspector (feeds reputation); report stored + downloadable (PDF).
- [ ] Manual dispatch/admin queue first; automate scheduling later.

## TI5 — Commerce, fees & validation
- [ ] Inspection fees + trade-in lead value via existing money spine (`BUSINESS_MODEL.md`); pricing in `platform_settings`.
- [ ] Verify by execution: submit trade-in → estimate → dealer bids → accept; book + pay inspection → inspector report → rate; fees correct; actions audited.
- [ ] Both themes, mobile + desktop.

**Order:** TI0 → TI1 → TI2 → TI3 → TI4 → TI5. Trade-in (TI1/TI2) and inspection (TI3/TI4) can parallelise after TI0.
**Reality reminder:** both are manual-first; build the shells + ops queues, prove demand, then automate.

*Status: **COMPLETE** (TI0–TI5) · Phase 5 of master_build_roadmap.md · full suite 694 green*

---

## Completion log (TI0–TI5)

Built manual-first on the concierge ops pattern; no AI (valuation = comparable-listing math).

| Task | Delivered |
|------|-----------|
| TI0 | Reconciliation: reused concierge SM/queue pattern, PM0 taxonomy, own listing-price data, Vendor+VB, Pesepay, AC1 notifications, leads, audit_logs |
| TI1 | `trade_ins`/`trade_in_photos`/`trade_in_offers`; ValuationService (median comparables + config mileage/condition adjust → range, "estimate not an offer", null when insufficient); buyer submit + estimate |
| TI2 | Dealer bidding: notify approved dealers (in-app+email), bid portal, buyer compare/accept (declines rest, notifies winner, audited), admin ops queue |
| TI3 | `inspectors`/`inspections`; admin panel; buyer booking → Pesepay pay (free=instant; fee from platform_settings) → confirm; cancel |
| TI4 | Inspector portal (linked user): assigned jobs + standardized checklist/verdict report → buyer notified; buyer views + print + rates inspector (feeds reputation) |
| TI5 | Fees in platform_settings; end-to-end gate; 694 green |

*Status: Ready → DELIVERED (manual-first; automate scheduling/matching when volume justifies).*
