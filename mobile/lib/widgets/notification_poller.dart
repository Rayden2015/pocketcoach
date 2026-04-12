import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/services/local_notification_service.dart';

/// Polls unread count while signed in and shows a local notification when it increases.
class NotificationPoller extends ConsumerStatefulWidget {
  const NotificationPoller({super.key, required this.child});

  final Widget child;

  @override
  ConsumerState<NotificationPoller> createState() => _NotificationPollerState();
}

class _NotificationPollerState extends ConsumerState<NotificationPoller> {
  Timer? _timer;
  int? _lastUnread;

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 50), (_) => _tick());
    WidgetsBinding.instance.addPostFrameCallback((_) => _tick());
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _tick() async {
    final token = ref.read(sessionProvider).valueOrNull;
    if (token == null || token.isEmpty) {
      _lastUnread = null;
      return;
    }
    try {
      final api = ref.read(apiProvider);
      final count = await api.fetchUnreadNotificationCount(bearer: token);
      if (_lastUnread != null && count > _lastUnread!) {
        final list = await api.fetchNotifications(bearer: token);
        final unread = list.where((n) => n.isUnread).toList();
        final first = unread.isNotEmpty ? unread.first : (list.isNotEmpty ? list.first : null);
        final title = first?.title ?? 'Pocket Coach';
        final body = first?.preview ?? 'You have new notifications.';
        await LocalNotificationService.instance.showNewActivity(title: title, body: body);
      }
      _lastUnread = count;
    } catch (_) {
      /* ignore network errors */
    }
  }

  @override
  Widget build(BuildContext context) => widget.child;
}
