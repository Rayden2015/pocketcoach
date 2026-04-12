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
    final idRaw = j['id'];
    final cid = idRaw is int ? idRaw : (idRaw is num ? idRaw.toInt() : int.parse(idRaw.toString()));
    return CourseDetail(
      id: cid,
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
    final midRaw = j['id'];
    final mid = midRaw is int ? midRaw : (midRaw is num ? midRaw.toInt() : int.parse(midRaw.toString()));
    return ModuleOutline(
      id: mid,
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

class LessonProgressSnapshot {
  LessonProgressSnapshot({
    this.completedAt,
    this.notes,
    this.notesIsPublic = false,
    this.positionSeconds,
    this.contentProgressPercent,
  });

  factory LessonProgressSnapshot.fromJson(Map<String, dynamic> j) {
    final ps = j['position_seconds'];
    final cpp = j['content_progress_percent'];
    final nip = j['notes_is_public'];
    return LessonProgressSnapshot(
      completedAt: j['completed_at'] as String?,
      notes: j['notes'] as String?,
      notesIsPublic: nip is bool ? nip : (nip == true),
      positionSeconds: ps is int ? ps : (ps is num ? ps.toInt() : null),
      contentProgressPercent: cpp is int ? cpp : (cpp is num ? cpp.toInt() : null),
    );
  }

  final String? completedAt;
  final String? notes;
  final bool notesIsPublic;
  final int? positionSeconds;
  final int? contentProgressPercent;

  bool get isComplete =>
      completedAt != null && completedAt!.isNotEmpty;
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
    this.progress,
  });

  factory LessonOutline.fromJson(Map<String, dynamic> j) {
    LessonProgressSnapshot? pr;
    final prRaw = j['progress'];
    if (prRaw is Map<String, dynamic>) {
      pr = LessonProgressSnapshot.fromJson(prRaw);
    }
    final lidRaw = j['id'];
    final lid = lidRaw is int ? lidRaw : (lidRaw is num ? lidRaw.toInt() : int.parse(lidRaw.toString()));
    return LessonOutline(
      id: lid,
      title: j['title'] as String,
      slug: j['slug'] as String,
      lessonType: j['lesson_type'] as String? ?? 'text',
      body: j['body'] as String?,
      mediaUrl: j['media_url'] as String?,
      meta: j['meta'],
      progress: pr,
    );
  }

  final int id;
  final String title;
  final String slug;
  final String lessonType;
  final String? body;
  final String? mediaUrl;
  final Object? meta;
  final LessonProgressSnapshot? progress;
}
