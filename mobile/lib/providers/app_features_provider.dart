import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/config/api_config.dart';

/// Whether the Google Sign-In button should show (compile-time `GOOGLE_SERVER_CLIENT_ID`).
/// Override in tests: `googleSignInEnabledProvider.overrideWithValue(true)`.
final googleSignInEnabledProvider = Provider<bool>(
  (ref) => ApiConfig.googleServerClientId.isNotEmpty,
);
