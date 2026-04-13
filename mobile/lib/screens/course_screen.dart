import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/api/pocket_coach_api.dart';
import 'package:pocket_coach_mobile/models/course_detail.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/learning_providers.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';
import 'package:pocket_coach_mobile/router/app_paths.dart';

class CourseScreen extends ConsumerStatefulWidget {
  const CourseScreen({super.key, required this.courseId});

  final int courseId;

  @override
  ConsumerState<CourseScreen> createState() => _CourseScreenState();
}

class _CourseScreenState extends ConsumerState<CourseScreen> {
  var _enrolling = false;

  Future<void> _enrollFree(int? productId) async {
    if (productId == null || _enrolling) {
      return;
    }
    final token = ref.read(sessionProvider).valueOrNull;
    if (token == null) {
      return;
    }
    setState(() => _enrolling = true);
    try {
      final slug = ref.read(tenantSlugProvider);
      await ref.read(apiProvider).freeEnroll(
            bearer: token,
            tenantSlug: slug,
            productId: productId,
          );
      ref.invalidate(catalogProvider);
      ref.invalidate(learningSummaryProvider);
      ref.invalidate(continueLearningProvider);
      ref.invalidate(courseDetailProvider(widget.courseId));
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('You are enrolled')),
        );
      }
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.message ?? 'Enrollment failed')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _enrolling = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(courseDetailProvider(widget.courseId));

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          tooltip: 'Back',
          onPressed: () {
            if (context.canPop()) {
              context.pop();
            } else {
              AppPaths.goHome(context);
            }
          },
        ),
        title: async.maybeWhen(data: (c) => Text(c.title), orElse: () => const Text('Course')),
        actions: [
          IconButton(
            icon: const Icon(Icons.home_outlined),
            tooltip: 'Home',
            onPressed: () => AppPaths.goHome(context),
          ),
        ],
      ),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) {
          if (e is ApiException && e.statusCode == 403) {
            final freeId = e.freeProductId;
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
                    if (freeId != null) ...[
                      const SizedBox(height: 24),
                      FilledButton.icon(
                        onPressed: _enrolling ? null : () => _enrollFree(freeId),
                        icon: _enrolling
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Icon(Icons.how_to_reg),
                        label: Text(_enrolling ? 'Enrolling…' : 'Enroll free'),
                      ),
                    ],
                    const SizedBox(height: 16),
                    OutlinedButton(
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
              leading: lesson.progress?.isComplete == true
                  ? Icon(Icons.check_circle, color: Theme.of(context).colorScheme.primary)
                  : Icon(Icons.radio_button_unchecked, color: Theme.of(context).colorScheme.outline),
              trailing: Icon(Icons.chevron_right, color: Theme.of(context).colorScheme.primary),
              onTap: () => context.push('lesson/${lesson.id}'),
            ),
        ],
      ),
    );
  }
}
