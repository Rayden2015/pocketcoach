import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:http/http.dart' as http;
import 'package:pocket_coach_mobile/api/pocket_coach_api.dart';
import 'package:pocket_coach_mobile/api/unauthorized_intercepting_client.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';

final apiProvider = Provider<PocketCoachApi>((ref) {
  final inner = http.Client();
  final client = UnauthorizedInterceptingClient(
    inner,
    () {
      unawaited(ref.read(sessionProvider.notifier).clearSessionLocally());
    },
  );
  final api = PocketCoachApi(httpClient: client);
  ref.onDispose(api.close);
  return api;
});
