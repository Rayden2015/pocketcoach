/// `GET /api/v1/tenants/{slug}/home-dashboard` → `{ data: { membership, learner, coach } }`.
class HomeDashboardPayload {
  const HomeDashboardPayload({
    required this.membership,
    required this.learner,
    this.coach,
  });

  final DashboardMembership? membership;
  final LearnerHomeStats learner;
  final CoachHomeStats? coach;

  factory HomeDashboardPayload.fromApi(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? json;
    return HomeDashboardPayload(
      membership: data['membership'] != null
          ? DashboardMembership.fromJson(data['membership'] as Map<String, dynamic>)
          : null,
      learner: LearnerHomeStats.fromJson(data['learner'] as Map<String, dynamic>),
      coach: data['coach'] != null
          ? CoachHomeStats.fromJson(data['coach'] as Map<String, dynamic>)
          : null,
    );
  }
}

class DashboardMembership {
  const DashboardMembership({required this.role, required this.isStaff});

  final String role;
  final bool isStaff;

  factory DashboardMembership.fromJson(Map<String, dynamic> json) {
    return DashboardMembership(
      role: json['role'] as String? ?? 'learner',
      isStaff: json['is_staff'] as bool? ?? false,
    );
  }
}

class LearnerHomeStats {
  const LearnerHomeStats({
    required this.lessonsCompleted7d,
    required this.lessonsCompleted30d,
    required this.coursesEnrolled,
    required this.coursesCompleted,
    required this.coursesInProgress,
    required this.coursesNotStarted,
    this.continueLearning,
  });

  final int lessonsCompleted7d;
  final int lessonsCompleted30d;
  final int coursesEnrolled;
  final int coursesCompleted;
  final int coursesInProgress;
  final int coursesNotStarted;
  final ContinueLearningPreview? continueLearning;

  factory LearnerHomeStats.fromJson(Map<String, dynamic> json) {
    final cont = json['continue'];
    return LearnerHomeStats(
      lessonsCompleted7d: _int(json['lessons_completed_7d']),
      lessonsCompleted30d: _int(json['lessons_completed_30d']),
      coursesEnrolled: _int(json['courses_enrolled']),
      coursesCompleted: _int(json['courses_completed']),
      coursesInProgress: _int(json['courses_in_progress']),
      coursesNotStarted: _int(json['courses_not_started']),
      continueLearning: cont is Map<String, dynamic> ? ContinueLearningPreview.fromJson(cont) : null,
    );
  }
}

class ContinueLearningPreview {
  const ContinueLearningPreview({
    required this.courseId,
    required this.courseTitle,
    required this.lessonId,
    required this.lessonTitle,
  });

  final int courseId;
  final String courseTitle;
  final int lessonId;
  final String lessonTitle;

  factory ContinueLearningPreview.fromJson(Map<String, dynamic> json) {
    final course = json['course'] as Map<String, dynamic>? ?? {};
    final lesson = json['lesson'] as Map<String, dynamic>? ?? {};
    return ContinueLearningPreview(
      courseId: _int(course['id']),
      courseTitle: course['title'] as String? ?? '',
      lessonId: _int(lesson['id']),
      lessonTitle: lesson['title'] as String? ?? '',
    );
  }
}

class CoachHomeStats {
  const CoachHomeStats({
    required this.programsLive,
    required this.programsDraft,
    required this.coursesLive,
    required this.activeEnrollments,
    required this.learnersWithEnrollment,
    required this.learnerMembers,
    required this.lessonCompletions7d,
    required this.reflectionPromptsLive,
    required this.scheduledReflectionsPending,
  });

  final int programsLive;
  final int programsDraft;
  final int coursesLive;
  final int activeEnrollments;
  final int learnersWithEnrollment;
  final int learnerMembers;
  final int lessonCompletions7d;
  final int reflectionPromptsLive;
  final int scheduledReflectionsPending;

  factory CoachHomeStats.fromJson(Map<String, dynamic> json) {
    return CoachHomeStats(
      programsLive: _int(json['programs_live']),
      programsDraft: _int(json['programs_draft']),
      coursesLive: _int(json['courses_live']),
      activeEnrollments: _int(json['active_enrollments']),
      learnersWithEnrollment: _int(json['learners_with_enrollment']),
      learnerMembers: _int(json['learner_members']),
      lessonCompletions7d: _int(json['lesson_completions_7d']),
      reflectionPromptsLive: _int(json['reflection_prompts_live']),
      scheduledReflectionsPending: _int(json['scheduled_reflections_pending']),
    );
  }
}

int _int(dynamic v) {
  if (v is int) {
    return v;
  }
  if (v is num) {
    return v.toInt();
  }
  return 0;
}
