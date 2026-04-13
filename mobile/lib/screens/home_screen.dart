import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/models/home_dashboard.dart';
import 'package:pocket_coach_mobile/providers/engagement_providers.dart';
import 'package:pocket_coach_mobile/providers/home_dashboard_provider.dart';
import 'package:pocket_coach_mobile/providers/learning_providers.dart';
import 'package:pocket_coach_mobile/providers/membership_providers.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';
import 'package:pocket_coach_mobile/router/app_paths.dart';
import 'package:pocket_coach_mobile/services/web_links.dart';
import 'package:pocket_coach_mobile/widgets/space_switcher.dart';

/// Space home: learner performance + continue learning; coach snapshot + web shortcuts when staff.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    ref.watch(membershipSlugAlignmentProvider);
    final dash = ref.watch(homeDashboardProvider);

    return Scaffold(
      appBar: AppBar(
        title: const SpaceSwitcherButton(),
        actions: [
          IconButton(
            icon: const Icon(Icons.search),
            tooltip: 'Search courses',
            onPressed: () => context.push('/search'),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(homeDashboardProvider);
          ref.invalidate(continueLearningProvider);
          ref.invalidate(learningSummaryProvider);
          ref.invalidate(reflectionLatestProvider);
          await ref.read(homeDashboardProvider.future);
        },
        child: dash.when(
          loading: () => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            children: const [
              SizedBox(
                height: 120,
                child: Center(child: CircularProgressIndicator()),
              ),
            ],
          ),
          error: (e, _) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(24),
            children: [
              Text(e.toString(), style: TextStyle(color: Theme.of(context).colorScheme.error)),
            ],
          ),
          data: (payload) {
            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
              children: [
                if (payload.membership != null) ...[
                  const SizedBox(height: 6),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      Chip(
                        label: Text(_roleLabel(payload.membership!.role)),
                        visualDensity: VisualDensity.compact,
                      ),
                      if (payload.membership!.isStaff)
                        Chip(
                          label: const Text('Coach'),
                          visualDensity: VisualDensity.compact,
                          backgroundColor: Theme.of(context).colorScheme.tertiaryContainer,
                        ),
                    ],
                  ),
                ],
                const SizedBox(height: 20),
                Text(
                  'Your week',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                _LearnerStatsRow(learner: payload.learner),
                const SizedBox(height: 20),
                Text(
                  'Courses',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                _CourseCountsCard(learner: payload.learner),
                const SizedBox(height: 20),
                Text(
                  'Continue learning',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                _ContinueCard(
                  preview: payload.learner.continueLearning,
                ),
                const SizedBox(height: 8),
                const _ReflectionPromptHomeCard(),
                const SizedBox(height: 28),
                const _LearningCoursesSection(),
                if (payload.coach != null) ...[
                  const SizedBox(height: 20),
                  Text(
                    'Coaching',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 8),
                  _CoachSection(stats: payload.coach!, tenantSlug: ref.watch(tenantSlugProvider)),
                ],
              ],
            );
          },
        ),
      ),
    );
  }
}

String _roleLabel(String role) {
  if (role.isEmpty) {
    return 'Member';
  }
  return role[0].toUpperCase() + role.substring(1);
}

class _LearnerStatsRow extends StatelessWidget {
  const _LearnerStatsRow({required this.learner});

  final LearnerHomeStats learner;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _StatTile(
            label: 'Lessons done (7d)',
            value: '${learner.lessonsCompleted7d}',
            icon: Icons.calendar_view_week_outlined,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _StatTile(
            label: 'Lessons done (30d)',
            value: '${learner.lessonsCompleted30d}',
            icon: Icons.calendar_month_outlined,
          ),
        ),
      ],
    );
  }
}

class _StatTile extends StatelessWidget {
  const _StatTile({
    required this.label,
    required this.value,
    required this.icon,
  });

  final String label;
  final String value;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, size: 22, color: Theme.of(context).colorScheme.primary),
            const SizedBox(height: 8),
            Text(
              value,
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
            ),
          ],
        ),
      ),
    );
  }
}

class _CourseCountsCard extends StatelessWidget {
  const _CourseCountsCard({required this.learner});

  final LearnerHomeStats learner;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            _CountRow(label: 'Enrolled', value: learner.coursesEnrolled),
            const Divider(height: 20),
            _CountRow(label: 'Completed', value: learner.coursesCompleted),
            const Divider(height: 20),
            _CountRow(label: 'In progress', value: learner.coursesInProgress),
            const Divider(height: 20),
            _CountRow(label: 'Not started', value: learner.coursesNotStarted),
          ],
        ),
      ),
    );
  }
}

class _CountRow extends StatelessWidget {
  const _CountRow({required this.label, required this.value});

  final String label;
  final int value;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: Theme.of(context).textTheme.bodyLarge),
        Text(
          '$value',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
        ),
      ],
    );
  }
}

class _ContinueCard extends StatelessWidget {
  const _ContinueCard({
    required this.preview,
  });

  final ContinueLearningPreview? preview;

