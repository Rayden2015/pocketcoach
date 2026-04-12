import 'dart:async';
import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_markdown/flutter_markdown.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/api/pocket_coach_api.dart';
import 'package:pocket_coach_mobile/models/course_detail.dart';
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
  final _scrollController = ScrollController();
  double _scrollProgress = 0;
  Timer? _progressDebounce;
  int _lastSentPercent = 0;

  void _switchLesson(int lessonId) {
    if (context.canPop()) {
      context.pop();
    }
    context.push('/course/${widget.courseId}/lesson/$lessonId');
  }

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _progressDebounce?.cancel();
    _scrollController.removeListener(_onScroll);
    _scrollController.dispose();
    _notes.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_scrollController.hasClients) {
      return;
    }
    final max = _scrollController.position.maxScrollExtent;
    final next = max <= 0 ? 1.0 : (_scrollController.offset / max).clamp(0.0, 1.0);
    if (mounted) {
      setState(() => _scrollProgress = next);
    }
    _scheduleProgressPing();
  }

  void _scheduleProgressPing() {
    _progressDebounce?.cancel();
    _progressDebounce = Timer(const Duration(seconds: 2), _flushProgress);
  }

  Future<void> _flushProgress() async {
    final token = ref.read(sessionProvider).valueOrNull;
    if (token == null) {
      return;
    }
    final slug = ref.read(tenantSlugProvider);
    final async = ref.read(courseDetailProvider(widget.courseId));
    final course = async.valueOrNull;
    final lesson = course?.findLesson(widget.lessonId);
    if (lesson == null || lesson.progress?.isComplete == true) {
      return;
    }
    final pct = (_scrollProgress * 100).round().clamp(0, 100);
    if (pct <= _lastSentPercent) {
      return;
    }
    _lastSentPercent = pct;
    try {
      await ref.read(apiProvider).updateLessonProgress(
            bearer: token,
            tenantSlug: slug,
            lessonId: widget.lessonId,
            contentProgressPercent: pct,
          );
    } on ApiException {
      /* ignore */
    }
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

  Future<void> _setCompletion({required bool completed, bool silent = false}) async {
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
      if (!silent && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(completed ? 'Marked complete' : 'Marked incomplete'),
          ),
        );
      }
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

  Future<void> _nextAndComplete(LessonOutline next) async {
    await _setCompletion(completed: true, silent: true);
    if (!mounted) {
      return;
    }
    _switchLesson(next.id);
  }

  double _barValue(LessonOutline lesson) {
    if (lesson.progress?.isComplete == true) {
      return 1;
    }
    final stored = lesson.progress?.contentProgressPercent;
    final storedFrac = stored != null ? (stored / 100).clamp(0.0, 1.0) : 0.0;
    return math.max(storedFrac, _scrollProgress);
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
        actions: [
          IconButton(
            icon: const Icon(Icons.home_outlined),
            tooltip: 'Home',
            onPressed: () => context.go('/home'),
          ),
          IconButton(
            icon: const Icon(Icons.menu_book_outlined),
            tooltip: 'Course overview',
            onPressed: () => context.go('/course/${widget.courseId}'),
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(4),
          child: async.maybeWhen(
            data: (c) {
              final lesson = c.findLesson(widget.lessonId);
              if (lesson == null) {
                return const SizedBox.shrink();
              }
              return LinearProgressIndicator(
                value: _barValue(lesson),
                minHeight: 4,
                backgroundColor: Theme.of(context).colorScheme.surfaceContainerHighest,
              );
            },
            orElse: () => const SizedBox.shrink(),
          ),
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
            final stored = lesson.progress?.contentProgressPercent;
            if (stored != null && stored > _lastSentPercent) {
              _lastSentPercent = stored;
            }
          }

          final (prev, next) = course.lessonNeighbors(widget.lessonId);
          final complete = lesson.progress?.isComplete == true;

          return ListView(
            controller: _scrollController,
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
            children: [
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
                'Share reflections or questions for this lesson. Save notes anytime; completion uses the button below.',
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
                  child: const Text('Save notes'),
                ),
              ),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: complete
                    ? OutlinedButton(
                        onPressed: _saving ? null : () => _setCompletion(completed: false),
                        child: const Text('Mark incomplete'),
                      )
                    : FilledButton(
                        onPressed: _saving ? null : () => _setCompletion(completed: true),
                        child: const Text('Mark complete'),
                      ),
              ),
              const SizedBox(height: 32),
              Row(
                children: [
                  if (prev != null)
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: _saving ? null : () => _switchLesson(prev.id),
                        icon: const Icon(Icons.arrow_back),
                        label: const Text('Previous'),
                      ),
                    ),
                  if (prev != null && next != null) const SizedBox(width: 12),
                  if (next != null)
                    Expanded(
                      child: FilledButton.tonalIcon(
                        onPressed: _saving ? null : () => _nextAndComplete(next),
                        icon: const Icon(Icons.arrow_forward),
                        label: const Text('Next'),
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 28),
              Text(
                'Leave this lesson',
                style: Theme.of(context).textTheme.titleSmall,
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  OutlinedButton.icon(
                    onPressed: _saving ? null : () => context.go('/course/${widget.courseId}'),
                    icon: const Icon(Icons.menu_book_outlined),
                    label: const Text('Course overview'),
                  ),
                  OutlinedButton.icon(
                    onPressed: _saving ? null : () => context.go('/home'),
                    icon: const Icon(Icons.home_outlined),
                    label: const Text('Home'),
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
