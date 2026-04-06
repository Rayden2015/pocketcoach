import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/providers/learning_providers.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';

class CatalogScreen extends ConsumerWidget {
  const CatalogScreen({super.key});

  Future<void> _editSlug(BuildContext context, WidgetRef ref) async {
    final controller = TextEditingController(text: ref.read(tenantSlugProvider));
    final ok = await showDialog<bool>(
      context: context,
      builder: (c) {
        return AlertDialog(
          title: const Text('Space slug'),
          content: TextField(
            controller: controller,
            decoration: const InputDecoration(
              hintText: 'e.g. adeola',
              border: OutlineInputBorder(),
            ),
            autofocus: true,
            textInputAction: TextInputAction.done,
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(c, false),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(c, true),
              child: const Text('Save'),
            ),
          ],
        );
      },
    );

    if (ok == true && context.mounted) {
      await ref.read(tenantSlugProvider.notifier).setSlug(controller.text);
      ref.invalidate(catalogProvider);
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final slug = ref.watch(tenantSlugProvider);
    final catalog = ref.watch(catalogProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Catalog'),
        actions: [
          IconButton(
            icon: const Icon(Icons.edit_location_alt_outlined),
            tooltip: 'Change space',
            onPressed: () => _editSlug(context, ref),
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
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: Text(
              'Space: $slug',
              style: Theme.of(context).textTheme.titleSmall?.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(catalogProvider);
                await ref.read(catalogProvider.future);
              },
              child: catalog.when(
                loading: () => const ListView(
                  physics: AlwaysScrollableScrollPhysics(),
                  children: [
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
                          'No published programs for this space.',
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Check the slug or ask your coach to publish content.',
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
                                  subtitle: course.summary != null && course.summary!.isNotEmpty
                                      ? Text(course.summary!)
                                      : null,
                                  trailing: Icon(
                                    Icons.chevron_right,
                                    color: Theme.of(context).colorScheme.primary,
                                  ),
                                  onTap: () => context.push('/course/${course.id}'),
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
