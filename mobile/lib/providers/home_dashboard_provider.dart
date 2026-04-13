import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/models/home_dashboard.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';

/// Aggregated learner + coach stats for the space home screen.
final homeDashboardProvider = FutureProvider.autoDispose<HomeDashboardPayload>((ref) async {
  final session = ref.watch(sessionProvider);
  final token = session.valueOrNull;
  if (token == null || token.isEmpty) {
    throw StateError('Unauthenticated');
  }
  final slug = ref.watch(tenantSlugProvider);
  return ref.read(apiProvider).fetchHomeDashboard(bearer: token, tenantSlug: slug);
});
