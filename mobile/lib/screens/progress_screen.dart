import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/providers/learning_providers.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';

class ProgressScreen extends ConsumerWidget {
  const ProgressScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final slug = ref.watch(tenantSlugProvider);
    final summary = ref.watch(learningSummaryProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Progress'),
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
          ref.invalidate(learningSummaryProvider);
          await ref.read(learningSummaryProvider.future);
        },
        child: summary.when(
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
          data: (rows) {
            if (rows.isEmpty) {
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
                    Icons.pie_chart_outline,
                    size: 56,
                    color: Theme.of(context).colorScheme.outline,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'No progress yet',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Enroll in a course from the catalog to see lesson completion here.',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: Theme.of(context).colorScheme.onSurfaceVariant,
                        ),
                  ),
                ],
              );
            }
            return ListView.builder(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
              itemCount: rows.length + 1,
              itemBuilder: (context, i) {
                if (i == 0) {
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Text(
                      'Space: $slug',
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                            color: Theme.of(context).colorScheme.onSurfaceVariant,
                          ),
                    ),
                  );
                }
                final row = rows[i - 1];
                final done = row.lessonsCompleted >= row.lessonsTotal && row.lessonsTotal > 0;
                return Card(
                  margin: const EdgeInsets.only(bottom: 10),
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
              },
            );
          },
        ),
      ),
    );
  }
}
