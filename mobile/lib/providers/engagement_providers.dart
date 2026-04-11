import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/models/engagement_models.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';

final notificationsProvider = FutureProvider.autoDispose<List<UserNotificationItem>>((ref) async {
  final token = await ref.watch(sessionProvider.future);
  if (token == null || token.isEmpty) {
    return [];
  }
  return ref.read(apiProvider).fetchNotifications(bearer: token);
});

final unreadNotificationCountProvider = FutureProvider.autoDispose<int>((ref) async {
  final token = await ref.watch(sessionProvider.future);
  if (token == null || token.isEmpty) {
    return 0;
  }
  return ref.read(apiProvider).fetchUnreadNotificationCount(bearer: token);
});

final reflectionLatestProvider = FutureProvider.autoDispose<ReflectionPrompt?>((ref) async {
  final token = await ref.watch(sessionProvider.future);
  if (token == null || token.isEmpty) {
    return null;
  }
  final slug = ref.watch(tenantSlugProvider);
  return ref.read(apiProvider).fetchReflectionLatest(bearer: token, tenantSlug: slug);
});

final reflectionPromptProvider = FutureProvider.autoDispose.family<ReflectionPrompt, int>((ref, id) async {
  final token = await ref.watch(sessionProvider.future);
  if (token == null || token.isEmpty) {
    throw StateError('Unauthenticated');
  }
  final slug = ref.watch(tenantSlugProvider);
  return ref.read(apiProvider).fetchReflectionPrompt(
        bearer: token,
        tenantSlug: slug,
        promptId: id,
      );
});
