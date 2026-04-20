import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/models/booking_models.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';

final bookableCoachesProvider = FutureProvider.autoDispose<List<BookableCoach>>((ref) async {
  final slug = ref.watch(tenantSlugProvider);
  return ref.read(apiProvider).fetchBookableCoaches(tenantSlug: slug);
});

final bookingSlotsProvider =
    FutureProvider.autoDispose.family<List<BookingSlot>, int>((ref, coachUserId) async {
  final slug = ref.watch(tenantSlugProvider);
  return ref.read(apiProvider).fetchBookingSlots(tenantSlug: slug, coachUserId: coachUserId);
});

final coachBookingsProvider = FutureProvider.autoDispose<List<CoachBookingRow>>((ref) async {
  final token = await ref.watch(sessionProvider.future);
  if (token == null || token.isEmpty) {
    return [];
  }
  final slug = ref.watch(tenantSlugProvider);
  return ref.read(apiProvider).fetchCoachBookings(bearer: token, tenantSlug: slug);
});
