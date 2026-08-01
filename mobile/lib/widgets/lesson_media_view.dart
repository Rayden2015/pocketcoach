import 'package:flutter/material.dart';
import 'package:pocket_coach_mobile/utils/lesson_media.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:video_player/video_player.dart';
import 'package:webview_flutter/webview_flutter.dart';

typedef LessonMediaProgressCallback = void Function({
  required double fraction,
  int? positionSeconds,
});

/// Renders lesson media by type (image / video / audio / pdf / youtube).
class LessonMediaView extends StatefulWidget {
  const LessonMediaView({
    super.key,
    required this.lessonType,
    required this.mediaUrl,
    this.title,
    this.compact = false,
    this.onProgress,
    this.initialPositionSeconds,
  });

  final String lessonType;
  final String mediaUrl;
  final String? title;
  final bool compact;
  final LessonMediaProgressCallback? onProgress;
  final int? initialPositionSeconds;

  @override
  State<LessonMediaView> createState() => _LessonMediaViewState();
}

class _LessonMediaViewState extends State<LessonMediaView> {
  VideoPlayerController? _video;
  WebViewController? _web;
  var _webReady = false;
  var _videoError = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  @override
  void didUpdateWidget(covariant LessonMediaView oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.mediaUrl != widget.mediaUrl ||
        oldWidget.lessonType != widget.lessonType) {
      _disposePlayers();
      _bootstrap();
    }
  }

  @override
  void dispose() {
    _disposePlayers();
    super.dispose();
  }

  void _disposePlayers() {
    _video?.removeListener(_onVideoTick);
    _video?.dispose();
    _video = null;
    _web = null;
    _webReady = false;
    _videoError = false;
    _errorMessage = null;
  }

  Future<void> _bootstrap() async {
    final type = widget.lessonType;
    final url = widget.mediaUrl;

    void reportProgress(double fraction) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) {
          return;
        }
        widget.onProgress?.call(fraction: fraction);
      });
    }

    if (type == 'image') {
      reportProgress(0.25);
      return;
    }

    if (type == 'video') {
      final embed = LessonMedia.youtubeEmbedUrl(url);
      if (embed != null) {
        _initWeb(embed);
        reportProgress(0.2);
        return;
      }
      await _initVideo(url, audioOnly: false);
      return;
    }

    if (type == 'audio') {
      await _initVideo(url, audioOnly: true);
      return;
    }

    if (type == 'pdf') {
      _initWeb(url);
      reportProgress(0.2);
      return;
    }

    // Fallback: try webview for unknown embeds.
    _initWeb(url);
  }

  void _initWeb(String url) {
    final controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(const Color(0xFF0C0A09))
      ..loadRequest(Uri.parse(url));
    setState(() {
      _web = controller;
      _webReady = true;
    });
  }

  Future<void> _initVideo(String url, {required bool audioOnly}) async {
    try {
      final controller = VideoPlayerController.networkUrl(Uri.parse(url));
      await controller.initialize();
      final seek = widget.initialPositionSeconds;
      if (seek != null && seek > 0) {
        await controller.seekTo(Duration(seconds: seek));
      }
      controller.addListener(_onVideoTick);
      if (!mounted) {
        await controller.dispose();
        return;
      }
      setState(() {
        _video = controller;
        _videoError = false;
      });
      if (audioOnly) {
        // Audio lessons still use VideoPlayer; UI shows controls only.
      }
    } catch (e) {
      if (!mounted) {
        return;
      }
      setState(() {
        _videoError = true;
        _errorMessage = 'Could not load media';
      });
    }
  }

  void _onVideoTick() {
    final c = _video;
    if (c == null || !c.value.isInitialized) {
      return;
    }
    final duration = c.value.duration.inMilliseconds;
    if (duration <= 0) {
      return;
    }
    final pos = c.value.position;
    final fraction = (pos.inMilliseconds / duration).clamp(0.0, 1.0);
    widget.onProgress?.call(
      fraction: fraction,
      positionSeconds: pos.inSeconds,
    );
  }

  Future<void> _openExternal() async {
    final uri = Uri.tryParse(widget.mediaUrl);
    if (uri == null) {
      return;
    }
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    final type = widget.lessonType;
    final maxH = widget.compact ? 280.0 : double.infinity;

    Widget child;
    switch (type) {
      case 'image':
        child = _ImageStage(
          url: widget.mediaUrl,
          interactive: !widget.compact,
        );
      case 'video':
        final embed = LessonMedia.youtubeEmbedUrl(widget.mediaUrl);
        if (embed != null) {
          child = _WebStage(controller: _web, ready: _webReady);
        } else if (_videoError) {
          child = _Fallback(
            message: _errorMessage ?? 'Video unavailable',
            onOpen: _openExternal,
          );
        } else if (_video == null) {
          child = const Center(child: CircularProgressIndicator());
        } else {
          child = _VideoStage(controller: _video!);
        }
      case 'audio':
        if (_videoError) {
          child = _Fallback(
            message: _errorMessage ?? 'Audio unavailable',
            onOpen: _openExternal,
          );
        } else if (_video == null) {
          child = const Center(child: CircularProgressIndicator());
        } else {
          child = _AudioStage(
            controller: _video!,
            title: widget.title,
          );
        }
      case 'pdf':
        child = Column(
          children: [
            Expanded(child: _WebStage(controller: _web, ready: _webReady)),
            Padding(
              padding: const EdgeInsets.all(8),
              child: TextButton.icon(
                onPressed: _openExternal,
                icon: const Icon(Icons.open_in_new, size: 18),
                label: const Text('Open PDF externally'),
              ),
            ),
          ],
        );
      default:
        child = _Fallback(
          message: 'Open this resource',
          onOpen: _openExternal,
        );
    }

    return ColoredBox(
      color: const Color(0xFF0C0A09),
      child: widget.compact
          ? SizedBox(width: double.infinity, height: maxH, child: child)
          : SizedBox.expand(child: child),
    );
  }
}

