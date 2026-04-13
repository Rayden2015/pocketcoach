import 'package:flutter/widgets.dart';
import 'package:go_router/go_router.dart';

/// Route helpers for the main shell (`/home`, `/catalog`, `/profile`).
abstract final class AppPaths {
  static const String homeRoot = '/home';
  static const String catalogRoot = '/catalog';
  static const String profileRoot = '/profile';

  static String catalogCourse(int courseId) => '/catalog/course/$courseId';

  static String catalogCourseLesson(int courseId, int lessonId) =>
      '/catalog/course/$courseId/lesson/$lessonId';

  /// Lesson URL in the active shell (catalog stack holds course/lesson flows).
  static String courseLessonInCurrentBranch(
    BuildContext context,
    int courseId,
    int lessonId,
  ) {
    final path = GoRouterState.of(context).uri.path;
    if (path.startsWith(homeRoot)) {
      return catalogCourseLesson(courseId, lessonId);
    }
    return catalogCourseLesson(courseId, lessonId);
  }

  static String courseInCurrentBranch(BuildContext context, int courseId) {
    return catalogCourse(courseId);
  }

  static void goHome(BuildContext context) => context.go(homeRoot);

  static void goToCourseOverview(BuildContext context, int courseId) {
    if (context.canPop()) {
      context.pop();
    } else {
      context.go(courseInCurrentBranch(context, courseId));
    }
  }
}
