import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:pocket_coach_mobile/providers/app_features_provider.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/screens/login_screen.dart';
import 'package:pocket_coach_mobile/services/token_store.dart';

void main() {
  testWidgets('login screen shows Google button when feature flag enabled', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          googleSignInEnabledProvider.overrideWithValue(true),
          tokenStoreProvider.overrideWithValue(_FakeTokenStore()),
        ],
        child: const MaterialApp(home: LoginScreen()),
      ),
    );
    await tester.pump();

    expect(find.text('Continue with Google'), findsOneWidget);
    expect(find.text('or use email'), findsOneWidget);
  });

  testWidgets('login screen hides Google button when feature flag disabled', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          googleSignInEnabledProvider.overrideWithValue(false),
          tokenStoreProvider.overrideWithValue(_FakeTokenStore()),
        ],
        child: const MaterialApp(home: LoginScreen()),
      ),
    );
    await tester.pump();

    expect(find.text('Continue with Google'), findsNothing);
    expect(find.text('or use email'), findsNothing);
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
