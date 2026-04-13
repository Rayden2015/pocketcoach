import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:pocket_coach_mobile/providers/app_features_provider.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/screens/register_screen.dart';
import 'package:pocket_coach_mobile/services/token_store.dart';

void main() {
  testWidgets('register screen shows Google button when feature flag enabled', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          googleSignInEnabledProvider.overrideWithValue(true),
          tokenStoreProvider.overrideWithValue(_FakeTokenStore()),
        ],
        child: const MaterialApp(home: RegisterScreen()),
      ),
    );
    await tester.pump();

    expect(find.text('Sign up with Google'), findsOneWidget);
    expect(find.text('or register with email'), findsOneWidget);
  });

  testWidgets('register screen hides Google button when feature flag disabled', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          googleSignInEnabledProvider.overrideWithValue(false),
          tokenStoreProvider.overrideWithValue(_FakeTokenStore()),
        ],
        child: const MaterialApp(home: RegisterScreen()),
      ),
    );
    await tester.pump();

    expect(find.text('Sign up with Google'), findsNothing);
    expect(find.text('or register with email'), findsNothing);
  });
}

class _FakeTokenStore extends TokenStore {
  @override
  Future<String?> read() async => null;

  @override
  Future<void> write(String token) async {}

  @override
  Future<void> clear() async {}
}
