import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:pocket_coach_mobile/models/course_detail.dart';
import 'package:pocket_coach_mobile/providers/learning_providers.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';
import 'package:pocket_coach_mobile/screens/lesson_screen.dart';
import 'package:pocket_coach_mobile/screens/lesson_studio_screen.dart';
import 'package:pocket_coach_mobile/services/token_store.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  final course = CourseDetail(
    id: 3,
    title: 'Atomic starters',
    slug: 'atomic-starters',
    modules: [
      ModuleOutline(
        id: 1,
        title: 'Cue',
        slug: 'cue',
        lessons: [
          LessonOutline(
            id: 21,
            title: 'Studio E2E — Image chart',
            slug: 'studio-e2e-image',
            lessonType: 'image',
            body: 'Inspect the chart closely in studio mode.',
            mediaUrl: 'https://example.com/chart.png',
            progress: LessonProgressSnapshot(
              notes: 'Prior note',
              notesIsPublic: false,
              contentProgressPercent: 10,
            ),
          ),
          LessonOutline(
            id: 22,
            title: 'Studio E2E — Video',
            slug: 'studio-e2e-video',
            lessonType: 'video',
            body: 'Watch in studio',
            mediaUrl: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
          ),
          LessonOutline(
            id: 1,
            title: 'Welcome',
            slug: 'welcome',
            lessonType: 'text',
            body: 'Hello',
            mediaUrl: null,
          ),
        ],
      ),
    ],
  );

  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  testWidgets('lesson screen shows Open studio for image lessons', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          tokenStoreProvider.overrideWithValue(_FakeTokenStore(token: 'test-token')),
          sessionProvider.overrideWith(() => _FakeSession('test-token')),
          courseDetailProvider(3).overrideWith((ref) async => course),
        ],
        child: const MaterialApp(
          home: LessonScreen(courseId: 3, lessonId: 21),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Open studio'), findsOneWidget);
    expect(find.text('Full window media with notes beside it'), findsOneWidget);
    expect(find.text('Feedback & notes'), findsOneWidget);
  });

  testWidgets('studio screen shows exit, fullscreen, and notes', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          tokenStoreProvider.overrideWithValue(_FakeTokenStore(token: 'test-token')),
          sessionProvider.overrideWith(() => _FakeSession('test-token')),
          courseDetailProvider(3).overrideWith((ref) async => course),
        ],
        child: const MaterialApp(
          home: LessonStudioScreen(courseId: 3, lessonId: 21),
        ),
      ),
    );
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 100));

    expect(find.textContaining('Exit studio'), findsOneWidget);
    expect(find.text('Your notes'), findsOneWidget);
    expect(find.text('IMAGE STUDIO'), findsOneWidget);
    expect(find.text('Studio E2E — Image chart'), findsWidgets);
    expect(find.byTooltip('Fullscreen'), findsOneWidget);
    expect(find.text('Save notes'), findsOneWidget);
    expect(find.text('Mark complete'), findsOneWidget);
  });

  testWidgets('studio does not show chrome for text lessons', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          tokenStoreProvider.overrideWithValue(_FakeTokenStore(token: 'test-token')),
          sessionProvider.overrideWith(() => _FakeSession('test-token')),
          courseDetailProvider(3).overrideWith((ref) async => course),
        ],
        child: const MaterialApp(
          home: LessonStudioScreen(courseId: 3, lessonId: 1),
        ),
      ),
    );
    await tester.pump();
    expect(find.textContaining('Exit studio'), findsNothing);
    expect(find.text('Your notes'), findsNothing);
    expect(find.textContaining('Studio is available for image'), findsOneWidget);
  });
}

class _FakeTokenStore extends TokenStore {
  _FakeTokenStore({this.token});

  final String? token;

  @override
  Future<String?> read() async => token;

  @override
  Future<void> write(String token) async {}

  @override
  Future<void> clear() async {}
}

class _FakeSession extends SessionNotifier {
  _FakeSession(this._token);

  final String _token;

  @override
  Future<String?> build() async => _token;
}
