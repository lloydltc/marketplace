# Salma Drive — Phase 3: Saved Searches & Smart Alerts + Comparison (v1.0)

**Roadmap position:** Phase 3 of `master_build_roadmap.md`. The engagement/retention engine. **Heavy overlap with the H-series** — this phase consolidates so nothing is built twice.

**Companion docs:** `master_build_roadmap.md`, `high_impact_features_task_order.md` (H7 #12/#13/#15), `design/`, `BUSINESS_MODEL.md`.

---

## Audit — what already exists (reuse, don't rebuild)

Documented; **confirm live in AC0**.

| Already built / specced | Where | This phase |
|---|---|---|
| Customer **saved searches** + wishlist/favorites | R1 customer portal | Reuse; add alerting |
| **Price alerts + saved-search notifications** | H7 #13 (specced) | **Merge** — build once here |
| **Compare vehicles** | H7 #12 (specced) | **Merge** — build once here |
| **Recently viewed** | H7 #15 (specced) | Reuse/relocate |
| **Notifications** (email + in-app) | original Phase 18 | Reuse as channels |
| Listing analytics events | H4/H5 | Source for price-drop detection |

**Net-new this phase:** a unified **notification/event architecture** (events → channels → preferences) reused by all later phases; smart-alert types (new listing, price drop, similar vehicle, dealer promotion); optional **push**; comparison up to 5 + printable.

### AC0 — Live audit (do first)
- [ ] Confirm what saved searches, favorites, recently-viewed, comparison, and notification plumbing already exist (and whether H7 #12/#13/#15 shipped).
- [ ] Reconciliation note: reuse vs merge vs new. Build only the gaps; if H7 items shipped, this phase extends rather than rebuilds.

---

## AC1 — Notification & event architecture (shared foundation)
- [ ] `notifications` + `notification_preferences` (user, type, channels[push|email|in_app], enabled); event → fan-out → channel dispatch (queued).
- [ ] Channels: in-app + email (reuse Phase 18); **push** (web push / FCM) — config-gated, add if feasible.
- [ ] Reusable by later phases (report-ready, inspection updates, trade-in offers).

## AC2 — Saved searches & smart alerts
- [ ] Saved search = criteria (make/model/price/year/mileage/dealer-type/location); reuse existing where present.
- [ ] Alert types: **new listings** matching, **price drops** (from analytics events), **similar vehicles**, **dealer promotions**; per-alert channel prefs; digest vs instant (config).
- [ ] Match/evaluation job (efficient, indexed); "notify me" toggles on saved searches and (Phase 1) garage vehicles.

## AC3 — Comparison tool (vehicles; pattern reused for parts)
- [ ] Side-by-side **up to 5**: specs, features, pricing, fuel economy, ownership cost; diffs highlighted.
- [ ] **Mobile-optimized** (frozen attribute column + horizontal scroll) and **printable** report.
- [ ] Add-to-compare from cards/detail; comparison tray; shareable URL. (Reuse the design-system `x-compare-table`.)

## AC4 — Recently viewed & re-engagement surfaces
- [ ] Recently-viewed (per user/session) on landing + portal; sponsored row (paid placement) reused from promotion model.

## AC5 — Validation & QA gate
- [ ] Verify by execution: save a search → receive a new-match and a price-drop alert across enabled channels; compare 5 vehicles on mobile and print; preferences honored; no duplicate alerts.
- [ ] Both themes, mobile + desktop.

**Order:** AC0 → AC1 → AC2 → AC3 → AC4 → AC5. AC3 can parallel AC1/AC2.

*Status: Ready · Phase 3 of master_build_roadmap.md*
