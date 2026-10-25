import 'dart:typed_data';
import 'dart:ui' as ui;
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';

/// One mounted, scoped decoder. It never enters Flutter's global ImageCache.
class PrivateGalleryImage extends StatefulWidget {
  const PrivateGalleryImage({
    required this.load,
    required this.active,
    required this.label,
    required this.identity,
    this.full = false,
    this.onDenied,
    super.key,
  });
  final Future<Uint8List> Function() load;
  final bool Function() active;
  final String label;
  final Object identity;
  final bool full;
  final VoidCallback? onDenied;
  @override
  State<PrivateGalleryImage> createState() => _PrivateGalleryImageState();
}

class _PrivateGalleryImageState extends State<PrivateGalleryImage> {
  ui.Image? _image;
  int _generation = 0;
  bool _failed = false;
  @override
  void initState() {
    super.initState();
    _read();
  }

  @override
  void didUpdateWidget(PrivateGalleryImage oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.identity != widget.identity) {
      _generation++;
      _image?.dispose();
      _image = null;
      _failed = false;
      _read();
    }
  }

  Future<void> _read() async {
    final generation = ++_generation;
    ui.Codec? codec;
    try {
      if (!widget.active()) return;
      final bytes = await widget.load();
      if (!mounted || generation != _generation || !widget.active()) return;
      if (bytes.isEmpty || bytes.length > 4194304)
        throw const FormatException();
      codec = await ui.instantiateImageCodec(bytes);
      final frame = await codec.getNextFrame();
      final maximum = widget.full ? 2048 : 360;
      if (!mounted || generation != _generation || !widget.active()) {
        frame.image.dispose();
        return;
      }
      if (frame.image.width > maximum || frame.image.height > maximum) {
        frame.image.dispose();
        throw const FormatException();
      }
      setState(() => _image = frame.image);
    } catch (error) {
      if (!mounted || generation != _generation || !widget.active()) return;
      if (error is ApiException &&
          const [401, 403, 404, 423].contains(error.statusCode))
        widget.onDenied?.call();
      if (mounted) setState(() => _failed = true);
    } finally {
      codec?.dispose();
    }
  }

  @override
  void dispose() {
    _generation++;
    _image?.dispose();
    _image = null;
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Semantics(
    image: true,
    label: widget.label,
    child: AspectRatio(
      aspectRatio: 4 / 3,
      child: widget.active() && _image != null
          ? RawImage(image: _image, fit: BoxFit.contain)
          : Center(
              child: Icon(
                _failed ? Icons.broken_image_outlined : Icons.photo_outlined,
              ),
            ),
    ),
  );
}
