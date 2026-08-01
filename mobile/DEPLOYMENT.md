# Pocket Coach mobile — deployment plan

Deploy the **learner Flutter app** against production API at `https://pocketcoach.africa/api` (or your staging URL). The Laravel web app must be live first; mobile is a Sanctum API client only.

---

## 1. Test status (pre-release gate)

Run before every store submission:

```bash
# Automated
cd mobile && flutter test
cd .. && php artisan test --filter='LearnLesson|LearnerEngagement'

# Production API smoke (replace token flow with your prod test account)
curl -s -X POST https://pocketcoach.africa/api/v1/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"YOUR_TEST_USER","password":"YOUR_PASSWORD"}'
```

| Gate | Command / check | Pass criteria |
|------|-----------------|---------------|
| Flutter unit + widget | `flutter test` | 16/16 (includes studio + media helpers) |
| Web lesson/studio | `php artisan test --filter=LearnLesson` | 10/10 |
| API login + course | `GET /api/v1/tenants/{slug}/courses/{id}` | 200 + lessons with `media_url` |
| API progress | `PUT /api/v1/tenants/{slug}/lessons/{id}/progress` | 200 + persisted notes/% |
| Release build | see §4–5 below | IPA/AAB builds without error |

**Manual QA on device** (recommended each release):

1. Log in (email + Google if enabled).
2. Set space slug → open catalog → enroll → open **image lesson** → **Open studio** → pinch zoom, save notes, mark complete.
3. Open **video lesson** studio → YouTube embed plays, notes visible.
4. Reflection with image → tap for fullscreen zoom.
5. Notifications permission prompt → background poll (optional).

---

## 2. Backend prerequisites

Mobile depends on the same Laravel deploy as web (`deploy.sh` on cPanel). Confirm on production:

| Item | Production value |
|------|------------------|
| `APP_URL` | `https://pocketcoach.africa` |
| API base (mobile) | `https://pocketcoach.africa/api` |
| HTTPS | Required (no cleartext in release builds) |
| Sanctum | Bearer tokens via `POST /api/v1/login`, `POST /api/v1/auth/google` |
| `storage:link` | Media URLs resolve (`media_disk_path` → public storage) |
| CORS | Not required for native app (direct HTTPS to API) |
| Google OAuth | `GOOGLE_CLIENT_ID` set; Web client ID passed to mobile as `GOOGLE_SERVER_CLIENT_ID` |

No separate mobile backend deploy — ship API + web first, then point the app at production.

---

## 3. Build-time configuration

All production settings are **compile-time** `--dart-define` flags (see `lib/config/api_config.dart`):

| Define | Production example | Required |
|--------|-------------------|----------|
| `API_BASE_URL` | `https://pocketcoach.africa/api` | Yes |
| `WEB_BASE_URL` | `https://pocketcoach.africa` | Optional (defaults from API URL) |
| `GOOGLE_SERVER_CLIENT_ID` | Same as Laravel `GOOGLE_CLIENT_ID` | If Google sign-in enabled |

**Versioning** (`pubspec.yaml`):

```yaml
version: 1.0.0+1   # name+build — increment +N every store upload
```

- **Android:** `versionCode` = build number (`+1`)
- **iOS:** `CFBundleVersion` = build number, `CFBundleShortVersionString` = name

**App identifiers (current):**

| Platform | ID |
|----------|-----|
| Android | `com.pocketcoach.app.pocket_coach_mobile` |
| iOS | `com.pocketcoach.app.pocketCoachMobile` |

Align display name in stores: **Pocket Coach** (currently “Pocket Coach Mobile” on iOS).

---

## 4. Android (Google Play)

### 4.1 One-time setup

1. **Google Play Console** — create app, complete store listing, content rating, privacy policy URL (`https://pocketcoach.africa/privacy` or equivalent).
2. **Upload keystore** — generate once, store securely (password manager + backup):

   ```bash
   keytool -genkey -v -keystore pocketcoach-upload.jks \
     -keyalg RSA -keysize 2048 -validity 10000 \
     -alias pocketcoach
   ```

