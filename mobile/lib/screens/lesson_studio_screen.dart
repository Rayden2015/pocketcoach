import 'dart:async';
import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/api/pocket_coach_api.dart';
import 'package:pocket_coach_mobile/models/course_detail.dart';
import 'package:pocket_coach_mobile/providers/api_provider.dart';
import 'package:pocket_coach_mobile/providers/learning_providers.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/providers/tenant_slug_provider.dart';
import 'package:pocket_coach_mobile/router/app_paths.dart';
import 'package:pocket_coach_mobile/widgets/lesson_media_view.dart';

/// Full-window media studio (parity with web `/learn/lessons/{id}/studio`).
class LessonStudioScreen extends ConsumerStatefulWidget {
  const LessonStudioScreen({
    super.key,
    required this.courseId,
    required this.lessonId,
  });

  final int courseId;
  final int lessonId;

  @override
  ConsumerState<LessonStudioScreen> createState() => _LessonStudioScreenState();
}

class _LessonStudioScreenState extends ConsumerState<LessonStudioScreen> {
  var _saving = false;
  var _notesHydrated = false;
  var _notesPublic = false;
  var _notesVisible = true;
  var _immersive = false;
  var _mediaFraction = 0.0;
  int _positionSeconds = 0;
  int _lastSentPercent = 0;
  Timer? _progressDebounce;
  final _notes = TextEditingController();

  @override
  void dispose() {
    _progressDebounce?.cancel();
    _notes.dispose();
    if (_immersive) {
      SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
    }
    super.dispose();
  }

