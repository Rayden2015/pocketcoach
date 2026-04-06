import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/api/pocket_coach_api.dart';

final apiProvider = Provider<PocketCoachApi>((ref) {
  final api = PocketCoachApi();
  ref.onDispose(api.close);
  return api;
});
