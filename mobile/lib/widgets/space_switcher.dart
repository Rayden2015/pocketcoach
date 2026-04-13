import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/providers/engagement_providers.dart';
import 'package:pocket_coach_mobile/providers/home_dashboard_provider.dart';
import 'package:pocket_coach_mobile/providers/learning_providers.dart';
import 'package:pocket_coach_mobile/providers/membership_providers.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';

void invalidateTenantScopedProviders(WidgetRef ref) {
  ref.invalidate(homeDashboardProvider);
  ref.invalidate(catalogProvider);
  ref.invalidate(continueLearningProvider);
  ref.invalidate(learningSummaryProvider);
  ref.invalidate(reflectionLatestProvider);
}

String spaceDisplayName(String slug, List<Map<String, dynamic>> memberships) {
  for (final m in memberships) {
    if (m['tenant_slug'] == slug) {
      return m['tenant_name'] as String? ?? slug;
    }
  }
  return slug;
}

/// Shows current space name and lets the user pick another membership or enter a slug.
class SpaceSwitcherButton extends ConsumerWidget {
  const SpaceSwitcherButton({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final slug = ref.watch(tenantSlugProvider);
    final memberships = ref.watch(userMembershipsProvider);
    final title = spaceDisplayName(slug, memberships);

    return PopupMenuButton<String>(
      tooltip: 'Space',
      onSelected: (value) async {
        if (value == '__other__') {
          await showOtherSpaceSlugDialog(context, ref);
          return;
        }
        await ref.read(tenantSlugProvider.notifier).setSlug(value);
        invalidateTenantScopedProviders(ref);
      },
      itemBuilder: (context) {
        return [
          ...memberships.map((m) {
            final s = m['tenant_slug'] as String? ?? '';
            final n = m['tenant_name'] as String? ?? s;
            return PopupMenuItem<String>(
              value: s,
              child: Text(n, overflow: TextOverflow.ellipsis),
            );
          }),
          const PopupMenuDivider(),
          const PopupMenuItem<String>(
            value: '__other__',
            child: Text('Other space…'),
          ),
        ];
      },
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 4),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Flexible(
              child: Text(
                title,
                overflow: TextOverflow.ellipsis,
                style: Theme.of(context).textTheme.titleMedium,
              ),
            ),
            const Icon(Icons.arrow_drop_down),
          ],
        ),
      ),
    );
  }
}

Future<void> showOtherSpaceSlugDialog(BuildContext context, WidgetRef ref) async {
  final controller = TextEditingController(text: ref.read(tenantSlugProvider));
  final ok = await showDialog<bool>(
    context: context,
    builder: (c) => AlertDialog(
      title: const Text('Other space'),
      content: TextField(
        controller: controller,
        decoration: const InputDecoration(
          hintText: 'Space slug (URL segment)',
          border: OutlineInputBorder(),
        ),
        autofocus: true,
        textInputAction: TextInputAction.done,
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(c, false), child: const Text('Cancel')),
        FilledButton(onPressed: () => Navigator.pop(c, true), child: const Text('Save')),
      ],
    ),
  );
  if (ok == true && context.mounted) {
    await ref.read(tenantSlugProvider.notifier).setSlug(controller.text);
    invalidateTenantScopedProviders(ref);
  }
}
