import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/router/app_router.dart';
import 'package:pocket_coach_mobile/services/local_notification_service.dart';
import 'package:pocket_coach_mobile/widgets/notification_poller.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await LocalNotificationService.instance.init();
  runApp(const ProviderScope(child: PocketCoachApp()));
}

class PocketCoachApp extends ConsumerWidget {
  const PocketCoachApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);
    return MaterialApp.router(
      title: 'Pocket Coach',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF0D9488)),
        useMaterial3: true,
      ),
      builder: (context, child) {
        return NotificationPoller(child: child ?? const SizedBox.shrink());
      },
      routerConfig: router,
    );
  }
}
