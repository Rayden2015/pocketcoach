import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/providers/engagement_providers.dart';
import 'package:pocket_coach_mobile/providers/learning_providers.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';

/// Continue + learning summary on one screen (parity with web "My learning" overview).
class LearningHomeScreen extends ConsumerWidget {
  const LearningHomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final slug = ref.watch(tenantSlugProvider);
    final cont = ref.watch(continueLearningProvider);
    final summary = ref.watch(learningSummaryProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Learning'),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(continueLearningProvider);
          ref.invalidate(learningSummaryProvider);
          ref.invalidate(reflectionLatestProvider);
          await Future.wait([
            ref.read(continueLearningProvider.future),
            ref.read(learningSummaryProvider.future),
          ]);
        },
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
          children: [
            Text(
              'Space: $slug',
              style: Theme.of(context).textTheme.titleSmall?.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
            ),
            const SizedBox(height: 20),
            Text(
              'Continue',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 8),
            cont.when(
              loading: () => const Padding(
                padding: EdgeInsets.symmetric(vertical: 24),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) => Text(e.toString()),
              data: (payload) {
                if (payload == null) {
                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const _ReflectionPromptCard(),
                      const SizedBox(height: 16),
                      Icon(
                        Icons.check_circle_outline,
                        size: 48,
                        color: Theme.of(context).colorScheme.primary,
                      ),
                      const SizedBox(height: 12),
                      Text(
                        "You're all caught up",
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'No incomplete lessons, or you are not enrolled in any courses yet.',
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              color: Theme.of(context).colorScheme.onSurfaceVariant,
                            ),
                      ),
                    ],
                  );
                }
                final progress = payload.progress;
                final complete = progress?.isComplete ?? false;
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const _ReflectionPromptCard(),
                    const SizedBox(height: 12),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              payload.course.title,
                              style: Theme.of(context).textTheme.labelMedium?.copyWith(
                                    color: Theme.of(context).colorScheme.primary,
                                  ),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              payload.lesson.title,
                              style: Theme.of(context).textTheme.titleMedium,
                            ),
                            const SizedBox(height: 8),
                            if (complete)
                              const Chip(
                                avatar: Icon(Icons.check, size: 18),
                                label: Text('Marked complete'),
                              )
                            else
                              const Chip(label: Text('In progress')),
                            if (progress?.notes != null && progress!.notes!.isNotEmpty) ...[
                              const SizedBox(height: 8),
                              Text(
                                'Notes: ${progress.notes}',
                                style: Theme.of(context).textTheme.bodySmall,
                              ),
                            ],
                            const SizedBox(height: 12),
                            FilledButton.icon(
                              onPressed: () {
                                context.push(
                                  '/course/${payload.course.id}/lesson/${payload.lesson.id}',
                                );
                              },
                              icon: const Icon(Icons.play_arrow),
                              label: const Text('Open lesson'),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                );
              },
            ),
            const SizedBox(height: 28),
            Text(
              'Your progress',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 8),
            summary.when(
              loading: () => const Padding(
                padding: EdgeInsets.symmetric(vertical: 24),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) => Text(e.toString()),
              data: (rows) {
                if (rows.isEmpty) {
                  return Text(
                    'Enroll in a course from the catalog to see completion here.',
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
                        subtitle: Text(
                          '${row.lessonsCompleted} / ${row.lessonsTotal} lessons complete',
                        ),
                        trailing: done
                            ? Icon(Icons.check_circle, color: Theme.of(context).colorScheme.primary)
                            : Text('${(row.fraction * 100).round()}%'),
                        onTap: () => context.push('/course/${row.courseId}'),
                      ),
                    );
                  }).toList(),
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}

class _ReflectionPromptCard extends ConsumerWidget {
  const _ReflectionPromptCard();

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
            subtitle: const Text('Reflection prompt — tap to respond'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => context.push('/reflection/${prompt.id}'),
          ),
        );
      },
    );
  }
}
