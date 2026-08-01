# Pocket Coach (Flutter)

Starter **cross-platform** app that talks to the Laravel API (`/api/v1`, Sanctum bearer tokens).

## Prerequisites

- [Flutter SDK](https://docs.flutter.dev/get-started/install) (stable channel)

## Bootstrap platform folders

This repo includes `pubspec.yaml` and `lib/` only. Generate Android / iOS / Web targets once:

```bash
cd mobile
flutter create . --org com.pocketcoach.app --project-name pocket_coach_mobile
```

This keeps existing `pubspec.yaml` and adds `android/`, `ios/`, etc.

## Configure API base URL

Default is `http://127.0.0.1:8000/api` (works for **iOS Simulator** and **desktop**).

| Target | Typical `API_BASE_URL` |
|--------|-------------------------|
| Android emulator | `http://10.0.2.2:8000/api` |
| Physical device | `http://<your-lan-ip>:8000/api` |
| iOS Simulator | `http://127.0.0.1:8000/api` |

Run with defines (see Google Sign-In below):

```bash
flutter run \
  --dart-define=API_BASE_URL=http://10.0.2.2:8000/api \
  --dart-define=GOOGLE_SERVER_CLIENT_ID=YOUR_WEB_CLIENT_ID.apps.googleusercontent.com
```

### Google Sign-In (mobile)

Use the **Web application** OAuth client ID from Google Cloud (the same value as Laravel `GOOGLE_CLIENT_ID`) as `GOOGLE_SERVER_CLIENT_ID` so `google_sign_in` returns an `id_token` your API can verify.

After `flutter create`, configure the native Google Sign-In setup (iOS `REVERSED_CLIENT_ID` in Info.plist, Android `default_web_client_id` in strings if needed — follow [google_sign_in](https://pub.dev/packages/google_sign_in) docs).

The UI shows the Google button when `GOOGLE_SERVER_CLIENT_ID` is non-empty at build time (`ApiConfig` / `googleSignInEnabledProvider`). For **widget tests**, override `googleSignInEnabledProvider` in `ProviderScope` so `flutter test` does not need that define; see `test/login_screen_test.dart` and `test/register_screen_test.dart`.

Ensure the Laravel app accepts requests from the device (firewall, `php artisan serve --host=0.0.0.0` if needed).

**Android (HTTP to your machine):** After `flutter create`, allow cleartext for local dev by adding `android:usesCleartextTraffic="true"` on the `<application>` element in `android/app/src/main/AndroidManifest.xml`. Use HTTPS in production.

## Run

```bash
cd mobile
flutter pub get
flutter run
```

Log in with a valid user. Set the **space slug** (tenant slug, same as web `/spaces/{slug}`) from the catalog toolbar; it is stored in `SharedPreferences`.

## App structure

| Tech | Role |
|------|------|
| **flutter_riverpod** | Session, tenant slug, catalog/course/continue providers |
| **go_router** | `/splash`, `/login`; shell: `/catalog`, `/learning`, `/profile` + nested course/lesson, notifications, reflection |
| **flutter_markdown** | Lesson body |
| **video_player** | In-app video / audio lessons |
| **webview_flutter** | YouTube embeds + PDF preview |
| **url_launcher** | Opens external resources when needed |

Screens: **Catalog**, **Continue**, **Course**, **Lesson** (in-app media preview + notes), **Lesson studio** (full-window media with notes beside / below, fullscreen, image pinch-zoom), **Reflection** (tap image for fullscreen zoom).

API: `GET .../catalog`, `GET .../continue`, `GET .../courses/{id}`, `PUT .../lessons/{id}/progress` (notes, completion, `content_progress_percent`, `position_seconds`).

### Media studio (mobile ↔ web parity)

Image, video, audio, and PDF lessons with a `media_url` show an in-app preview and **Open studio**. Studio route:

`/catalog/course/{courseId}/lesson/{lessonId}/studio`

- Large media stage + notes panel (side-by-side on wide layouts; stacked on phones)
- Immersive fullscreen toggle
- Pinch-to-zoom for images
- Debounced progress including video/audio position

## Deploy to stores

See **[DEPLOYMENT.md](DEPLOYMENT.md)** for Play Store / App Store setup, production `--dart-define` flags, signing, TestFlight rollout, and the release checklist.
