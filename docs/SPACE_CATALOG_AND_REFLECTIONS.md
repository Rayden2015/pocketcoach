# Space catalog brief, featured content, and daily reflections

## Tenant `settings` JSON (optional)

Merge into `tenants.settings` (per row). Keys:

```json
{
  "catalog": {
    "intro_markdown": "## Welcome\nSpace-specific brief shown above programs.",
    "track_catalog_views": true,
    "show_featured_first": true
  },
  "reflections": {
    "enabled": true,
    "notify_email": true,
    "notify_database": true
  }
}
```

- **`catalog.intro_markdown`** — Shown on `/{slug}/catalog`. If omitted, **`branding.welcome_headline`** is used when present.
- **`catalog.track_catalog_views`** — When true, learner clicks from the public catalog increment `courses.catalog_view_count` (used for “popular” ordering).
- **`catalog.show_featured_first`** — Featured programs/courses appear before others.
- **`reflections.enabled`** — Hide reflection UI when false.
- **`reflections.notify_email`** / **`notify_database`** — Channels when a coach **publishes** a new reflection prompt (queued `Notification` per learner).

Push/mobile (FCM) is not wired yet; the mobile app can poll **`GET /api/v1/notifications`** and **`GET /api/v1/notifications/unread-count`**.

Queued mail/database notifications require a worker: **`php artisan queue:work`** (or `sync` in `.env` for local only).

## Coach tools

- Mark **programs** / **courses** as **Featured** (public catalog ordering).
- **Coach → Daily reflections** — create prompts; check **Published** and save to notify learners (email + in-app DB notifications when enabled).

## Learner tracking

- **`reflection_prompt_views`** — `first_viewed_at` / `last_viewed_at` when the user opens a prompt.
- **`reflection_responses`** — `first_submitted_at` set when they first save a reflection; body can be edited later.
