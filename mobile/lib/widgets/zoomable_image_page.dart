import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

/// Fullscreen pinch-to-zoom image viewer (lessons + reflections).
class ZoomableImagePage extends StatefulWidget {
  const ZoomableImagePage({
    super.key,
    required this.imageUrl,
    this.title,
  });

  final String imageUrl;
  final String? title;

  static Future<void> open(
    BuildContext context, {
    required String imageUrl,
    String? title,
  }) {
    return Navigator.of(context).push(
      MaterialPageRoute<void>(
        fullscreenDialog: true,
        builder: (_) => ZoomableImagePage(imageUrl: imageUrl, title: title),
      ),
    );
  }

  @override
  State<ZoomableImagePage> createState() => _ZoomableImagePageState();
}

class _ZoomableImagePageState extends State<ZoomableImagePage> {
  @override
  void initState() {
    super.initState();
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.immersiveSticky);
  }

  @override
  void dispose() {
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        title: Text(widget.title ?? 'Image'),
      ),
      body: InteractiveViewer(
        minScale: 0.5,
        maxScale: 5,
        child: Center(
          child: Image.network(
            widget.imageUrl,
            fit: BoxFit.contain,
            errorBuilder: (_, __, ___) => const Icon(
              Icons.broken_image_outlined,
              color: Colors.white54,
              size: 64,
            ),
          ),
        ),
      ),
    );
  }
}
