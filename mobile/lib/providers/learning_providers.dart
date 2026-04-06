import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/models/catalog_models.dart';
import 'package:pocket_coach_mobile/models/continue_learning.dart';
import 'package:pocket_coach_mobile/models/course_detail.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';

final catalogProvider = FutureProvider.autoDispose<List<CatalogProgram>>((ref) async {
  final token = await ref.watch(sessionProvider.future);
  if (token == null || token.isEmpty) {
    return [];
  }
  final slug = ref.watch(tenantSlugProvider);
  return ref.read(apiProvider).fetchCatalog(bearer: token, tenantSlug: slug);
});

final courseDetailProvider = FutureProvider.autoDispose.family<CourseDetail, int>((
  ref,
  courseId,
) async {
  final token = await ref.watch(sessionProvider.future);
  if (token == null || token.isEmpty) {
    throw StateError('Unauthenticated');
  }
  final slug = ref.watch(tenantSlugProvider);
  return ref.read(apiProvider).fetchCourse(
    bearer: token,
    tenantSlug: slug,
    courseId: courseId,
  );
});

final continueLearningProvider =
    FutureProvider.autoDispose<ContinueLearningPayload?>((ref) async {
  final token = await ref.watch(sessionProvider.future);
  if (token == null || token.isEmpty) {
    return null;
  }
  final slug = ref.watch(tenantSlugProvider);
  return ref.read(apiProvider).fetchContinue(bearer: token, tenantSlug: slug);
});