3. **Signing config** — replace debug signing in `android/app/build.gradle.kts`:

   ```kotlin
   // android/key.properties (gitignored)
   storePassword=...
   keyPassword=...
   keyAlias=pocketcoach
   storeFile=/path/to/pocketcoach-upload.jks
   ```

4. **Google Sign-In** — in Google Cloud Console, add **Android** OAuth client:
   - Package name: `com.pocketcoach.app.pocket_coach_mobile`
   - SHA-1 from upload keystore: `keytool -list -v -keystore pocketcoach-upload.jks`

5. **Remove cleartext** — ensure `android:usesCleartextTraffic` is **not** set in release `AndroidManifest.xml` (HTTPS only).

### 4.2 Release build

```bash
cd mobile
flutter pub get
flutter build appbundle \
  --release \
  --dart-define=API_BASE_URL=https://pocketcoach.africa/api \
  --dart-define=GOOGLE_SERVER_CLIENT_ID=YOUR_WEB_CLIENT_ID.apps.googleusercontent.com
```

Output: `build/app/outputs/bundle/release/app-release.aab` → upload to Play Console → **Internal testing** first → Production.

### 4.3 Play rollout

| Phase | Audience | Duration |
|-------|----------|----------|
| Internal | Team (≤100 testers) | 1–2 days |
| Closed beta | Selected coaches/learners | 1 week |
| Production | Staged rollout 10% → 50% → 100% | 1–2 weeks |

---

## 5. iOS (App Store / TestFlight)

### 5.1 One-time setup

