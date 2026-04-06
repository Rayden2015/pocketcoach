class CourseDetail {
  CourseDetail({
    required this.id,
    required this.title,
    required this.slug,
    this.summary,
    required this.modules,
  });

  factory CourseDetail.fromJson(Map<String, dynamic> j) {
    final modulesRaw = j['modules'];
    final modules = <ModuleOutline>[];
    if (modulesRaw is List) {
      for (final m in modulesRaw) {
        if (m is Map<String, dynamic>) {
          modules.add(ModuleOutline.fromJson(m));
        }
      }
    }
    return CourseDetail(
      id: j['id'] as int,
      title: j['title'] as String,
      slug: j['slug'] as String,
      summary: j['summary'] as String?,
      modules: modules,
    );
  }

  final int id;
  final String title;
  final String slug;
  final String? summary;
  final List<ModuleOutline> modules;

  /// First lesson in module order, or null if none.
  LessonOutline? findLesson(int lessonId) {
    for (final m in modules) {
      for (final l in m.lessons) {
        if (l.id == lessonId) {
          return l;
        }
      }
    }
    return null;
  }

  /// Neighbor navigation: (previous, next) around [lessonId].
  (LessonOutline?, LessonOutline?) lessonNeighbors(int lessonId) {
    final flat = <LessonOutline>[];
    for (final m in modules) {
      flat.addAll(m.lessons);
    }
    final i = flat.indexWhere((l) => l.id == lessonId);
    if (i < 0) {
      return (null, null);
    }
    final prev = i > 0 ? flat[i - 1] : null;
    final next = i < flat.length - 1 ? flat[i + 1] : null;
    return (prev, next);
  }
}

class ModuleOutline {
  ModuleOutline({
    required this.id,
    required this.title,
    required this.slug,
    required this.lessons,
  });

  factory ModuleOutline.fromJson(Map<String, dynamic> j) {
    final lessonsRaw = j['lessons'];
    final lessons = <LessonOutline>[];
    if (lessonsRaw is List) {
      for (final l in lessonsRaw) {
        if (l is Map<String, dynamic>) {
          lessons.add(LessonOutline.fromJson(l));
        }
      }
    }
    return ModuleOutline(
      id: j['id'] as int,
      title: j['title'] as String,
      slug: j['slug'] as String,
      lessons: lessons,
    );
  }

  final int id;
  final String title;
  final String slug;
  final List<LessonOutline> lessons;
}

class LessonOutline {
  LessonOutline({
    required this.id,
    required this.title,
    required this.slug,
    required this.lessonType,
    required this.body,
    required this.mediaUrl,
    this.meta,
  });

  factory LessonOutline.fromJson(Map<String, dynamic> j) {
    return LessonOutline(
      id: j['id'] as int,
      title: j['title'] as String,
      slug: j['slug'] as String,
      lessonType: j['lesson_type'] as String? ?? 'text',
      body: j['body'] as String?,
      mediaUrl: j['media_url'] as String?,
      meta: j['meta'],
    );
  }

  final int id;
  final String title;
  final String slug;
  final String lessonType;
  final String? body;
  final String? mediaUrl;
  final Object? meta;
}
