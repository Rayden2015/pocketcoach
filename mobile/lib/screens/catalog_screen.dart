import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/api/pocket_coach_api.dart';
import 'package:pocket_coach_mobile/models/catalog_models.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/learning_providers.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/user_provider.dart';
import 'package:pocket_coach_mobile/providers/membership_providers.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';
import 'package:pocket_coach_mobile/widgets/space_switcher.dart';

Future<void> _joinSpace(BuildContext context, WidgetRef ref) async {
  final token = ref.read(sessionProvider).valueOrNull;
  if (token == null) {
    return;
  }
  final slug = ref.read(tenantSlugProvider);
  try {
    await ref.read(apiProvider).joinTenant(bearer: token, tenantSlug: slug);
    ref.invalidate(currentUserProvider);
    invalidateTenantScopedProviders(ref);
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('You joined this space')),
      );
    }
  } on ApiException catch (e) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.message ?? 'Could not join space')),
      );
    }
  }
}

Future<void> _enrollAndOpenCourse(
  BuildContext context,
  WidgetRef ref,
  CatalogCourse course,
) async {
  final pid = course.freeProductId;
  if (pid == null) {
    return;
  }
  final token = ref.read(sessionProvider).valueOrNull;
  if (token == null) {
    return;
  }
  final slug = ref.read(tenantSlugProvider);
  try {
    await ref.read(apiProvider).freeEnroll(
          bearer: token,
          tenantSlug: slug,
          productId: pid,
        );
    invalidateTenantScopedProviders(ref);
    ref.invalidate(courseDetailProvider(course.id));
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('You are enrolled')),
      );
      context.push('course/${course.id}');
    }
  } on ApiException catch (e) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.message ?? 'Enrollment failed')),
      );
    }
  }
}

class CatalogScreen extends ConsumerWidget {
  const CatalogScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    ref.watch(membershipSlugAlignmentProvider);
    final catalog = ref.watch(catalogProvider);

    return Scaffold(
      appBar: AppBar(
        title: const SpaceSwitcherButton(),
        actions: [
          IconButton(
            icon: const Icon(Icons.search),
            tooltip: 'Search courses',
            onPressed: () => context.push('/search'),
          ),
          IconButton(
            icon: const Icon(Icons.groups_2_outlined),
            tooltip: 'Join this space',
            onPressed: () => _joinSpace(context, ref),
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Sign out',
            onPressed: () => ref.read(sessionProvider.notifier).logout(),
          ),
        ],
      ),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(catalogProvider);
                await ref.read(catalogProvider.future);
              },
              child: catalog.when(
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
                    Text('Could not load catalog', style: Theme.of(context).textTheme.titleMedium),
                    const SizedBox(height: 8),
                    Text(e.toString()),
                  ],
                ),
                data: (programs) {
                  if (programs.isEmpty) {
                    return ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.all(24),
                      children: [
                        Icon(
                          Icons.school_outlined,
                          size: 56,
                          color: Theme.of(context).colorScheme.outline,
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'No catalog content for this space.',
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Check the slug or ask your coach to publish programs or single courses.',
                          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                color: Theme.of(context).colorScheme.onSurfaceVariant,
                              ),
                        ),
                      ],
                    );
                  }
                  return ListView.builder(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                    itemCount: programs.length,
                    itemBuilder: (context, i) {
                      final program = programs[i];
                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        clipBehavior: Clip.antiAlias,
                        child: ExpansionTile(
                          key: PageStorageKey<int>(program.id),
                          title: Text(program.title),
                          subtitle: program.summary != null && program.summary!.isNotEmpty
                              ? Text(
                                  program.summary!,
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                )
                              : null,
                          childrenPadding: const EdgeInsets.only(bottom: 8),
                          children: [
                            if (program.courses.isEmpty)
                              const ListTile(
                                title: Text('No courses yet'),
                              )
                            else
                              for (final course in program.courses)
                                ListTile(
                                  title: Text(course.title),
                                  subtitle: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      if (course.summary != null && course.summary!.isNotEmpty)
                                        Text(course.summary!),
                                      const SizedBox(height: 4),
                                      Text(
                                        course.isEnrolled
                                            ? 'Enrolled — open course'
                                            : course.canEnrollFree
                                                ? 'Free — tap to enroll & open'
                                                : 'Open course (paid / invite may apply)',
                                        style: Theme.of(context).textTheme.labelSmall?.copyWith(
                                              color: course.isEnrolled
                                                  ? Theme.of(context).colorScheme.primary
                                                  : Theme.of(context).colorScheme.onSurfaceVariant,
                                            ),
                                      ),
                                    ],
                                  ),
                                  isThreeLine: true,
                                  trailing: Icon(
                                    Icons.chevron_right,
                                    color: Theme.of(context).colorScheme.primary,
                                  ),
                                  onTap: () async {
                                    if (course.isEnrolled) {
                                      context.push('course/${course.id}');
                                      return;
                                    }
                                    if (course.canEnrollFree) {
                                      await _enrollAndOpenCourse(context, ref, course);
                                      return;
                                    }
                                    if (context.mounted) {
                                      context.push('course/${course.id}');
                                    }
                                  },
                                ),
                          ],
                        ),
                      );
                    },
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}
