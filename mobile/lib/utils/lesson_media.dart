/// Helpers for learner lesson media (parity with web studio).
library;

class LessonMedia {
  LessonMedia._();

  static const studioTypes = {'image', 'video', 'audio', 'pdf'};

  static bool supportsStudio({
    required String lessonType,
    String? mediaUrl,
  }) {
    final url = mediaUrl?.trim();
    return studioTypes.contains(lessonType) && url != null && url.isNotEmpty;
  }

  /// Convert a YouTube watch / short URL to an embed URL, if applicable.
  static String? youtubeEmbedUrl(String? url) {
    if (url == null || url.trim().isEmpty) {
      return null;
    }
    final watch = RegExp(
      r'youtube\.com/watch\?(?:[^#]*&)?v=([a-zA-Z0-9_-]{11})',
    ).firstMatch(url);
    if (watch != null) {
      return 'https://www.youtube.com/embed/${watch.group(1)}';
    }
    final short = RegExp(r'youtu\.be/([a-zA-Z0-9_-]{11})').firstMatch(url);
    if (short != null) {
      return 'https://www.youtube.com/embed/${short.group(1)}';
    }
    if (url.contains('youtube.com/embed/')) {
      return url;
    }
    return null;
  }

  static bool isDirectVideo(String url) {
    final lower = url.toLowerCase();
    return lower.endsWith('.mp4') ||
        lower.endsWith('.m3u8') ||
        lower.endsWith('.webm') ||
        lower.endsWith('.mov') ||
        lower.contains('/video/') ||
        lower.contains('mime=video');
  }

  static bool isDirectAudio(String url) {
    final lower = url.toLowerCase();
    return lower.endsWith('.mp3') ||
        lower.endsWith('.m4a') ||
        lower.endsWith('.wav') ||
        lower.endsWith('.aac') ||
        lower.endsWith('.ogg');
  }
}
