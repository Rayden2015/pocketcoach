import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/api/pocket_coach_api.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/services/token_store.dart';

final tokenStoreProvider = Provider<TokenStore>((ref) => TokenStore());

final sessionProvider =
    AsyncNotifierProvider<SessionNotifier, String?>(SessionNotifier.new);

class SessionNotifier extends AsyncNotifier<String?> {
  TokenStore get _store => ref.read(tokenStoreProvider);

  @override
  Future<String?> build() => ref.read(tokenStoreProvider).read();

  Future<void> login({required String email, required String password}) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() async {
      final api = ref.read(apiProvider);
      final j = await api.login(email: email, password: password);
      final t = j['token'] as String?;
      if (t == null || t.isEmpty) {
        throw StateError('No token in response');
      }
      await _store.write(t);
      return t;
    });
  }

  Future<void> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() async {
      final api = ref.read(apiProvider);
      final j = await api.register(
        name: name,
        email: email,
        password: password,
        passwordConfirmation: passwordConfirmation,
      );
      final t = j['token'] as String?;
      if (t == null || t.isEmpty) {
        throw StateError('No token in response');
      }
      await _store.write(t);
      return t;
    });
  }

  Future<void> loginWithGoogleIdToken(String idToken) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() async {
      final api = ref.read(apiProvider);
      final j = await api.loginWithGoogle(idToken: idToken);
      final t = j['token'] as String?;
      if (t == null || t.isEmpty) {
        throw StateError('No token in response');
      }
      await _store.write(t);
      return t;
    });
  }

  Future<void> logout() async {
    final t = state.valueOrNull;
    if (t != null) {
      try {
        await ref.read(apiProvider).logout(t);
      } on ApiException {
        // still clear locally
      }
    }
    await _store.clear();
    state = const AsyncData(null);
  }

  /// Drop the token without calling the API (e.g. after 401 Unauthenticated).
  Future<void> clearSessionLocally() async {
    await _store.clear();
    state = const AsyncData(null);
  }
}
