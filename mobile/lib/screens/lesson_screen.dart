import 'package:flutter/material.dart';
import 'package:flutter_markdown/flutter_markdown.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/api/pocket_coach_api.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/learning_providers.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';
import 'package:url_launcher/url_launcher.dart';

class LessonScreen extends ConsumerStatefulWidget {
  const LessonScreen({
    super.key,
    required this.courseId,
    required this.lessonId,
  });

  final int courseId;
  final int lessonId;

  @override
  ConsumerState<LessonScreen> createState() => _LessonScreenState();
}

class _LessonScreenState extends ConsumerState<LessonScreen> {
  var _saving = false;
  var _notesHydrated = false;
  var _notesPublic = false;
  final _notes = TextEditingController();

  void _switchLesson(int lessonId) {
    if (context.canPop()) {
      context.pop();
    }
    context.push('/course/${widget.courseId}/lesson/$lessonId');
  }

  @override
  void dispose() {
    _notes.dispose();
    super.dispose();
  }

  Future<void> _openUrl(String url) async {
    final uri = Uri.tryParse(url);
    if (uri == null) {
      return;
    }
    if (!await canLaunchUrl(uri)) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Could not open link')),
        );
      }
      return;
    }
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  Future<void> _saveFeedbackOnly() async {
    final text = _notes.text.trim();
    if (text.isEmpty) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Add feedback before saving')),
        );
      }
      return;
    }
    setState(() => _saving = true);
    try {
      final token = ref.read(sessionProvider).valueOrNull;
      if (token == null) {
        throw StateError('Not signed in');
      }
      final slug = ref.read(tenantSlugProvider);
      await ref.read(apiProvider).updateLessonProgress(
            bearer: token,
            tenantSlug: slug,
            lessonId: widget.lessonId,
            notes: text,
            notesIsPublic: _notesPublic,
          );
      if (!mounted) {
        return;
      }
      ref.invalidate(courseDetailProvider(widget.courseId));
      ref.invalidate(continueLearningProvider);
      ref.invalidate(learningSummaryProvider);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Feedback saved')),
      );
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.message ?? 'Request failed')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  Future<void> _markComplete({required bool completed}) async {
    setState(() => _saving = true);
    try {
      final token = ref.read(sessionProvider).valueOrNull;
      if (token == null) {
        throw StateError('Not signed in');
      }
      final slug = ref.read(tenantSlugProvider);
      await ref.read(apiProvider).updateLessonProgress(
            bearer: token,
            tenantSlug: slug,
            lessonId: widget.lessonId,
            completed: completed,
            notes: _notes.text.trim().isEmpty ? null : _notes.text.trim(),
            notesIsPublic: _notesPublic,
          );
      if (!mounted) {
        return;
      }
      ref.invalidate(courseDetailProvider(widget.courseId));
      ref.invalidate(continueLearningProvider);
      ref.invalidate(learningSummaryProvider);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(completed ? 'Marked complete' : 'Marked incomplete')),
      );
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.message ?? 'Request failed')),
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
    final async = ref.watch(courseDetailProvider(widget.courseId));

    return Scaffold(
      appBar: AppBar(
        title: async.maybeWhen(
          data: (c) {
            final lesson = c.findLesson(widget.lessonId);
            return Text(lesson?.title ?? 'Lesson');
          },
          orElse: () => const Text('Lesson'),
        ),
      ),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Padding(padding: const EdgeInsets.all(24), child: Text('$e'))),
        data: (course) {
          final lesson = course.findLesson(widget.lessonId);
          if (lesson == null) {
            return const Center(child: Text('Lesson not found'));
          }

            if (!_notesHydrated) {
            _notesHydrated = true;
            final existing = lesson.progress?.notes;
            if (existing != null && existing.isNotEmpty) {
              WidgetsBinding.instance.addPostFrameCallback((_) {
                if (mounted) {
                  _notes.text = existing;
                }
              });
            }
            _notesPublic = lesson.progress?.notesIsPublic ?? false;
          }

          final (prev, next) = course.lessonNeighbors(widget.lessonId);

          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
            children: [
              if (lesson.progress?.isComplete == true)
                Padding(
                  padding: const EdgeInsets.only(bottom: 16),
                  child: Align(
                    alignment: Alignment.centerLeft,
                    child: Chip(
                      avatar: Icon(Icons.check_circle, size: 18, color: Theme.of(context).colorScheme.primary),
                      label: const Text('Lesson completed'),
                    ),
                  ),
                ),
              if (lesson.mediaUrl != null && lesson.mediaUrl!.isNotEmpty) ...[
                FilledButton.tonalIcon(
                  onPressed: () => _openUrl(lesson.mediaUrl!),
                  icon: const Icon(Icons.link),
                  label: const Text('Open media / resource'),
                ),
                const SizedBox(height: 16),
              ],
              SelectionArea(
                child: MarkdownBody(
                  data: lesson.body?.trim().isNotEmpty == true ? lesson.body! : '_No lesson text yet._',
                  styleSheet: MarkdownStyleSheet.fromTheme(Theme.of(context)).copyWith(
                    p: Theme.of(context).textTheme.bodyLarge,
                  ),
                ),
              ),
              const SizedBox(height: 24),
              Text(
                'Feedback & notes',
                style: Theme.of(context).textTheme.titleSmall,
              ),
              const SizedBox(height: 8),
              Text(
                'Share reflections or questions for this lesson. Saved with completion or via Save feedback.',
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _notes,
                minLines: 3,
                maxLines: 6,
                decoration: const InputDecoration(
                  hintText: 'What stood out? What will you try next?',
                  border: OutlineInputBorder(),
                  alignLabelWithHint: true,
                ),
              ),
              const SizedBox(height: 8),
              CheckboxListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('Share with other learners'),
                subtitle: const Text('Show this note to enrolled learners on this lesson.'),
                value: _notesPublic,
                onChanged: _saving
                    ? null
                    : (v) {
                        setState(() => _notesPublic = v ?? false);
                      },
              ),
              const SizedBox(height: 12),
              Align(
                alignment: Alignment.centerLeft,
                child: FilledButton.tonal(
                  onPressed: _saving ? null : _saveFeedbackOnly,
                  child: const Text('Save feedback'),
                ),
              ),
              const SizedBox(height: 20),
              Row(
                children: [
                  Expanded(
                    child: FilledButton(
                      onPressed: _saving ? null : () => _markComplete(completed: true),
                      child: const Text('Mark complete'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: OutlinedButton(
                      onPressed: _saving ? null : () => _markComplete(completed: false),
                      child: const Text('Mark incomplete'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 32),
              Row(
                children: [
                  if (prev != null)
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => _switchLesson(prev.id),
                        icon: const Icon(Icons.arrow_back),
                        label: const Text('Previous'),
                      ),
                    ),
                  if (prev != null && next != null) const SizedBox(width: 12),
                  if (next != null)
                    Expanded(
                      child: FilledButton.tonalIcon(
                        onPressed: () => _switchLesson(next.id),
                        icon: const Icon(Icons.arrow_forward),
                        label: const Text('Next'),
                      ),
                    ),
                ],
              ),
            ],
          );
        },
      ),
    );
  }
}