  @override
  void didUpdateWidget(covariant LessonStudioScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.lessonId != widget.lessonId) {
      _notesHydrated = false;
      _mediaFraction = 0;
      _positionSeconds = 0;
      _lastSentPercent = 0;
      _notes.clear();
    }
  }

  void _onMediaProgress({required double fraction, int? positionSeconds}) {
    final next = math.max(_mediaFraction, fraction);
    if (positionSeconds != null) {
      _positionSeconds = math.max(_positionSeconds, positionSeconds);
    }
    if ((next - _mediaFraction).abs() > 0.01 || positionSeconds != null) {
      if (!mounted) {
        return;
      }
      setState(() => _mediaFraction = next);
      _scheduleProgressPing();
    }
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
    final course = ref.read(courseDetailProvider(widget.courseId)).valueOrNull;
    final lesson = course?.findLesson(widget.lessonId);
    if (lesson == null || lesson.progress?.isComplete == true) {
      return;
    }
    final pct = (_mediaFraction * 100).round().clamp(0, 100);
    if (pct <= _lastSentPercent && _positionSeconds == 0) {
      return;
    }
    if (pct > _lastSentPercent) {
      _lastSentPercent = pct;
    }
    try {
      await ref.read(apiProvider).updateLessonProgress(
            bearer: token,
            tenantSlug: slug,
            lessonId: widget.lessonId,
            contentProgressPercent: pct,
            positionSeconds: _positionSeconds > 0 ? _positionSeconds : null,
          );
    } on ApiException {
      /* ignore */
    }
  }

  Future<void> _toggleImmersive() async {
    setState(() => _immersive = !_immersive);
    if (_immersive) {
      await SystemChrome.setEnabledSystemUIMode(SystemUiMode.immersiveSticky);
    } else {
      await SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
    }
  }

  Future<void> _saveNotes() async {
    final text = _notes.text.trim();
    if (text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Add notes before saving')),
      );
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
            contentProgressPercent: (_mediaFraction * 100).round(),
            positionSeconds: _positionSeconds > 0 ? _positionSeconds : null,
          );
      if (!mounted) {
        return;
      }
      ref.invalidate(courseDetailProvider(widget.courseId));
      ref.invalidate(continueLearningProvider);
      ref.invalidate(learningSummaryProvider);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Notes saved')),
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

  Future<void> _setCompletion({required bool completed}) async {
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
            contentProgressPercent: completed ? 100 : (_mediaFraction * 100).round(),
            positionSeconds: _positionSeconds > 0 ? _positionSeconds : null,
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

  Future<void> _goNext(LessonOutline next) async {
    await _setCompletion(completed: true);
    if (!mounted) {
      return;
    }
    if (next.supportsMediaStudio) {
      context.go(AppPaths.catalogCourseLessonStudio(widget.courseId, next.id));
    } else {
      context.go(AppPaths.catalogCourseLesson(widget.courseId, next.id));
    }
  }

  Widget _notesPanel(LessonOutline lesson, {required bool complete}) {
    return Material(
      color: const Color(0xFF1C1917),
      child: SafeArea(
        top: false,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
          children: [
            Text(
              'Your notes',
              style: Theme.of(context).textTheme.titleSmall?.copyWith(color: Colors.white),
            ),
            const SizedBox(height: 4),
            Text(
              'Keep notes visible while you study the media.',
              style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Colors.white60),
            ),
            if (lesson.body != null && lesson.body!.trim().isNotEmpty) ...[
              const SizedBox(height: 12),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.black38,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.white12),
                ),
                child: Text(
                  lesson.body!.trim(),
                  style: const TextStyle(color: Colors.white70, fontSize: 12, height: 1.4),
                ),
              ),
            ],
            const SizedBox(height: 12),
            TextField(
              controller: _notes,
              minLines: 5,
              maxLines: 10,
              style: const TextStyle(color: Colors.white),
              decoration: InputDecoration(
                hintText: 'Write while you watch or inspect the media…',
                hintStyle: const TextStyle(color: Colors.white38),
                filled: true,
                fillColor: Colors.black54,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
            CheckboxListTile(
              contentPadding: EdgeInsets.zero,
              activeColor: Colors.teal,
              title: const Text(
                'Share with other learners',
                style: TextStyle(color: Colors.white70, fontSize: 13),
              ),
              value: _notesPublic,
              onChanged: _saving
                  ? null
                  : (v) => setState(() => _notesPublic = v ?? false),
            ),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                OutlinedButton(
                  onPressed: _saving ? null : _saveNotes,
                  style: OutlinedButton.styleFrom(foregroundColor: Colors.white),
                  child: const Text('Save notes'),
                ),
                if (complete)
                  OutlinedButton(
                    onPressed: _saving ? null : () => _setCompletion(completed: false),
                    style: OutlinedButton.styleFrom(foregroundColor: Colors.amber.shade200),
                    child: const Text('Mark incomplete'),
                  )
                else
                  FilledButton(
                    onPressed: _saving ? null : () => _setCompletion(completed: true),
                    style: FilledButton.styleFrom(backgroundColor: Colors.teal.shade700),
                    child: const Text('Mark complete'),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(courseDetailProvider(widget.courseId));

    return Theme(
      data: ThemeData.dark(useMaterial3: true).copyWith(
        colorScheme: ColorScheme.fromSeed(
          seedColor: Colors.teal,
          brightness: Brightness.dark,
        ),
      ),
      child: Scaffold(
        backgroundColor: const Color(0xFF0C0A09),
        body: async.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => Center(child: Text('$e')),
          data: (course) {
            final lesson = course.findLesson(widget.lessonId);
            if (lesson == null) {
              return const Center(child: Text('Lesson not found'));
            }
            if (!lesson.supportsMediaStudio) {
              return Center(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Text(
                        'Studio is available for image, video, audio, and PDF lessons with media.',
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 16),
                      FilledButton(
                        onPressed: () {
                          if (context.canPop()) {
                            context.pop();
                          }
                        },
                        child: const Text('Go back'),
                      ),
                    ],
                  ),
                ),
              );
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
              _positionSeconds = lesson.progress?.positionSeconds ?? 0;
              final stored = lesson.progress?.contentProgressPercent;
              if (stored != null && stored > _lastSentPercent) {
                _lastSentPercent = stored;
                _mediaFraction = (stored / 100).clamp(0.0, 1.0);
              }
            }

            final (_, next) = course.lessonNeighbors(widget.lessonId);
            final complete = lesson.progress?.isComplete == true;
            final wide = MediaQuery.sizeOf(context).width >= 700;

            return SafeArea(
              child: Column(
                children: [
                  Material(
                    color: const Color(0xFF0C0A09),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      child: Row(
                        children: [
                          TextButton(
                            onPressed: () {
                              if (context.canPop()) {
                                context.pop();
                              } else {
                                context.go(
                                  AppPaths.catalogCourseLesson(widget.courseId, widget.lessonId),
                                );
                              }
                            },
                            child: const Text('← Exit studio'),
                          ),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  '${lesson.lessonType.toUpperCase()} STUDIO',
                                  style: TextStyle(
                                    color: Colors.teal.shade300,
                                    fontSize: 11,
                                    fontWeight: FontWeight.w700,
                                    letterSpacing: 0.6,
                                  ),
                                ),
                                Text(
                                  lesson.title,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(fontWeight: FontWeight.w600),
                                ),
                              ],
                            ),
                          ),
                          if (!wide)
                            IconButton(
                              tooltip: _notesVisible ? 'Hide notes' : 'Notes',
                              onPressed: () => setState(() => _notesVisible = !_notesVisible),
                              icon: Icon(_notesVisible ? Icons.notes : Icons.notes_outlined),
                            ),
                          IconButton(
                            tooltip: _immersive ? 'Exit fullscreen' : 'Fullscreen',
                            onPressed: _toggleImmersive,
                            icon: Icon(_immersive ? Icons.fullscreen_exit : Icons.fullscreen),
                          ),
                          if (next != null)
                            FilledButton(
                              onPressed: _saving ? null : () => _goNext(next),
                              style: FilledButton.styleFrom(
                                backgroundColor: Colors.white,
                                foregroundColor: Colors.black,
                              ),
                              child: const Text('Next'),
                            ),
                        ],
                      ),
                    ),
                  ),
                  LinearProgressIndicator(
                    value: complete ? 1 : _mediaFraction.clamp(0.0, 1.0),
                    minHeight: 3,
                    backgroundColor: Colors.white10,
                  ),
                  Expanded(
                    child: wide
                        ? Row(
                            children: [
                              Expanded(
                                flex: 3,
                                child: LessonMediaView(
                                  lessonType: lesson.lessonType,
                                  mediaUrl: lesson.mediaUrl!,
                                  title: lesson.title,
                                  initialPositionSeconds: lesson.progress?.positionSeconds,
                                  onProgress: _onMediaProgress,
                                ),
                              ),
                              SizedBox(
                                width: 320,
                                child: _notesPanel(lesson, complete: complete),
                              ),
                            ],
                          )
                        : Column(
                            children: [
                              Expanded(
                                flex: _notesVisible ? 3 : 1,
                                child: LessonMediaView(
                                  lessonType: lesson.lessonType,
                                  mediaUrl: lesson.mediaUrl!,
                                  title: lesson.title,
                                  initialPositionSeconds: lesson.progress?.positionSeconds,
                                  onProgress: _onMediaProgress,
                                ),
                              ),
                              if (_notesVisible)
                                SizedBox(
                                  height: MediaQuery.sizeOf(context).height * 0.38,
                                  child: _notesPanel(lesson, complete: complete),
                                ),
                            ],
                          ),
                  ),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}
