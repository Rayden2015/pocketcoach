class LearningCourseSummary {
  LearningCourseSummary({
    required this.courseId,
    required this.title,
    required this.slug,
    required this.lessonsTotal,
    required this.lessonsCompleted,
  });

  factory LearningCourseSummary.fromJson(Map<String, dynamic> j) {
    final raw = j['course_id'];
    final courseId = raw is int ? raw : (raw is num ? raw.toInt() : int.parse(raw.toString()));
    return LearningCourseSummary(
      courseId: courseId,
      title: j['title'] as String,
      slug: j['slug'] as String,
      lessonsTotal: j['lessons_total'] as int,
      lessonsCompleted: j['lessons_completed'] as int,
    );
  }

  final int courseId;
  final String title;
  final String slug;
  final int lessonsTotal;
  final int lessonsCompleted;

  double get fraction =>
      lessonsTotal <= 0 ? 0.0 : lessonsCompleted / lessonsTotal;
}
