class ContinueLearningPayload {
  ContinueLearningPayload({
    required this.course,
    required this.lesson,
    this.progress,
  });

  factory ContinueLearningPayload.fromJson(Map<String, dynamic> j) {
    final course = j['course'];
    final lesson = j['lesson'];
    if (course is! Map<String, dynamic> || lesson is! Map<String, dynamic>) {
      throw FormatException('continue payload shape');
    }

    ContinueProgress? progress;
    final p = j['progress'];
    if (p is Map<String, dynamic>) {
      progress = ContinueProgress.fromJson(p);
    }

    return ContinueLearningPayload(
      course: ContinueCourse.fromJson(course),
      lesson: ContinueLesson.fromJson(lesson),
      progress: progress,
    );
  }

  final ContinueCourse course;
  final ContinueLesson lesson;
  final ContinueProgress? progress;
}

class ContinueCourse {
  ContinueCourse({
    required this.id,
    required this.title,
    required this.slug,
  });

  factory ContinueCourse.fromJson(Map<String, dynamic> j) {
    return ContinueCourse(
      id: j['id'] as int,
      title: j['title'] as String,
      slug: j['slug'] as String,
    );
  }

  final int id;
  final String title;
  final String slug;
}

class ContinueLesson {
  ContinueLesson({
    required this.id,
    required this.title,
    required this.slug,
    required this.lessonType,
    required this.body,
    required this.mediaUrl,
    this.meta,
  });

  factory ContinueLesson.fromJson(Map<String, dynamic> j) {
    return ContinueLesson(
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

class ContinueProgress {
  ContinueProgress({
    this.completedAt,
    this.notes,
    this.positionSeconds,
  });

  factory ContinueProgress.fromJson(Map<String, dynamic> j) {
    return ContinueProgress(
      completedAt: j['completed_at'] as String?,
      notes: j['notes'] as String?,
      positionSeconds: j['position_seconds'] as int?,
    );
  }

  bool get isComplete => completedAt != null && completedAt!.isNotEmpty;

  final String? completedAt;
  final String? notes;
  final int? positionSeconds;
}
