# Pocket Coach LMS – Functional spec (v1)

**Build this as a scalable SaaS application with clean architecture, reusable components, and optimized performance. Focus on simplicity, usability, and maintainability.**

Multi-tenant product: each **coach / teacher / organization** is a tenant with isolated catalog, learners, and monetization. Learners access content through **memberships** and **entitlements** (free, one-time purchase, or subscription).

---

## 1. Product overview

- Mobile-first web app (native apps later): structured programs, lessons, progress, optional AI coach.
- **Tenants**: coaches publish **programs → courses → modules → lessons**; set pricing per market.
- **Learners**: consume content, track progress, notes, assessments (phased).

**Primary markets:** Ghana and Nigeria — payments and SMS patterns should favor **NGN** and **GHS**, local rails (cards, bank, mobile money) via regional providers.

---

## 2. Payments (Ghana & Nigeria)

### Recommended: **Paystack** (primary)

- Strong coverage for **Nigeria** (origin) and **Ghana** (GHS settlements, local payment methods).
- Single integration for **cards, bank, mobile money** (where enabled), familiar to regional users.
- Solid API: **Transaction initialize**, **verify**, **webhooks** (`charge.success`, etc.), **Plans** for subscriptions (Phase 2).
- Laravel-friendly: HTTP JSON API; verify webhook with `x-paystack-signature` (HMAC SHA512 of raw body).

**Environment:** `PAYSTACK_SECRET_KEY`, `PAYSTACK_PUBLIC_KEY` (public key for future in-app/Paystack.js flows).

### Secondary / complementary: **Flutterwave**

- Pan-African; good if you need a second acquirer, specific MMO corridors, or redundancy.
- Abstract behind the same `PaymentGatewayInterface` before adding a second driver.

### Not first choice for hyper-local GH/NG MVP: **Stripe**

- Global-grade but **Nigeria/Ghana local methods and settlement** are weaker than Paystack/Flutterwave for typical coaching businesses in-region.

Implementation in this repo: **Paystack** for **one-time** checkout + webhook fulfillment (**MVP**). **Subscriptions** (Paystack Plans + customer subscriptions) are spec’d for Phase 2.

---

## 3. Core modules (summary)

| Area | MVP | Later |
|------|-----|--------|
| Auth | **Email/password** or **Google (Gmail)**; Sanctum (API) + session (web) | Apple Sign-In, magic link |
| Tenants & roles | Memberships (`owner`, `admin`, `instructor`, `learner`) | Invites, SSO |
| LMS structure | Program → course → module → lesson | Dripped content, versioning |
| Progress | Lesson completion, resume, notes | Video position, certificates |
| Payments | Paystack one-time, webhook enrollment | Subscriptions, coupons, Paystack split |
| AI coach | Optional service, tenant-scoped context | RAG on published lessons |
| Admin UI | API + future dashboard | Full CMS |

---

## 4. Multi-tenant data model (concept)

- **`tenants`**: slug, name, branding JSON.
- **`tenant_memberships`**: `user_id`, `tenant_id`, `role`.
- **Content**: all rows carry `tenant_id`; APIs always scope by tenant.
- **`products`**: `free` \| `one_time` \| `subscription` (+ amount, currency, linked course/program).
- **`payments`**: provider reference, status, fulfillment idempotency.
- **`enrollments`**: learner access to a course or program.

---

## 5. Technical guidelines

- **API-first** (`/api/v1/...`) for web and mobile clients.
- **PostgreSQL** recommended in production; **SQLite** acceptable for local dev.
- **Row-level tenant scoping** in every query; never trust client-supplied tenant without authZ checks.
- **Idempotent webhooks** (ignore duplicate `charge.success` for same reference).
- **Observability**: structured logs for payment and webhook paths; never log full PAN or secrets.

---

## 6. Phasing

1. **MVP:** auth, tenant + catalog schema, progress, Paystack one-time + enrollment fulfillment.
2. **Phase 2:** subscriptions, notifications, habits, richer quizzes, coach analytics.
3. **Phase 3:** React Native / Flutter, community, custom domains, advanced AI.