class _ImageStage extends StatelessWidget {
  const _ImageStage({required this.url, required this.interactive});

  final String url;
  final bool interactive;

  @override
  Widget build(BuildContext context) {
    final image = Image.network(
      url,
      fit: BoxFit.contain,
      errorBuilder: (_, __, ___) => const Center(
        child: Icon(Icons.broken_image_outlined, color: Colors.white54, size: 48),
      ),
    );
    if (!interactive) {
      return Center(child: image);
    }
    return InteractiveViewer(
      minScale: 0.5,
      maxScale: 4,
      child: Center(child: image),
    );
  }
}

class _VideoStage extends StatelessWidget {
  const _VideoStage({required this.controller});

  final VideoPlayerController controller;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: AspectRatio(
        aspectRatio: controller.value.aspectRatio == 0
            ? 16 / 9
            : controller.value.aspectRatio,
        child: Stack(
          alignment: Alignment.bottomCenter,
          children: [
            VideoPlayer(controller),
            _VideoControls(controller: controller),
          ],
        ),
      ),
    );
  }
}

class _AudioStage extends StatelessWidget {
  const _AudioStage({required this.controller, this.title});

  final VideoPlayerController controller;
  final String? title;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 420),
        child: Card(
          color: const Color(0xFF1C1917),
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (title != null && title!.isNotEmpty) ...[
                  Text(
                    title!,
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: Colors.white70),
                  ),
                  const SizedBox(height: 16),
                ],
                const Icon(Icons.graphic_eq, color: Colors.tealAccent, size: 40),
                const SizedBox(height: 12),
                _VideoControls(controller: controller),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _VideoControls extends StatelessWidget {
  const _VideoControls({required this.controller});

  final VideoPlayerController controller;

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: controller,
      builder: (context, _) {
        final v = controller.value;
        final duration = v.duration;
        final position = v.position;
        final maxMs = duration.inMilliseconds <= 0 ? 1.0 : duration.inMilliseconds.toDouble();
        return Material(
          color: Colors.black54,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Slider(
                value: position.inMilliseconds.clamp(0, maxMs.toInt()).toDouble(),
                max: maxMs,
                onChanged: (value) {
                  controller.seekTo(Duration(milliseconds: value.round()));
                },
              ),
              Row(
                children: [
                  IconButton(
                    color: Colors.white,
                    onPressed: () {
                      if (v.isPlaying) {
                        controller.pause();
                      } else {
                        controller.play();
                      }
                    },
                    icon: Icon(v.isPlaying ? Icons.pause : Icons.play_arrow),
                  ),
                  Text(
                    '${_fmt(position)} / ${_fmt(duration)}',
                    style: const TextStyle(color: Colors.white70, fontSize: 12),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  String _fmt(Duration d) {
    final m = d.inMinutes.remainder(60).toString().padLeft(2, '0');
    final s = d.inSeconds.remainder(60).toString().padLeft(2, '0');
    final h = d.inHours;
    if (h > 0) {
      return '$h:$m:$s';
    }
    return '$m:$s';
  }
}

class _WebStage extends StatelessWidget {
  const _WebStage({required this.controller, required this.ready});

  final WebViewController? controller;
  final bool ready;

  @override
  Widget build(BuildContext context) {
    if (!ready || controller == null) {
      return const Center(child: CircularProgressIndicator());
    }
    return WebViewWidget(controller: controller!);
  }
}

class _Fallback extends StatelessWidget {
  const _Fallback({required this.message, required this.onOpen});

  final String message;
  final VoidCallback onOpen;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(message, style: const TextStyle(color: Colors.white70)),
            const SizedBox(height: 12),
            FilledButton.tonal(
              onPressed: onOpen,
              child: const Text('Open externally'),
            ),
          ],
        ),
      ),
    );
  }
}
