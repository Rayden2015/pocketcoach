import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/api/pocket_coach_api.dart';
import 'package:pocket_coach_mobile/models/booking_models.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/booking_providers.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';

String _formatSlotRange(BookingSlot s) {
  final a = DateTime.parse(s.startLocal).toLocal();
  final b = DateTime.parse(s.endLocal).toLocal();
  String part(DateTime d) {
    final h24 = d.hour;
    final h = h24 > 12 ? h24 - 12 : (h24 == 0 ? 12 : h24);
    final am = h24 >= 12 ? 'PM' : 'AM';
    final m = d.minute.toString().padLeft(2, '0');
    return '$h:$m $am';
  }

  final label =
      '${a.year}-${a.month.toString().padLeft(2, '0')}-${a.day.toString().padLeft(2, '0')}';
  return '$label · ${part(a)} – ${part(b)}';
}

/// Public booking flow (same API as web); uses the signed-in user as the booker.
class BookCoachScreen extends ConsumerStatefulWidget {
  const BookCoachScreen({super.key});

  @override
  ConsumerState<BookCoachScreen> createState() => _BookCoachScreenState();
}

class _BookCoachScreenState extends ConsumerState<BookCoachScreen> {
  int? _coachId;
  BookingSlot? _slot;
  final _message = TextEditingController();
  var _submitting = false;

  @override
  void dispose() {
    _message.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final coaches = ref.watch(bookableCoachesProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Book a coach'),
      ),
      body: coaches.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text('Could not load coaches.\n$e', textAlign: TextAlign.center),
          ),
        ),
        data: (list) {
          if (list.isEmpty) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: Text(
                  'No coaches are accepting bookings in this space yet.',
                  textAlign: TextAlign.center,
                ),
              ),
            );
          }

          if (_coachId == null) {
            if (list.length == 1) {
              WidgetsBinding.instance.addPostFrameCallback((_) {
                if (mounted) {
                  setState(() => _coachId = list.first.id);
                }
              });
            }
          } else if (!list.any((c) => c.id == _coachId)) {
            WidgetsBinding.instance.addPostFrameCallback((_) {
              if (mounted) {
                setState(() {
                  _coachId = null;
                  _slot = null;
                });
              }
            });
          }

          final cid = _coachId;

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              DropdownButtonFormField<int>(
                decoration: const InputDecoration(
                  labelText: 'Coach',
                  border: OutlineInputBorder(),
                ),
                value: cid,
                items: list
                    .map(
                      (c) => DropdownMenuItem(
                        value: c.id,
                        child: Text(c.displayLabel),
                      ),
                    )
                    .toList(),
                onChanged: (v) {
                  setState(() {
                    _coachId = v;
                    _slot = null;
                  });
                },
              ),
              if (cid != null) ...[
                const SizedBox(height: 20),
                Text('Open times', style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: 8),
                _SlotsList(
                  coachId: cid,
                  selected: _slot,
                  onSelect: (s) => setState(() => _slot = s),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: _message,
                  decoration: const InputDecoration(
                    labelText: 'Message (optional)',
                    border: OutlineInputBorder(),
                  ),
                  maxLines: 3,
                  maxLength: 2000,
                ),
                const SizedBox(height: 12),
                FilledButton(
                  onPressed: _submitting || _slot == null ? null : _submit,
                  child: _submitting
                      ? const SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Text('Request this time'),
                ),
              ],
            ],
          );
        },
      ),
    );
  }

  Future<void> _submit() async {
    final slot = _slot;
    final cid = _coachId;
    if (slot == null || cid == null) {
      return;
    }
    final token = ref.read(sessionProvider).valueOrNull;
    if (token == null || token.isEmpty) {
      return;
    }
    final slug = ref.read(tenantSlugProvider);
    setState(() => _submitting = true);
    try {
      await ref.read(apiProvider).createBookingRequest(
            bearer: token,
            tenantSlug: slug,
            coachUserId: cid,
            startsAt: slot.start,
            endsAt: slot.end,
            bookerMessage: _message.text.trim().isEmpty ? null : _message.text.trim(),
          );
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Booking request sent')),
      );
      context.pop();
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.message ?? 'Booking failed')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }
}

class _SlotsList extends ConsumerWidget {
  const _SlotsList({
    required this.coachId,
    required this.selected,
    required this.onSelect,
  });

  final int coachId;
  final BookingSlot? selected;
  final ValueChanged<BookingSlot> onSelect;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(bookingSlotsProvider(coachId));
    return async.when(
      loading: () => const Padding(
        padding: EdgeInsets.all(16),
        child: Center(child: CircularProgressIndicator()),
      ),
      error: (e, _) => Text('Could not load times: $e'),
      data: (slots) {
        if (slots.isEmpty) {
          return const Text('No open times right now. Try again later or ask your coach to add hours.');
        }
        return Column(
          children: slots
              .map(
                (s) => RadioListTile<BookingSlot>(
                  title: Text(_formatSlotRange(s)),
                  value: s,
                  groupValue: selected,
                  onChanged: (BookingSlot? v) {
                    if (v != null) {
                      onSelect(v);
                    }
                  },
                ),
              )
              .toList(),
        );
      },
    );
  }
}
