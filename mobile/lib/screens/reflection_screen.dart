import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/api/pocket_coach_api.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/engagement_providers.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';

class ReflectionScreen extends ConsumerStatefulWidget {
  const ReflectionScreen({super.key, required this.promptId});

  final int promptId;

  @override
  ConsumerState<ReflectionScreen> createState() => _ReflectionScreenState();
}

class _ReflectionScreenState extends ConsumerState<ReflectionScreen> {
  final _controller = TextEditingController();
  var _saving = false;
  var _viewRecorded = false;
  var _viewCallbackScheduled = false;
  var _responseFieldsSeeded = false;
  var _isPublic = false;

  @override
  void didUpdateWidget(ReflectionScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.promptId != widget.promptId) {
      _responseFieldsSeeded = false;
      _viewCallbackScheduled = false;
      _viewRecorded = false;
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _ensureViewRecorded(String token, String slug) async {
    if (_viewRecorded) {
      return;
    }
    try {
      await ref.read(apiProvider).recordReflectionView(
            bearer: token,
            tenantSlug: slug,
            promptId: widget.promptId,
          );
      _viewRecorded = true;
    } on ApiException {
      // ignore; prompt may be unreadable in edge cases
    }
  }

  Future<void> _save(String token, String slug) async {
    setState(() => _saving = true);
    try {
      await ref.read(apiProvider).upsertReflectionResponse(
            bearer: token,
            tenantSlug: slug,
            promptId: widget.promptId,
            body: _controller.text.trim(),
            isPublic: _isPublic,
          );
      if (mounted) {
        ref.invalidate(reflectionLatestProvider);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Reflection saved')),
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
    final token = ref.watch(sessionProvider).valueOrNull;
    final slug = ref.watch(tenantSlugProvider);
    final promptAsync = ref.watch(reflectionPromptProvider(widget.promptId));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Reflection'),
      ),
      body: promptAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text(e.toString(), textAlign: TextAlign.center),
          ),
        ),
        data: (prompt) {
          if (!_viewCallbackScheduled && token != null && token.isNotEmpty) {
            _viewCallbackScheduled = true;
            WidgetsBinding.instance.addPostFrameCallback((_) {
              if (!mounted) {
                return;
              }
              // ignore: discarded_futures
              _ensureViewRecorded(token, slug);
            });
          }
          if (!_responseFieldsSeeded) {
            _responseFieldsSeeded = true;
            WidgetsBinding.instance.addPostFrameCallback((_) {
              if (!mounted) {
                return;
              }
              final mine = prompt.myResponse;
              setState(() {
                _controller.text = mine?.body ?? '';
                _isPublic = mine?.isPublic ?? false;
              });
            });
          }
          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Text(
                prompt.title,
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              const SizedBox(height: 16),
              Text(prompt.body),
              const SizedBox(height: 24),
                    Text(
                      'Your response',
                      style: Theme.of(context).textTheme.titleSmall,
                    ),
              const SizedBox(height: 8),
              TextField(
                controller: _controller,
                minLines: 5,
                maxLines: 12,
                decoration: const InputDecoration(
                  border: OutlineInputBorder(),
                  alignLabelWithHint: true,
                ),
              ),
              SwitchListTile(
                title: const Text('Share response publicly in this space'),
                subtitle: const Text('Other learners can see your text when the coach enables peer sharing.'),
                value: _isPublic,
                onChanged: (v) => setState(() => _isPublic = v),
              ),
              const SizedBox(height: 20),
              FilledButton(
                onPressed: _saving || token == null || token.isEmpty
                    ? null
                    : () => _save(token, slug),
                child: _saving
                    ? const SizedBox(
                        height: 22,
                        width: 22,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Save response'),
              ),
            ],
          );
        },
      ),
    );
  }
}