---

## 7. API surface

| Method | Path | Auth | Purpose |
|--------|------|------|---------|
| POST | `/api/v1/register` | — | Create user + token |
| POST | `/api/v1/login` | — | Issue token (email/password) |
| POST | `/api/v1/auth/google` | — | Issue token (`id_token` from Google Sign-In; `GOOGLE_CLIENT_ID` required) |
| POST | `/api/v1/logout` | Sanctum | Revoke token |
| GET | `/api/v1/me` | Sanctum | Current user |
| GET | `/api/v1/tenants/{slug}/catalog` | Sanctum | Published programs + courses |
| GET | `/api/v1/tenants/{slug}/continue` | Sanctum | Next incomplete lesson (enrolled) |
| GET | `/api/v1/tenants/{slug}/courses/{id}` | Sanctum | Full course content if enrolled |
| PUT | `/api/v1/tenants/{slug}/lessons/{id}/progress` | Sanctum | Notes, `position_seconds`, `completed` |
| POST | `/api/v1/tenants/{slug}/payments/paystack/initialize` | Sanctum | Start one-time payment |
| POST | `/api/v1/tenants/{slug}/enrollments/free` | Sanctum | Enroll via free product |
| * | `/api/v1/tenants/{slug}/admin/programs` | Sanctum + staff | Program CRUD (`apiResource`) |
| * | `/api/v1/tenants/{slug}/admin/courses` | Sanctum + staff | Course CRUD; index `?program_id=` |
| * | `/api/v1/tenants/{slug}/admin/modules` | Sanctum + staff | Module CRUD; index `?course_id=` |
| * | `/api/v1/tenants/{slug}/admin/lessons` | Sanctum + staff | Lesson CRUD; index `?module_id=` |
| POST | `/api/webhooks/paystack` | Signature | Fulfill paid enrollments |

**Staff roles:** `owner`, `admin`, `instructor` (see `App\Enums\TenantRole`). Refer to `routes/api.php` for verb-level routes.

---

## 8. Local development

After `php artisan migrate --seed`:

- Demo tenant slug: **`adeola`**
- Coach user: **`coach@pocketcoach.test`** (password from `User::factory`: **`password`** unless overridden in your factory).
- Sample learner: **`learner@pocketcoach.test`** (same default password), seeded with an active enrollment.

Configure Paystack test keys in `.env`, then set your Paystack dashboard webhook URL to `{APP_URL}/api/webhooks/paystack` (use **ngrok** or similar for local testing).

---

## 9. Web UI (Blade) — testing locally

The browser UI uses **session auth** (same `users` table as the API). You can exercise it **as soon as** front-end assets are built.

1. Install JS dependencies and build Vite assets: `npm install` then `npm run build` (or `npm run dev` while developing).
2. Run the app: `php artisan serve` (or `composer run dev` for server + Vite + queue + logs).
3. Visit `/` → **Register** or **Log in** (email/password, e.g. seeded `learner@pocketcoach.test` / `password`), or **Continue with Google** when `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` are set (`/auth/google` → callback).
4. **Dashboard** lists spaces you have enrollments or memberships for; open **Catalog**, **Continue**, or **Coach** (staff only).

**Public (no login):** `GET /spaces/{tenant-slug}/catalog` — published programs/courses only (e.g. `/spaces/adeola/catalog`).

**Learner URLs** (auth required): prefix `learn/{tenant-slug}/` — catalog, course, lesson, progress form.

**Coach URLs** (staff only): `coach/{tenant-slug}/…`

- Programs: full CRUD `coach/.../programs` (resource).
- Courses: `coach/.../courses?program_id=` (index), `courses/create?program_id=`, edit/destroy.
- Modules: `modules?course_id=`, `modules/create?course_id=`, etc.
- Lessons: `lessons?module_id=`, `lessons/create?module_id=`, etc.
