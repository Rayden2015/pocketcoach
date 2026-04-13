import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/api/pocket_coach_api.dart';
import 'package:pocket_coach_mobile/models/search_course_hit.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';
import 'package:pocket_coach_mobile/router/app_paths.dart';

/// Global course search (enrolled + member spaces), parity with web `/search`.
class SearchScreen extends ConsumerStatefulWidget {
  const SearchScreen({super.key, this.initialQuery});

  final String? initialQuery;

  @override
  ConsumerState<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends ConsumerState<SearchScreen> {
  late final TextEditingController _q;
  var _loading = false;
  String? _error;
  List<SearchCourseHit> _hits = [];

  @override
  void initState() {
    super.initState();
    _q = TextEditingController(text: widget.initialQuery ?? '');
  }

  @override
  void dispose() {
    _q.dispose();
    super.dispose();
  }

  Future<void> _run() async {
    final query = _q.text.trim();
    if (query.length < 2) {
      setState(() {
        _hits = [];
        _error = 'Enter at least 2 characters.';
      });
      return;
    }
    final token = ref.read(sessionProvider).valueOrNull;
    if (token == null) {
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final list = await ref.read(apiProvider).searchCourses(bearer: token, query: query);
      if (mounted) {
        setState(() {
          _hits = list;
          _loading = false;
        });
      }
    } on ApiException catch (e) {
      if (mounted) {
        setState(() {
          _loading = false;
          _error = e.message ?? 'Search failed';
        });
      }
    }
  }

  Future<void> _openCourse(SearchCourseHit hit) async {
    final slug = hit.tenantSlug;
    if (slug != null && slug.isNotEmpty) {
      await ref.read(tenantSlugProvider.notifier).setSlug(slug);
    }
    if (!mounted) {
      return;
    }
    context.go(AppPaths.catalogCourse(hit.id));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Search courses'),
      ),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _q,
                    decoration: const InputDecoration(
                      hintText: 'Course title or summary…',
                      border: OutlineInputBorder(),
                      isDense: true,
                    ),
                    textInputAction: TextInputAction.search,
                    onSubmitted: (_) => _run(),
                  ),
                ),
                const SizedBox(width: 8),
                FilledButton(
                  onPressed: _loading ? null : _run,
                  child: _loading
                      ? const SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Text('Search'),
                ),
              ],
            ),
          ),
          if (_error != null)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
            ),
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
              itemCount: _hits.length,
              itemBuilder: (context, i) {
                final h = _hits[i];
                return Card(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: ListTile(
                    title: Text(h.title),
                    subtitle: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (h.tenantName != null) Text(h.tenantName!, style: Theme.of(context).textTheme.labelSmall),
                        if (h.programTitle != null) Text(h.programTitle!, style: Theme.of(context).textTheme.bodySmall),
                        if (h.summary != null && h.summary!.isNotEmpty)
                          Text(
                            h.summary!,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                                ),
                          ),
                      ],
                    ),
                    isThreeLine: true,
                    onTap: () => _openCourse(h),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
