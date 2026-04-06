import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/api/pocket_coach_api.dart';
import 'package:pocket_coach_mobile/models/course_detail.dart';
import 'package:pocket_coach_mobile/providers/learning_providers.dart';

class CourseScreen extends ConsumerWidget {
  const CourseScreen({super.key, required this.courseId});

  final int courseId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(courseDetailProvider(courseId));

    return Scaffold(
      appBar: AppBar(
        title: async.maybeWhen(data: (c) => Text(c.title), orElse: () => const Text('Course')),
      ),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) {
          if (e is ApiException && e.statusCode == 403) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.lock_outline, size: 56, color: Theme.of(context).colorScheme.error),
                    const SizedBox(height: 16),
                    Text(
                      'Enrollment required',
                      style: Theme.of(context).textTheme.headlineSmall,
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      e.message ?? 'You are not enrolled in this course.',
                      textAlign: TextAlign.center,
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: Theme.of(context).colorScheme.onSurfaceVariant,
                          ),
                    ),
                    const SizedBox(height: 24),
                    FilledButton(
                      onPressed: () => context.pop(),
                      child: const Text('Back to catalog'),
                    ),
                  ],
                ),
              ),
            );
          }
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: SelectableText(
                e.toString(),
                style: Theme.of(context).textTheme.bodyMedium,
              ),
            ),
          );
        },
        data: (course) {
          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
            children: [
              if (course.summary != null && course.summary!.isNotEmpty)
                Padding(
                  padding: const EdgeInsets.only(bottom: 16),
                  child: Text(
                    course.summary!,
                    style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                          color: Theme.of(context).colorScheme.onSurfaceVariant,
                        ),
                  ),
                ),
              ...course.modules.map((m) => _ModuleSection(module: m, courseId: course.id)),
            ],
          );
        },
      ),
    );
  }
}

class _ModuleSection extends StatelessWidget {
  const _ModuleSection({required this.module, required this.courseId});

  final ModuleOutline module;
  final int courseId;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      clipBehavior: Clip.antiAlias,
      child: ExpansionTile(
        key: PageStorageKey<int>(module.id),
        title: Text(module.title),
        subtitle: Text('${module.lessons.length} lesson${module.lessons.length == 1 ? '' : 's'}'),
        children: [
          for (final lesson in module.lessons)
            ListTile(
              title: Text(lesson.title),
              trailing: Icon(Icons.chevron_right, color: Theme.of(context).colorScheme.primary),
              onTap: () => context.push('/course/$courseId/lesson/${lesson.id}'),
            ),
        ],
      ),
    );
  }
}
