import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:pocket_coach_mobile/config/api_config.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';

/// Shared Google Sign-In → Laravel `POST /api/v1/auth/google` (id_token).
Future<void> signInWithGoogle(WidgetRef ref, BuildContext context) async {
  if (ApiConfig.googleServerClientId.isEmpty) {
    return;
  }
  FocusScope.of(context).unfocus();
  final google = GoogleSignIn(
    serverClientId: ApiConfig.googleServerClientId,
  );
  try {
    final account = await google.signIn();
    if (account == null || !context.mounted) {
      return;
    }
    final auth = await account.authentication;
    final id = auth.idToken;
    if (id == null || id.isEmpty) {
      throw StateError(
        'Google did not return an ID token. Check GOOGLE_SERVER_CLIENT_ID matches Laravel GOOGLE_CLIENT_ID (Web OAuth client).',
      );
    }
    await ref.read(sessionProvider.notifier).loginWithGoogleIdToken(id);
  } catch (e) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Google sign-in failed: $e')),
      );
    }
    return;
  }

  if (!context.mounted) {
    return;
  }
  final s = ref.read(sessionProvider);
  if (s.hasError) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Sign in failed: ${s.error}')),
    );
  }
}
