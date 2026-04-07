import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/screens/course_screen.dart';
import 'package:pocket_coach_mobile/screens/lesson_screen.dart';
import 'package:pocket_coach_mobile/screens/login_screen.dart';
import 'package:pocket_coach_mobile/screens/main_tabs_screen.dart';
import 'package:pocket_coach_mobile/screens/register_screen.dart';
import 'package:pocket_coach_mobile/screens/splash_screen.dart';

final rootNavigatorKey = GlobalKey<NavigatorState>(debugLabel: 'root');

final routerProvider = Provider<GoRouter>((ref) {
  final refresh = ValueNotifier<int>(0);
  ref.onDispose(refresh.dispose);
  ref.listen<AsyncValue<String?>>(sessionProvider, (_, __) {
    refresh.value++;
  });

  return GoRouter(
    navigatorKey: rootNavigatorKey,
    initialLocation: '/splash',
    refreshListenable: refresh,
    redirect: (context, state) {
      final loc = state.matchedLocation;
      final session = ref.read(sessionProvider);

      if (session.isLoading) {
        if (loc != '/splash') {
          return '/splash';
        }
        return null;
      }

      if (session.hasError) {
        if (loc != '/login') {
          return '/login';
        }
        return null;
      }

      final token = session.valueOrNull;
      final authed = token != null && token.isNotEmpty;

      if (!authed) {
        if (loc == '/splash') {
          return '/login';
        }
        if (loc == '/login' || loc == '/register') {
          return null;
        }
        return '/login';
      }

      if (authed && (loc == '/login' || loc == '/splash' || loc == '/register')) {
        return '/home';
      }

      return null;
    },
    routes: [
      GoRoute(
        path: '/splash',
        builder: (_, __) => const SplashScreen(),
      ),
      GoRoute(
        path: '/login',
        builder: (_, __) => const LoginScreen(),
      ),
      GoRoute(
        path: '/register',
        builder: (_, __) => const RegisterScreen(),
      ),
      GoRoute(
        path: '/home',
        builder: (_, __) => const MainTabsScreen(),
      ),
      GoRoute(
        path: '/course/:courseId',
        builder: (context, state) {
          final id = int.parse(state.pathParameters['courseId']!);
          return CourseScreen(courseId: id);
        },
        routes: [
          GoRoute(
            path: 'lesson/:lessonId',
            builder: (context, state) {
              final cid = int.parse(state.pathParameters['courseId']!);
              final lid = int.parse(state.pathParameters['lessonId']!);
              return LessonScreen(courseId: cid, lessonId: lid);
            },
          ),
        ],
      ),
    ],
  );
});
