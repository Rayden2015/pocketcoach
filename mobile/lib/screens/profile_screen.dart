import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/api/pocket_coach_api.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/membership_providers.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/user_provider.dart';

/// Profile & account — parity with web `/profile`.
class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  final _name = TextEditingController();
  final _headline = TextEditingController();
  final _bio = TextEditingController();
  final _phone = TextEditingController();
  String? _timezone;
  String? _locale;
  var _hydrated = false;
  var _saving = false;

  static const _timezonesBase = <String>[
    'UTC',
    'Africa/Accra',
    'Africa/Lagos',
    'Africa/Nairobi',
    'Europe/London',
    'America/New_York',
    'America/Los_Angeles',
  ];

  static const _locales = <String, String>{
    'en': 'English',
    'fr': 'Français',
    'pt': 'Português',
    'ar': 'العربية',
    'tw': 'Twi',
    'ha': 'Hausa',
  };

  @override
  void dispose() {
    _name.dispose();
    _headline.dispose();
    _bio.dispose();
    _phone.dispose();
    super.dispose();
  }

  List<String> _timezoneChoices() {
    final list = List<String>.from(_timezonesBase);
    final tz = _timezone;
    if (tz != null && tz.isNotEmpty && !list.contains(tz)) {
      list.insert(0, tz);
    }
    return list;
  }

  Map<String, String> _localeChoices() {
    final map = Map<String, String>.from(_locales);
    final l = _locale;
    if (l != null && l.isNotEmpty && !map.containsKey(l)) {
      map[l] = l;
    }
    return map;
  }

  void _hydrate(Map<String, dynamic> u) {
    if (_hydrated) {
      return;
    }
    _hydrated = true;
    _name.text = (u['name'] as String?) ?? '';
    _headline.text = (u['headline'] as String?) ?? '';
    _bio.text = (u['bio'] as String?) ?? '';
    _phone.text = (u['phone'] as String?) ?? '';
    _timezone = u['timezone'] as String? ?? 'UTC';
    _locale = u['locale'] as String? ?? 'en';
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    try {
      final token = ref.read(sessionProvider).valueOrNull;
      if (token == null) {
        throw StateError('Not signed in');
      }
      await ref.read(apiProvider).updateProfile(
            bearer: token,
            fields: <String, dynamic>{
              'name': _name.text.trim(),
              'headline': _headline.text.trim().isEmpty ? null : _headline.text.trim(),
              'bio': _bio.text.trim().isEmpty ? null : _bio.text.trim(),
              'phone': _phone.text.trim().isEmpty ? null : _phone.text.trim(),
              'timezone': _timezone,
              'locale': _locale,
            },
          );
      ref.invalidate(currentUserProvider);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Profile saved')),
        );
      }
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.message ?? 'Could not save')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(currentUserProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Profile'),
        actions: [
          IconButton(
            icon: const Icon(Icons.search),
            tooltip: 'Search courses',
            onPressed: () => context.push('/search'),
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Sign out',
            onPressed: () => ref.read(sessionProvider.notifier).logout(),
          ),
        ],
      ),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Padding(padding: const EdgeInsets.all(24), child: Text('$e'))),
        data: (u) {
          _hydrate(u);
          final email = u['email'] as String? ?? '';

          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
            children: [
              Text(
                email,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
              ),
              if (ref.watch(isStaffInCurrentTenantProvider)) ...[
                const SizedBox(height: 12),
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.event_note_outlined),
                  title: const Text('Coach bookings'),
                  subtitle: const Text('Confirm or decline requests'),
                  onTap: () => context.push('/catalog/coach-bookings'),
                ),
              ],
              const SizedBox(height: 20),
              TextField(
                controller: _name,
                decoration: const InputDecoration(
                  labelText: 'Name',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _headline,
                decoration: const InputDecoration(
                  labelText: 'Headline',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _bio,
                minLines: 2,
                maxLines: 5,
                decoration: const InputDecoration(
                  labelText: 'Bio',
                  border: OutlineInputBorder(),
                  alignLabelWithHint: true,
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _phone,
                keyboardType: TextInputType.phone,
                decoration: const InputDecoration(
                  labelText: 'Phone',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 12),
              InputDecorator(
                decoration: const InputDecoration(
                  labelText: 'Timezone',
                  border: OutlineInputBorder(),
                ),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<String>(
                    isExpanded: true,
                    value: _timezone,
                    items: _timezoneChoices()
                        .map((z) => DropdownMenuItem(value: z, child: Text(z)))
                        .toList(),
                    onChanged: (v) => setState(() => _timezone = v),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              InputDecorator(
                decoration: const InputDecoration(
                  labelText: 'Language',
                  border: OutlineInputBorder(),
                ),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<String>(
                    isExpanded: true,
                    value: _locale,
                    items: _localeChoices()
                        .entries
                        .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value)))
                        .toList(),
                    onChanged: (v) => setState(() => _locale = v),
                  ),
                ),
              ),
              const SizedBox(height: 24),
              FilledButton(
                onPressed: _saving ? null : _save,
                child: _saving
                    ? const SizedBox(
                        height: 22,
                        width: 22,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Save profile'),
              ),
            ],
          );
        },
      ),
    );
  }
}
