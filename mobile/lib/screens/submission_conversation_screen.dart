import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/api/pocket_coach_api.dart';
import 'package:pocket_coach_mobile/models/conversation_models.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';

enum SubmissionConversationKind { reflection, lesson }

class SubmissionConversationScreen extends ConsumerStatefulWidget {
  const SubmissionConversationScreen({
    super.key,
    required this.kind,
    required this.subjectId,
    this.title,
  });

  final SubmissionConversationKind kind;
  final int subjectId;
  final String? title;

  @override
  ConsumerState<SubmissionConversationScreen> createState() =>
      _SubmissionConversationScreenState();
}

class _SubmissionConversationScreenState
    extends ConsumerState<SubmissionConversationScreen> {
  final _composer = TextEditingController();
  var _sending = false;
  List<ConversationMessage> _messages = const [];
  var _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      // ignore: discarded_futures
      _load();
    });
  }

  @override
  void dispose() {
    _composer.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    final token = ref.read(sessionProvider).valueOrNull;
    final slug = ref.read(tenantSlugProvider);
    if (token == null || token.isEmpty) {
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final api = ref.read(apiProvider);
      final rows = widget.kind == SubmissionConversationKind.reflection
          ? await api.fetchReflectionConversation(
              bearer: token,
              tenantSlug: slug,
              reflectionResponseId: widget.subjectId,
            )
          : await api.fetchLessonConversation(
              bearer: token,
              tenantSlug: slug,
              lessonProgressId: widget.subjectId,
            );
      if (mounted) {
        setState(() {
          _messages = rows;
          _loading = false;
        });
      }
    } on ApiException catch (e) {
      if (mounted) {
        setState(() {
          _error = e.message ?? 'Could not load conversation';
          _loading = false;
        });
      }
    }
  }

  Future<void> _send() async {
    final text = _composer.text.trim();
    if (text.isEmpty) {
      return;
    }
    final token = ref.read(sessionProvider).valueOrNull;
    final slug = ref.read(tenantSlugProvider);
    if (token == null || token.isEmpty) {
      return;
    }
    setState(() => _sending = true);
    try {
      final api = ref.read(apiProvider);
      final msg = widget.kind == SubmissionConversationKind.reflection
          ? await api.postReflectionConversationMessage(
              bearer: token,
              tenantSlug: slug,
              reflectionResponseId: widget.subjectId,
              body: text,
            )
          : await api.postLessonConversationMessage(
              bearer: token,
              tenantSlug: slug,
              lessonProgressId: widget.subjectId,
              body: text,
            );
      if (mounted) {
        setState(() {
          _messages = [..._messages, msg];
          _composer.clear();
        });
      }
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.message ?? 'Could not send message')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _sending = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title ?? 'Conversation'),
      ),
      body: Column(
        children: [
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? Center(child: Text(_error!))
                    : _messages.isEmpty
                        ? const Center(child: Text('No messages yet. Say hello to your coach.'))
                        : ListView.builder(
                            padding: const EdgeInsets.all(16),
                            itemCount: _messages.length,
                            itemBuilder: (context, index) {
                              final m = _messages[index];
                              return Padding(
                                padding: const EdgeInsets.only(bottom: 12),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      m.authorName ?? 'Member',
                                      style: Theme.of(context).textTheme.labelLarge,
                                    ),
                                    const SizedBox(height: 4),
                                    Text(m.body),
                                  ],
                                ),
                              );
                            },
                          ),
          ),
          SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _composer,
                      minLines: 1,
                      maxLines: 4,
                      decoration: const InputDecoration(
                        hintText: 'Write a message…',
                        border: OutlineInputBorder(),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton.filled(
                    onPressed: _sending ? null : _send,
                    icon: _sending
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.send),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
