import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/api/pocket_coach_api.dart';
import 'package:pocket_coach_mobile/models/booking_models.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/booking_providers.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';

/// Coach/staff view of booking requests (parity with web coach console).
class CoachBookingsScreen extends ConsumerWidget {
  const CoachBookingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rows = ref.watch(coachBookingsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Bookings'),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(coachBookingsProvider);
          await ref.read(coachBookingsProvider.future);
        },
        child: rows.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            children: [
              Padding(
                padding: const EdgeInsets.all(24),
                child: Text(
                  'Could not load bookings.\n$e',
                  textAlign: TextAlign.center,
                ),
              ),
            ],
          ),
          data: (list) {
            if (list.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  SizedBox(height: 48),
                  Center(child: Text('No bookings yet.')),
                ],
              );
            }
            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(12),
              itemCount: list.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, i) {
                final b = list[i];
                return _BookingTile(row: b);
              },
            );
          },
        ),
      ),
    );
  }
}

class _BookingTile extends ConsumerWidget {
  const _BookingTile({required this.row});

  final CoachBookingRow row;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final start = DateTime.tryParse(row.startsAt)?.toLocal();
    final timeStr = start != null ? start.toString() : row.startsAt;

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            timeStr,
            style: Theme.of(context).textTheme.titleSmall,
          ),
          Text(
            '${row.bookerDisplayName}${row.bookerEmail != null ? ' · ${row.bookerEmail}' : ''}',
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          if (row.bookerMessage != null && row.bookerMessage!.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 4),
              child: Text(
                '“${row.bookerMessage}”',
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ),
          Text(
            row.status.replaceAll('_', ' '),
            style: Theme.of(context).textTheme.labelSmall,
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              if (row.status == 'pending') ...[
                FilledButton(
                  onPressed: () => _confirm(context, ref),
                  child: const Text('Confirm'),
                ),
                OutlinedButton(
                  onPressed: () => _decline(context, ref),
                  child: const Text('Decline'),
                ),
              ],
              if (row.status == 'pending' || row.status == 'confirmed')
                TextButton(
                  onPressed: () => _cancel(context, ref),
                  child: const Text('Cancel as coach'),
                ),
            ],
          ),
        ],
      ),
    );
  }

  Future<void> _confirm(BuildContext context, WidgetRef ref) async {
    final token = ref.read(sessionProvider).valueOrNull;
    if (token == null) {
      return;
    }
    final slug = ref.read(tenantSlugProvider);
    try {
      await ref.read(apiProvider).confirmCoachBooking(
            bearer: token,
            tenantSlug: slug,
            bookingId: row.id,
          );
      ref.invalidate(coachBookingsProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Confirmed')));
      }
    } on ApiException catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.message ?? 'Failed')),
        );
      }
    }
  }

  Future<void> _decline(BuildContext context, WidgetRef ref) async {
    final token = ref.read(sessionProvider).valueOrNull;
    if (token == null) {
      return;
    }
    final slug = ref.read(tenantSlugProvider);
    try {
      await ref.read(apiProvider).declineCoachBooking(
            bearer: token,
            tenantSlug: slug,
            bookingId: row.id,
          );
      ref.invalidate(coachBookingsProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Declined')));
      }
    } on ApiException catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.message ?? 'Failed')),
        );
      }
    }
  }

  Future<void> _cancel(BuildContext context, WidgetRef ref) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (c) => AlertDialog(
        title: const Text('Cancel booking?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(c, false), child: const Text('No')),
          FilledButton(onPressed: () => Navigator.pop(c, true), child: const Text('Yes')),
        ],
      ),
    );
    if (ok != true) {
      return;
    }
    final token = ref.read(sessionProvider).valueOrNull;
    if (token == null) {
      return;
    }
    final slug = ref.read(tenantSlugProvider);
    try {
      await ref.read(apiProvider).cancelCoachBooking(
            bearer: token,
            tenantSlug: slug,
            bookingId: row.id,
          );
      ref.invalidate(coachBookingsProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Cancelled')));
      }
    } on ApiException catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.message ?? 'Failed')),
        );
      }
    }
  }
}
