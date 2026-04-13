import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

const _kTenantSlug = 'pocket_coach_tenant_slug';

final tenantSlugProvider = NotifierProvider<TenantSlugNotifier, String>(TenantSlugNotifier.new);

class TenantSlugNotifier extends Notifier<String> {
  @override
  String build() {
    Future.microtask(_hydrate);
    return 'adeola';
  }

  Future<void> _hydrate() async {
    final prefs = await SharedPreferences.getInstance();
    final s = prefs.getString(_kTenantSlug);
    if (s != null && s.trim().isNotEmpty) {
      state = s.trim();
    }
  }

  Future<void> setSlug(String raw) async {
    final v = raw.trim();
    state = v.isEmpty ? state : v;
    if (v.isEmpty) {
      return;
    }
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_kTenantSlug, v);
  }

  /// If the saved slug is not in [memberships], switch to the first space (web parity with enrolled tenants).
  Future<void> alignWithMemberships(List<Map<String, dynamic>> memberships) async {
    if (memberships.isEmpty) {
      return;
    }
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getString(_kTenantSlug)?.trim() ?? '';
    final slugs = memberships
        .map((m) => m['tenant_slug'] as String?)
        .whereType<String>()
        .where((s) => s.isNotEmpty)
        .toList();
    if (slugs.isEmpty) {
      return;
    }
    if (saved.isNotEmpty && slugs.contains(saved)) {
      if (state != saved) {
        state = saved;
      }
      return;
    }
    await setSlug(slugs.first);
  }
}
