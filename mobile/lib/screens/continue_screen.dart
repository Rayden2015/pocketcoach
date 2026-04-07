import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/providers/learning_providers.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';

class ContinueScreen extends ConsumerWidget {
  const ContinueScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final slug = ref.watch(tenantSlugProvider);
    final cont = ref.watch(continueLearningProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Continue'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Sign out',
            onPressed: () => ref.read(sessionProvider.notifier).logout(),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(continueLearningProvider);
          await ref.read(continueLearningProvider.future);
        },
        child: cont.when(
          loading: () => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            children: const [
              SizedBox(height: 120),
              Center(child: CircularProgressIndicator()),
            ],
          ),
          error: (e, _) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(24),
            children: [
              Icon(Icons.error_outline, size: 48, color: Theme.of(context).colorScheme.error),
              const SizedBox(height: 12),
              Text(e.toString()),
            ],
          ),
          data: (payload) {
            if (payload == null) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(24),
                children: [
                  Text(
                    'Space: $slug',
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(
                          color: Theme.of(context).colorScheme.onSurfaceVariant,
                        ),
                  ),
                  const SizedBox(height: 32),
                  Icon(
                    Icons.check_circle_outline,
                    size: 64,
                    color: Theme.of(context).colorScheme.primary,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    "You're all caught up",
                    style: Theme.of(context).textTheme.headlineSmall,
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

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(20),
              children: [
                Text(
                  'Space: $slug',
                  style: Theme.of(context).textTheme.titleSmall?.copyWith(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                      ),
                ),
                const SizedBox(height: 20),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          payload.course.title,
                          style: Theme.of(context).textTheme.labelMedium?.copyWith(
                                color: Theme.of(context).colorScheme.primary,
                              ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          payload.lesson.title,
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const SizedBox(height: 12),
                        if (complete)
                          const Chip(
                            avatar: Icon(Icons.check, size: 18),
                            label: Text('Marked complete'),
                          )
                        else
                          const Chip(
                            label: Text('In progress'),
                          ),
                        if (progress?.notes != null && progress!.notes!.isNotEmpty) ...[
                          const SizedBox(height: 12),
                          Text(
                            'Notes: ${progress.notes}',
                            style: Theme.of(context).textTheme.bodyMedium,
                          ),
                        ],
                        const SizedBox(height: 20),
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
      ),
    );
  }
}