1. **Apple Developer Program** ($99/yr) — enroll org/account.
2. **App Store Connect** — new app, bundle ID `com.pocketcoach.app.pocketCoachMobile`.
3. **Certificates & profiles** — Xcode → Signing & Capabilities → Team, Automatic signing for dev; **Distribution** cert + App Store profile for release.
4. **Google Sign-In iOS** — in Google Cloud, add **iOS** OAuth client with bundle ID; add `REVERSED_CLIENT_ID` URL scheme to `ios/Runner/Info.plist` (see [google_sign_in](https://pub.dev/packages/google_sign_in) iOS setup).
5. **Privacy** — `NSUserNotificationsUsageDescription` already in Info.plist; complete App Privacy questionnaire in Connect (data linked to user: email, notes, progress).

### 5.2 Release build

```bash
cd mobile
flutter pub get
flutter build ipa \
  --release \
  --dart-define=API_BASE_URL=https://pocketcoach.africa/api \
  --dart-define=GOOGLE_SERVER_CLIENT_ID=YOUR_WEB_CLIENT_ID.apps.googleusercontent.com
```

Or archive in Xcode: `open ios/Runner.xcworkspace` → Product → Archive → Distribute → App Store Connect.

### 5.3 TestFlight → App Store

| Phase | Steps |
|-------|--------|
| TestFlight internal | Upload build; add internal testers; verify login + studio |
| TestFlight external | Optional beta group (up to 10k) |
| App Review | Screenshots (6.7", 6.5", iPad if supported), description, review notes with test account |
| Release | Manual or automatic after approval |

**Review notes template:**

> Test account: learner@… / password …  
> Space slug: adeola  
> Media studio: Catalog → Atomic starters → “Studio E2E — Image chart” → Open studio.

---

## 6. CI/CD

Workflows in `.github/workflows/`:

| Workflow | Trigger | Purpose |
|----------|---------|---------|
| **mobile-ci.yml** | Push/PR touching `mobile/` | `flutter analyze`, `flutter test`, debug APK compile |
| **mobile-release.yml** | Manual (**Actions → Mobile Release → Run workflow**) | Signed AAB and/or IPA artifacts |

### GitHub repository secrets

**Android (required for release workflow)**

| Secret | Value |
|--------|--------|
| `ANDROID_KEYSTORE_BASE64` | `base64 -i pocketcoach-upload.jks \| pbcopy` |
| `ANDROID_KEYSTORE_PASSWORD` | Keystore password |
| `ANDROID_KEY_PASSWORD` | Key password |
| `ANDROID_KEY_ALIAS` | e.g. `pocketcoach` |

**iOS (required for IPA job)**

| Secret | Value |
|--------|--------|
| `IOS_BUILD_CERTIFICATE_BASE64` | Distribution `.p12`, base64 |
| `IOS_P12_PASSWORD` | Certificate export password |
| `IOS_PROVISION_PROFILE_BASE64` | App Store `.mobileprovision`, base64 |
| `IOS_KEYCHAIN_PASSWORD` | Ephemeral keychain password (any strong random string) |

**Optional (both platforms)**

| Secret | Value |
|--------|--------|
| `GOOGLE_SERVER_CLIENT_ID` | Laravel `GOOGLE_CLIENT_ID` (Web OAuth client) |

Edit `mobile/ios/ExportOptions.plist` → `provisioningProfiles` → match your App Store profile **name** exactly.

### Local release script

```bash
cd mobile
chmod +x scripts/build-release.sh   # once
API_BASE_URL=https://pocketcoach.africa/api \
GOOGLE_SERVER_CLIENT_ID=your-id.apps.googleusercontent.com \
./scripts/build-release.sh android
```

Copy `android/key.properties.example` → `android/key.properties` before a signed local Android build.

---

## 7. Release checklist

### Before build

- [ ] `flutter test` green
- [ ] Bump `pubspec.yaml` version (`1.0.1+2`)
- [ ] Changelog / release notes drafted
- [ ] Production API smoke-tested
- [ ] Google OAuth clients include Android SHA-1 + iOS bundle ID

### Build

- [ ] `API_BASE_URL=https://pocketcoach.africa/api` in release command
- [ ] `GOOGLE_SERVER_CLIENT_ID` set if Google login shipped
- [ ] Android: signed with upload keystore (not debug)
- [ ] iOS: distribution profile, archive succeeds

### Store submission

- [ ] Privacy policy URL live
- [ ] Screenshots + description updated (mention media studio, offline notes sync)
- [ ] Test account credentials in review notes
- [ ] Internal / TestFlight pass on real device

### After release

- [ ] Monitor Sentry/crash reports (if added to Flutter)
- [ ] Verify login + studio on production from installed build
- [ ] Announce to coaches/learners (optional)

---

## 8. Staging environment (optional)

For pre-prod testing without touching production stores:

```bash
flutter run --release \
  --dart-define=API_BASE_URL=https://staging.pocketcoach.africa/api \
  --dart-define=GOOGLE_SERVER_CLIENT_ID=...
```

Use **Internal testing** / **TestFlight** builds pointed at staging before switching defines to production for the store build.

---

## 9. Known gaps before v1.0 store launch

| Item | Status | Action |
|------|--------|--------|
| Android release signing | `key.properties.example` + Gradle hook | Copy to `key.properties`, add keystore |
| iOS Google URL scheme | May be missing | Add `REVERSED_CLIENT_ID` to Info.plist |
| App icons / splash | Default Flutter | Replace with Pocket Coach branding |
| Crash reporting | Not wired in Flutter | Optional: Sentry Flutter SDK |
| Push notifications (FCM/APNs) | Local notifications only | Future: server push for coach messages |
| macOS desktop | Builds for dev | Out of scope for App Store v1 |

---

## 10. Quick reference commands

```bash
# Dev (local API)
cd mobile && flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000/api

# Android emulator → host machine
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api

# Physical device on LAN
flutter run --dart-define=API_BASE_URL=http://192.168.x.x:8000/api

# Production release builds
flutter build appbundle --release \
  --dart-define=API_BASE_URL=https://pocketcoach.africa/api \
  --dart-define=GOOGLE_SERVER_CLIENT_ID=...

flutter build ipa --release \
  --dart-define=API_BASE_URL=https://pocketcoach.africa/api \
  --dart-define=GOOGLE_SERVER_CLIENT_ID=...
```

Web deploy remains: `bash deploy.sh` on the server (see repo root). Mobile deploy is **store-only** — no cPanel step for the Flutter binary.
