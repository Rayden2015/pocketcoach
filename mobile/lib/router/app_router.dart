import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/screens/book_coach_screen.dart';
import 'package:pocket_coach_mobile/screens/catalog_screen.dart';
import 'package:pocket_coach_mobile/screens/coach_bookings_screen.dart';
import 'package:pocket_coach_mobile/screens/course_screen.dart';
import 'package:pocket_coach_mobile/screens/home_screen.dart';
import 'package:pocket_coach_mobile/screens/lesson_screen.dart';
import 'package:pocket_coach_mobile/screens/login_screen.dart';
import 'package:pocket_coach_mobile/screens/notifications_screen.dart';
import 'package:pocket_coach_mobile/screens/profile_screen.dart';
import 'package:pocket_coach_mobile/screens/reflection_screen.dart';
import 'package:pocket_coach_mobile/screens/register_screen.dart';
import 'package:pocket_coach_mobile/screens/search_screen.dart';
import 'package:pocket_coach_mobile/screens/splash_screen.dart';
import 'package:pocket_coach_mobile/widgets/app_navigation_shell.dart';

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
        path: '/search',
        builder: (context, state) {
          final q = state.uri.queryParameters['q'];
          return SearchScreen(initialQuery: q);
        },
      ),
      GoRoute(
        path: '/notifications',
        redirect: (_, __) => '/catalog/notifications',
      ),
      GoRoute(
        path: '/reflection/:promptId',
        redirect: (c, s) => '/home/reflection/${s.pathParameters['promptId']}',
      ),
      GoRoute(
        path: '/learning/reflection/:promptId',
        redirect: (c, s) => '/home/reflection/${s.pathParameters['promptId']}',
      ),
      GoRoute(
        path: '/learning/course/:courseId',
        redirect: (c, s) => '/catalog/course/${s.pathParameters['courseId']}',
      ),
      GoRoute(
        path: '/learning/course/:courseId/lesson/:lessonId',
        redirect: (c, s) =>
            '/catalog/course/${s.pathParameters['courseId']}/lesson/${s.pathParameters['lessonId']}',
      ),
      GoRoute(
        path: '/course/:courseId/lesson/:lessonId',
        redirect: (c, s) =>
            '/catalog/course/${s.pathParameters['courseId']}/lesson/${s.pathParameters['lessonId']}',
      ),
      GoRoute(
        path: '/course/:courseId',
        redirect: (c, s) => '/catalog/course/${s.pathParameters['courseId']}',
      ),
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) {
          return AppNavigationShell(navigationShell: navigationShell);
        },
        branches: [
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/home',
                pageBuilder: (context, state) => NoTransitionPage(
                  key: state.pageKey,
                  child: const HomeScreen(),
                ),
                routes: [
                  GoRoute(
                    path: 'reflection/:promptId',
                    builder: (context, state) {
                      final id = int.parse(state.pathParameters['promptId']!);
                      return ReflectionScreen(promptId: id);
                    },
                  ),
                ],
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/catalog',
                pageBuilder: (context, state) => NoTransitionPage(
                  key: state.pageKey,
                  child: const CatalogScreen(),
                ),
                routes: [
                  GoRoute(
                    path: 'book',
                    builder: (_, __) => const BookCoachScreen(),
                  ),
                  GoRoute(
                    path: 'coach-bookings',
                    builder: (_, __) => const CoachBookingsScreen(),
                  ),
                  GoRoute(
                    path: 'notifications',
                    builder: (_, __) => const NotificationsScreen(),
                  ),
                  GoRoute(
                    path: 'course/:courseId',
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
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/profile',
                pageBuilder: (context, state) => NoTransitionPage(
                  key: state.pageKey,
                  child: const ProfileScreen(),
                ),
              ),
            ],
          ),
        ],
      ),
    ],
  );
});
