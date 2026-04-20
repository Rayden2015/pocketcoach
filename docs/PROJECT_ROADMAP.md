# Pocket Coach — project roadmap (client reference)

Commercial timeline and budget bands are **planning estimates** (GHS). Technical delivery should be reconciled with the current codebase and sprint capacity.

| Phase | Scope / features | Timeline | Est. cost (GHS) |
|-------|-------------------|----------|-----------------|
| **Phase 1: MVP (core platform)** | User registration & login; onboarding & goal selection; course structure (programs → lessons); basic dashboard (progress tracking); content consumption (video, text); admin panel (basic content upload); clean modern UI (mobile-first web) | 4–6 weeks | 12,000–18,000 |
| **Phase 2: AI coach + engagement** | AI chat (Pocket Coach); context-aware responses (basic); daily recommendations; progress-based suggestions; notifications (in-app + email); improved dashboard insights | 3–4 weeks | 8,000–12,000 |
| **Phase 3: Monetization & growth** | Subscription plans; payment integration (e.g. Paystack / Flutterwave); content locking (free vs premium); user analytics; admin reporting dashboard | 2–3 weeks | 6,000–9,000 |
| **Phase 4: Mobile app (cross-platform)** | Mobile app using Flutter; API integration with web backend; mobile-optimized UI/UX; push notifications; app store readiness (Android first, then iOS) | 4–6 weeks | 10,000–16,000 |
| **Phase 5: Advanced features (scale)** | Habit tracking; streak system; advanced AI coaching (behavior insights); community features (optional); performance optimization & scaling | 4–6 weeks | 8,000–15,000 |

---

## Calendar & appointment booking (client request — ASAP)

**Status:** Not in the original phase list above. Treat as a **scoped add-on** or **insert** (see below).

### How we can deliver it

1. **MVP booking (fastest path)**  
   - Coach defines **availability** (recurring blocks + exceptions) and **session types** (length, title).  
   - Learner (or client) **picks a slot** from published availability.  
   - **Confirmations** via email + in-app notification (reuse existing notification patterns where possible).  
   - **Coach / learner views**: list upcoming appointments, cancel/reschedule rules, timezone handling.

2. **Integrations (adds time & cost)**  
   - **Google Calendar / Outlook** sync (one-way or two-way).  
   - **Paid deposits** for bookings (ties to Phase 3 payments — can front-load a thin Paystack “booking hold” if required).

3. **Where it lives in the product**  
   - **Web:** New area under tenant context, e.g. `/{tenant}/book` (learner) and `/{tenant}/coach/appointments` or `…/schedule` (coach), aligned with existing path-based tenancy.  
   - **Mobile:** New **Book** entry (tab or home card) calling the same Laravel API; optional deep link from notifications.

### Where it sits on the roadmap

| Option | When | Trade-off |
|--------|------|-----------|
| **A — Phase 1 extension** | Before signing off MVP | Delays Phase 1 “done” date; MVP + booking ship together. |
| **B — Fast follow (recommended for ASAP)** | Immediately after Phase 1 core is stable (1–2 sprints) | Clear MVP boundary; booking ships with predictable scope. |
| **C — Fold into Phase 2** | With engagement / notifications | Good if booking is tightly coupled to reminders and AI nudges. |

**Recommendation:** **B** unless the contract explicitly redefines Phase 1 to include booking.

### Effect on pricing (GHS)

Booking is **additive** — it does not replace a phase; it adds **scope, calendar logic, UX, and often compliance edge cases** (timezones, cancellations, no-shows).

| Booking scope | Indicative add-on (GHS) | Extra weeks (indicative) |
|---------------|-------------------------|---------------------------|
| **Lean MVP** (availability + book + list + email/in-app notify, web only) | **+4,000–8,000** | **+2–3** |
| **Web + mobile parity** | **+6,000–12,000** | **+3–5** |
| **+ Calendar sync (Google) or paid deposits** | **+3,000–8,000+** on top of the row above | **+1–3** |

These bands should be confirmed after a **1-page spec**: who books whom (coach→client vs self-serve), max participants, cancellation policy, and whether payments apply.

---

## Related internal docs

- `docs/POCKET_COACH_SPEC.md` — product / technical spec  
- `docs/SPACE_CATALOG_AND_REFLECTIONS.md` — tenant learn / catalog behavior  

---

*Last updated: roadmap table per client share; calendar section added for scheduling discussion.*
