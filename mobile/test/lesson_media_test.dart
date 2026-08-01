import 'package:flutter_test/flutter_test.dart';
import 'package:pocket_coach_mobile/models/course_detail.dart';
import 'package:pocket_coach_mobile/utils/lesson_media.dart';

void main() {
  group('LessonMedia.youtubeEmbedUrl', () {
    test('converts watch URLs', () {
      expect(
        LessonMedia.youtubeEmbedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ'),
        'https://www.youtube.com/embed/dQw4w9WgXcQ',
      );
    });

    test('converts youtu.be URLs', () {
      expect(
        LessonMedia.youtubeEmbedUrl('https://youtu.be/dQw4w9WgXcQ'),
        'https://www.youtube.com/embed/dQw4w9WgXcQ',
      );
    });

    test('passes through embed URLs', () {
      expect(
        LessonMedia.youtubeEmbedUrl('https://www.youtube.com/embed/dQw4w9WgXcQ'),
        'https://www.youtube.com/embed/dQw4w9WgXcQ',
      );
    });

    test('returns null for non-youtube', () {
      expect(LessonMedia.youtubeEmbedUrl('https://example.com/video.mp4'), isNull);
    });
  });

  group('LessonMedia.supportsStudio', () {
    test('true for image with url', () {
      expect(
        LessonMedia.supportsStudio(lessonType: 'image', mediaUrl: 'https://x/a.png'),
        isTrue,
      );
    });

    test('false for text', () {
      expect(
        LessonMedia.supportsStudio(lessonType: 'text', mediaUrl: 'https://x/a.png'),
        isFalse,
      );
    });

    test('false without media', () {
      expect(
        LessonMedia.supportsStudio(lessonType: 'video', mediaUrl: null),
        isFalse,
      );
    });
  });

  group('LessonOutline.supportsMediaStudio', () {
    test('matches helper', () {
      final lesson = LessonOutline(
        id: 1,
        title: 'Chart',
        slug: 'chart',
        lessonType: 'image',
        body: null,
        mediaUrl: 'https://example.com/a.png',
      );
      expect(lesson.supportsMediaStudio, isTrue);
    });
  });
}
