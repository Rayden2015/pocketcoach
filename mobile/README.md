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
| **go_router** | `/splash`, `/login`, `/home` (tabs), `/course/:id`, nested `/lesson/:id` |
| **flutter_markdown** | Lesson body |
| **url_launcher** | Opens `media_url` in the browser |

Screens: **Catalog** (programs → courses), **Continue** (next lesson), **Course** (modules/lessons), **Lesson** (markdown, notes, progress, prev/next).

API: `GET .../catalog`, `GET .../continue`, `GET .../courses/{id}`, `PUT .../lessons/{id}/progress`.