  @override
  Widget build(BuildContext context) {
    if (preview == null) {
      return Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Icon(Icons.school_outlined, color: Theme.of(context).colorScheme.primary),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  'No lesson in progress — browse the catalog to start.',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                      ),
                ),
              ),
              TextButton(
                onPressed: () => context.go(AppPaths.catalogRoot),
                child: const Text('Catalog'),
              ),
            ],
          ),
        ),
      );
    }
    final p = preview!;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              p.courseTitle,
              style: Theme.of(context).textTheme.labelMedium?.copyWith(
                    color: Theme.of(context).colorScheme.primary,
                  ),
            ),
            const SizedBox(height: 6),
            Text(p.lessonTitle, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 12),
            FilledButton.icon(
              onPressed: () {
                context.push(
                  AppPaths.catalogCourseLesson(p.courseId, p.lessonId),
                );
              },
              icon: const Icon(Icons.play_arrow),
              label: const Text('Resume lesson'),
            ),
          ],
        ),
      ),
    );
  }
}

class _LearningCoursesSection extends ConsumerWidget {
  const _LearningCoursesSection();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final summary = ref.watch(learningSummaryProvider);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Your courses',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 8),
        summary.when(
          loading: () => const Padding(
            padding: EdgeInsets.symmetric(vertical: 16),
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (e, _) => Text(e.toString()),
          data: (rows) {
            if (rows.isEmpty) {
              return Text(
                'Enroll in a course from the Catalog tab to see progress here.',
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
              );
            }
            return Column(
              children: rows.map((row) {
                final done = row.lessonsCompleted >= row.lessonsTotal && row.lessonsTotal > 0;
                return Card(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: ListTile(
                    title: Text(row.title),
                    subtitle: Text('${row.lessonsCompleted} / ${row.lessonsTotal} lessons complete'),
                    trailing: done
                        ? Icon(Icons.check_circle, color: Theme.of(context).colorScheme.primary)
                        : Text('${(row.fraction * 100).round()}%'),
                    onTap: () => context.push(AppPaths.catalogCourse(row.courseId)),
                  ),
                );
              }).toList(),
            );
          },
        ),
      ],
    );
  }
}

class _ReflectionPromptHomeCard extends ConsumerWidget {
  const _ReflectionPromptHomeCard();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final reflection = ref.watch(reflectionLatestProvider);
    return reflection.when(
      loading: () => const SizedBox.shrink(),
      error: (_, __) => const SizedBox.shrink(),
      data: (prompt) {
        if (prompt == null) {
          return const SizedBox.shrink();
        }
        return Card(
          color: Theme.of(context).colorScheme.secondaryContainer.withValues(alpha: 0.35),
          child: ListTile(
            leading: Icon(Icons.edit_note, color: Theme.of(context).colorScheme.primary),
            title: Text(prompt.title),
            subtitle: const Text('Reflection — respond in the app'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => context.push('/home/reflection/${prompt.id}'),
          ),
        );
      },
    );
  }
}

class _CoachSection extends StatelessWidget {
  const _CoachSection({required this.stats, required this.tenantSlug});

  final CoachHomeStats stats;
  final String tenantSlug;

  @override
  Widget build(BuildContext context) {
    final base = '/$tenantSlug/coach';
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Space activity (7d)',
                  style: Theme.of(context).textTheme.titleSmall,
                ),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 16,
                  runSpacing: 12,
                  children: [
                    _CoachStat(value: '${stats.lessonCompletions7d}', label: 'Lesson completions (7d)'),
                    _CoachStat(value: '${stats.reflectionPromptsLive}', label: 'Live reflections'),
                    _CoachStat(value: '${stats.scheduledReflectionsPending}', label: 'Scheduled pending'),
                  ],
                ),
                const SizedBox(height: 16),
                Text('Catalog', style: Theme.of(context).textTheme.titleSmall),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 12,
                  runSpacing: 8,
                  children: [
                    _CoachStat(value: '${stats.programsLive}', label: 'Programs live'),
                    _CoachStat(value: '${stats.programsDraft}', label: 'Programs draft'),
                    _CoachStat(value: '${stats.coursesLive}', label: 'Courses live'),
                  ],
                ),
                const SizedBox(height: 16),
                Text('Learners', style: Theme.of(context).textTheme.titleSmall),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 12,
                  runSpacing: 8,
                  children: [
                    _CoachStat(value: '${stats.activeEnrollments}', label: 'Active enrollments'),
                    _CoachStat(value: '${stats.learnersWithEnrollment}', label: 'Learners (enrolled)'),
                    _CoachStat(value: '${stats.learnerMembers}', label: 'Learner members'),
                  ],
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        Text(
          'Open in browser',
          style: Theme.of(context).textTheme.titleSmall?.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
        ),
        const SizedBox(height: 8),
        Card(
          child: Column(
            children: [
              ListTile(
                leading: const Icon(Icons.dashboard_outlined),
                title: const Text('Coach console'),
                subtitle: const Text('Programs, courses, and content'),
                trailing: const Icon(Icons.open_in_new),
                onTap: () => openTenantWebPath('$base/programs'),
              ),
              const Divider(height: 1),
              ListTile(
                leading: const Icon(Icons.forum_outlined),
                title: const Text('Learner submissions'),
                subtitle: const Text('Reflections and notes'),
                trailing: const Icon(Icons.open_in_new),
                onTap: () => openTenantWebPath('$base/learner-submissions?tab=reflections'),
              ),
              const Divider(height: 1),
              ListTile(
                leading: const Icon(Icons.edit_note_outlined),
                title: const Text('Reflection prompts'),
                trailing: const Icon(Icons.open_in_new),
                onTap: () => openTenantWebPath('$base/reflections'),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _CoachStat extends StatelessWidget {
  const _CoachStat({required this.value, required this.label});

  final String value;
  final String label;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 140,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            value,
            style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
          ),
          Text(
            label,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
          ),
        ],
      ),
    );
  }
}
