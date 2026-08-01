import 'dart:io';

import 'package:flutter_local_notifications/flutter_local_notifications.dart';

/// OS-level alerts when unread count increases (reflections, messages, etc.).
class LocalNotificationService {
  LocalNotificationService._();
  static final instance = LocalNotificationService._();

  final FlutterLocalNotificationsPlugin _plugin = FlutterLocalNotificationsPlugin();

  static const _androidChannel = AndroidNotificationChannel(
    'pocket_coach_general',
    'Pocket Coach',
    description: 'Course, reflection, and message notifications',
    importance: Importance.defaultImportance,
  );

  Future<void> init() async {
    const androidInit = AndroidInitializationSettings('@mipmap/ic_launcher');
    const darwinInit = DarwinInitializationSettings();
    await _plugin.initialize(
      const InitializationSettings(
        android: androidInit,
        iOS: darwinInit,
        macOS: darwinInit,
      ),
    );

    if (Platform.isAndroid) {
      final impl = _plugin.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>();
      await impl?.createNotificationChannel(_androidChannel);
      await impl?.requestNotificationsPermission();
    }
    if (Platform.isIOS) {
      await _plugin
          .resolvePlatformSpecificImplementation<IOSFlutterLocalNotificationsPlugin>()
          ?.requestPermissions(alert: true, badge: true, sound: true);
    }
    if (Platform.isMacOS) {
      await _plugin
          .resolvePlatformSpecificImplementation<MacOSFlutterLocalNotificationsPlugin>()
          ?.requestPermissions(alert: true, badge: true, sound: true);
    }
  }

  Future<void> showNewActivity({required String title, required String body}) async {
    final android = AndroidNotificationDetails(
      _androidChannel.id,
      _androidChannel.name,
      channelDescription: _androidChannel.description,
      importance: Importance.defaultImportance,
      priority: Priority.defaultPriority,
    );
    const darwin = DarwinNotificationDetails();
    final details = NotificationDetails(android: android, iOS: darwin, macOS: darwin);
    await _plugin.show(
      DateTime.now().millisecondsSinceEpoch % 100000,
      title,
      body,
      details,
    );
  }
}
