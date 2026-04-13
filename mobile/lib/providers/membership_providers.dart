import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';
import 'package:pocket_coach_mobile/providers/user_provider.dart';

/// Membership rows from `GET /api/v1/me` (empty while loading / error).
final userMembershipsProvider = Provider<List<Map<String, dynamic>>>((ref) {
  final u = ref.watch(currentUserProvider);
  return u.maybeWhen(
    data: (user) {
      final m = user['memberships'];
      if (m is List) {
        return m.map((e) => Map<String, dynamic>.from(e as Map)).toList();
      }
      return [];
    },
    orElse: () => [],
  );
});

/// Ensures [tenantSlugProvider] matches a valid membership when possible (runs after `me` loads).
final membershipSlugAlignmentProvider = FutureProvider.autoDispose<void>((ref) async {
  final user = await ref.watch(currentUserProvider.future);
  final m = user['memberships'];
  if (m is! List || m.isEmpty) {
    return;
  }
  final list = m.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  await ref.read(tenantSlugProvider.notifier).alignWithMemberships(list);
});
