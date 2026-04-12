import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';

/// Current user from `GET /api/v1/me`. Refetches when the session token changes.
final currentUserProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final session = ref.watch(sessionProvider);
  final token = session.valueOrNull;
  if (token == null || token.isEmpty) {
    throw StateError('Unauthenticated');
  }
  return ref.read(apiProvider).fetchMe(bearer: token);
});
