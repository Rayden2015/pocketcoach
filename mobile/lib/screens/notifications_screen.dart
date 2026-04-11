import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/api/pocket_coach_api.dart';
import 'package:pocket_coach_mobile/models/engagement_models.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/engagement_providers.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:url_launcher/url_launcher.dart';

class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final list = ref.watch(notificationsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          IconButton(
            icon: const Icon(Icons.done_all),
            tooltip: 'Mark all read',
            onPressed: () async {
              final token = ref.read(sessionProvider).valueOrNull;
              if (token == null || token.isEmpty) {
                return;
              }
              try {
                await ref.read(apiProvider).markAllNotificationsRead(bearer: token);
                ref.invalidate(notificationsProvider);
                ref.invalidate(unreadNotificationCountProvider);
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('All notifications marked read')),
                  );
                }
              } on ApiException catch (e) {
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text(e.message ?? 'Could not update')),
                  );
                }
              }
            },
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Sign out',
            onPressed: () => ref.read(sessionProvider.notifier).logout(),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(notificationsProvider);
          ref.invalidate(unreadNotificationCountProvider);
          await ref.read(notificationsProvider.future);
        },
        child: list.when(
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
                  Icon(
                    Icons.notifications_none_outlined,
                    size: 56,
                    color: Theme.of(context).colorScheme.outline,
                  ),
                  const SizedBox(height: 16),
                  Text('No notifications yet', style: Theme.of(context).textTheme.titleMedium),
                ],
              );
            }
            return ListView.builder(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
              itemCount: rows.length,
              itemBuilder: (context, i) {
                final n = rows[i];
                final preview = _previewForNotification(n);
                return Card(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: ListTile(
                    title: Text(_titleForNotification(n), maxLines: 2, overflow: TextOverflow.ellipsis),
                    subtitle: preview != null && preview.isNotEmpty
                        ? Text(
                            preview,
                            maxLines: 3,
                            overflow: TextOverflow.ellipsis,
                            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                                ),
                          )
                        : Text(
                            _shortType(n.type),
                            style: Theme.of(context).textTheme.labelSmall?.copyWith(
                                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                                ),
                          ),
                    isThreeLine: preview != null && preview.isNotEmpty,
                    trailing: n.isUnread
                        ? Icon(Icons.circle, size: 10, color: Theme.of(context).colorScheme.primary)
                        : null,
                    onTap: () async {
                      final token = ref.read(sessionProvider).valueOrNull;
                      if (token == null || token.isEmpty) {
                        return;
                      }
                      try {
                        if (n.isUnread) {
                          await ref.read(apiProvider).markNotificationRead(
                                bearer: token,
                                notificationId: n.id,
                              );
                          ref.invalidate(notificationsProvider);
                          ref.invalidate(unreadNotificationCountProvider);
                        }
                        final u = n.url;
                        if (u != null && u.isNotEmpty) {
                          final uri = Uri.tryParse(u);
                          if (uri != null && await canLaunchUrl(uri)) {
                            await launchUrl(uri, mode: LaunchMode.externalApplication);
                          }
                        }
                      } on ApiException catch (e) {
                        if (context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text(e.message ?? 'Could not mark read')),
                          );
                        }
                      }
                    },
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }

  String _titleForNotification(UserNotificationItem n) {
    final top = n.title;
    if (top != null && top.isNotEmpty) {
      return top;
    }
    final d = n.data;
    if (d != null) {
      final t = d['title'];
      if (t is String && t.isNotEmpty) {
        return t;
      }
      final m = d['message'];
      if (m is String && m.isNotEmpty) {
        return m;
      }
    }
    // Laravel stores notification `type` as the PHP class FQCN; namespaces use `\` (single backslash).
    // In Dart, `'\\'` is one backslash character, so this yields e.g. `TestNotification`.
    final parts = n.type.split('\\');
    return parts.isNotEmpty ? parts.last : n.type;
  }

  String? _previewForNotification(UserNotificationItem n) {
    final p = n.preview;
    if (p != null && p.isNotEmpty) {
      return p;
    }
    final d = n.data;
    if (d == null) {
      return null;
    }
    final bp = d['body_preview'];
    if (bp is String && bp.isNotEmpty) {
      return bp;
    }
    final b = d['body'];
    if (b is String && b.isNotEmpty) {
      return b;
    }
    return null;
  }

  String _shortType(String fqcn) {
    final parts = fqcn.split('\\');
    return parts.isNotEmpty ? parts.last : fqcn;
  }
}
