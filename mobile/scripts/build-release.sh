#!/usr/bin/env bash
#
# Local release builds (same dart-defines as CI).
#
#   cd mobile
#   API_BASE_URL=https://pocketcoach.africa/api \
#   GOOGLE_SERVER_CLIENT_ID=your-client-id.apps.googleusercontent.com \
#   ./scripts/build-release.sh android
#
# Android signing: copy android/key.properties.example → android/key.properties
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

API_BASE_URL="${API_BASE_URL:-https://pocketcoach.africa/api}"
PLATFORM="${1:-android}"

DART_DEFINES=( "--dart-define=API_BASE_URL=${API_BASE_URL}" )
if [[ -n "${GOOGLE_SERVER_CLIENT_ID:-}" ]]; then
  DART_DEFINES+=( "--dart-define=GOOGLE_SERVER_CLIENT_ID=${GOOGLE_SERVER_CLIENT_ID}" )
fi

flutter pub get

case "$PLATFORM" in
  android)
    if [[ ! -f android/key.properties ]]; then
      echo "warning: android/key.properties missing — release will use debug signing." >&2
      echo "         Copy android/key.properties.example and add your upload keystore." >&2
    fi
    flutter build appbundle --release "${DART_DEFINES[@]}"
    echo "AAB: build/app/outputs/bundle/release/app-release.aab"
    ;;
  ios)
    (cd ios && pod install)
    flutter build ipa --release "${DART_DEFINES[@]}" --export-options-plist=ios/ExportOptions.plist
    echo "IPA: build/ios/ipa/"
    ;;
  both)
    "$0" android
    "$0" ios
    ;;
  *)
    echo "usage: $0 [android|ios|both]" >&2
    exit 1
    ;;
esac
