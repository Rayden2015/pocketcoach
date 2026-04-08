# Pocket Coach / LMS — End-to-end QA checklist

Structured test cases for QA engineers. Track pass/fail and attach screenshots or notes per row.

**Format:** Markdown was chosen over plain text so sections, tables, and lists stay readable in Git, Cursor, and GitHub.

---

## 0. Prerequisites

| # | Check |
|---|--------|
| P1 | App URL known (e.g. `http://127.0.0.1:8000`), `.env` DB migrated, optional `php artisan db:seed` for demo data. |
| P2 | Two **spaces (tenants)** available: **Space A** and **Space B**, each with at least one **published** program and course for learner tests. |
| P3 | For Paystack: keys configured; for Google sign-in: configured or explicitly test “not configured” behavior. |
| P4 | Browser + API client (Postman/Insomnia) for REST + Sanctum token tests. |

---

## 1. Platform home & space discovery

| ID | Scenario | Steps | Expected |
|----|----------|-------|----------|
| H1 | Home lists discoverable spaces | Open `/` | **200**; “Learning spaces” lists tenants that are **active** (or status null) and have **≥1 published program**. |
| H2 | Space with no published programs | Use tenant with only drafts / unpublished | **Not** listed on home (or empty grid if none qualify). |
| H3 | Space links | From home card, use **Browse catalog**, **Register**, **Log in** | Correct URLs under `/{slug}/...`. |
| H4 | Authenticated user on home | Log in; open `/` | Learner links go to **learner catalog** where applicable. |

---

## 2. Create space (new coach / owner)

| ID | Scenario | Steps | Expected |
|----|----------|-------|----------|
| CS1 | Happy path | As guest, `GET /create-space`, submit valid **space name**, **slug**, owner name/email/password | New tenant, user is **owner**, redirected to coach; flash mentions public URL. |
| CS2 | Slug validation | Submit slug matching `config/tenancy.php` **reserved** list (e.g. `login`, `api`) | Validation error, no tenant created. |
| CS3 | Duplicate slug | Same slug twice | **Unique** error on `tenants.slug`. |

---

## 3. Public catalog (guest)

| ID | Scenario | Steps | Expected |
|----|----------|-------|----------|
| PC1 | View catalog | `GET /{slug}/catalog` | Published programs/courses visible; copy explains browse without login. |
| PC2 | Unpublished hidden | Coach unpublishes program/course | Guests do **not** see unpublished items. |
| PC3 | Unknown slug | Invalid tenant slug | **404** (or your app’s not-found behavior). |

---

## 4. Registration, login, and sessions

| ID | Scenario | Steps | Expected |
|----|----------|-------|----------|
| AU1 | Space registration | `/{slug}/register` → create user | User created; **learner** membership for that tenant; logged in; redirect to **learner catalog**. |
| AU2 | Duplicate email | Register same email again | **Validation error** (global unique email). |
| AU3 | Space login | `/{slug}/login` with valid credentials | Session established; appropriate redirect. |
| AU4 | Platform gate | `GET /login` (SpaceGate) | Chooses or explains platform vs space login per UI. |
| AU5 | Logout | `POST /logout` while authenticated | Session cleared; protected routes redirect to login. |
| AU6 | Google OAuth (if enabled) | Complete Google flow from configured entry points | Account/session behavior per setup; if disabled: graceful **503** or message on API `POST /api/v1/auth/google` (see `AuthGoogleApiTest`). |

---

## 5. Multi-space: one account, multiple memberships

| ID | Scenario | Steps | Expected |
|----|----------|-------|----------|
| MS1 | Join second space | User member of Space A only; log in; open Space B **learner catalog** `/{slugB}/learn/catalog` | Banner: not a member → **Join this space** visible. |
| MS2 | Join POST | Submit join form | New `tenant_membership`; redirect to catalog; success message. |
| MS3 | Idempotent join | Join same space again | No duplicate membership; friendly “already a member” (or equivalent). |
| MS4 | Wrong path for new user | Guest tries **Register** on Space B with email **already** in `users` | **Cannot** register again → must **Log in** then **Join**. |

---

## 6. Coach (staff) — content & publishing

| ID | Scenario | Steps | Expected |
|----|----------|-------|----------|
| CO1 | Access control | Learner opens `/{slug}/coach/...` | **403** or redirect (not staff). |
| CO2 | Program CRUD | Create/edit/delete program; toggle **published** | Persists; published appears in public/learner catalog when intended. |
| CO3 | Course / module / lesson | Create hierarchy under program | Navigation and edits work; foreign tenant IDs rejected (see `CoachWebTest`). |
| CO4 | PDF lesson | Upload PDF lesson | Lesson saves; learner can open when enrolled (if applicable). |
| CO5 | API admin parity | `Bearer` token + staff: `POST/GET .../api/v1/tenants/{tenant}/admin/programs` (and courses/modules/lessons) | Matches permission rules; learner denied. |

---

## 7. Learner journey (web)

| ID | Scenario | Steps | Expected |
|----|----------|-------|----------|
| LR1 | Catalog | `/{slug}/learn/catalog` as member | Programs/courses list; enroll hints match setup. |
| LR2 | Free enroll | Course with free product: open course → **Enroll free** | Enrollment created; can open lessons. |
| LR3 | Not enrolled | Open lesson URL without enrollment | Redirect to course (or documented behavior). |
| LR4 | Lesson progress | Complete/submit progress on lesson | Persisted; refresh shows state. |
| LR5 | Continue | `/{slug}/learn/continue` | Resolves to next / appropriate lesson per logic. |
| LR6 | My learning | `GET /my-learning` | Lists enrolled courses and progress; empty state when none. |
| LR7 | Search | `GET /search` **without** auth | Redirect/login required. |
| LR8 | Search isolation | Search as user in Space A | Results **do not** include Space B courses. |

---

## 8. Payments (Paystack)

| ID | Scenario | Steps | Expected |
|----|----------|-------|----------|
| PY1 | Initialize | Authenticated **POST** `/api/v1/tenants/{tenant}/payments/paystack/initialize` for paid product | **503** if not configured; otherwise checkout payload (see `PaystackInitializeApiTest`). |
| PY2 | Free product | Initialize for free product | Rejected (not a paid checkout). |
| PY3 | Webhook | `POST /api/webhooks/paystack` with invalid signature | **Rejected**. |
| PY4 | Webhook success | Valid `charge.success` fixture | Enrollment fulfilled (`PaystackWebhookTest`). |

---

## 9. Learner API (mobile / Postman)

Use **Sanctum**: `POST /api/v1/register` or `login` → `Authorization: Bearer {token}`.

| ID | Scenario | Endpoint (examples) | Expected |
|----|----------|---------------------|----------|
| API1 | Me | `GET /api/v1/me` | User JSON; 401 without token. |
| API2 | Catalog | `GET /api/v1/tenants/{tenant}/catalog` | Published structure; 401 if not auth. |
| API3 | Join | `POST /api/v1/tenants/{tenant}/join` | Membership created; idempotent behavior consistent with web. |
| API4 | Course | `GET .../courses/{course}` | **403** without enrollment; body may include **free_product_id** when eligible; **200** with content when enrolled. |
| API5 | Progress | `PUT .../lessons/{lesson}/progress` | Valid payload updates; invalid payload **422**. |
| API6 | Learning summary | `GET .../learning-summary` | Counts / stats match completed lessons. |
| API7 | Continue | `GET .../continue` | Nullable / next lesson per enrollment state. |
| API8 | Free enroll | `POST .../enrollments/free` | Creates enrollment; idempotent; wrong tenant **404**; one-time product rejected. |
| API9 | Branding | `GET /api/v1/tenants/{tenant}/branding` | Public branding payload. |

---

## 10. Platform admin (super admin only)

| ID | Scenario | Steps | Expected |
|----|----------|-------|----------|
| PA1 | Access | Non–super-admin opens `/platform/tenants` | Denied per middleware. |
| PA2 | CRUD | Super admin lists/creates/edits tenants | Only where implemented in `TenantAdminController` routes. |

---

## 11. Regression / edge cases

| ID | Scenario | Expected |
|----|----------|----------|
| E1 | Tenant **suspended** / non-active status (if used) | Excluded from home listing; learner/coach behavior per business rules. |
| E2 | `POST` join as **guest** | Should not succeed (auth middleware). |
| E3 | API learner hits **another tenant’s** program/course IDs | **404** or forbidden, no data leak. |

---

## Suggested QA artifacts

- **Trace matrix:** map each **ID** above to a requirement or user story.
- **Data sheet:** fixed emails, slugs, and which courses are free vs paid for repeatable runs.
- **API collection:** see `docs/postman/Pocket_Coach_API.postman_collection.json`; use env vars `base_url`, `token`, `tenant_id`, `course_id`, `lesson_id`.
- **Automation (Trello / CI):** see `docs/TASK_BOARD.md` — HTTP endpoint and Artisan command to open QA tasks when features ship.
