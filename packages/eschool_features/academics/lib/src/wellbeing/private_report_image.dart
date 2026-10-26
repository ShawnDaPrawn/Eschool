import 'dart:convert';
import 'dart:ui' as ui;
import 'package:flutter/material.dart';

import '../media/private_image_dimensions.dart';

/// Decoded only for this mounted private reader. No global ImageCache entry,
/// capability URL, disk thumbnail or late decode survives its disposal.
class PrivateReportImage extends StatefulWidget {
  const PrivateReportImage({
    required this.base64,
    required this.label,
    this.maximumEdge = 360,
    this.maximumEncodedLength = 524288,
    super.key,
  });
  final String base64, label;
  final int maximumEdge, maximumEncodedLength;
  @override
  State<PrivateReportImage> createState() => _PrivateReportImageState();
}

class _PrivateReportImageState extends State<PrivateReportImage> {
  ui.Image? _image;
  int _generation = 0;
  bool _failed = false;
  @override
  void initState() {
    super.initState();
    _decode();
  }

  @override
  void didUpdateWidget(PrivateReportImage oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.base64 != oldWidget.base64 ||
        widget.maximumEdge != oldWidget.maximumEdge ||
        widget.maximumEncodedLength != oldWidget.maximumEncodedLength) {
      _image?.dispose();
      _image = null;
      _failed = false;
      _decode();
    }
  }

  Future<void> _decode() async {
    final generation = ++_generation;
    ui.Codec? codec;
    try {
      if (widget.maximumEdge < 1 ||
          widget.maximumEdge > 1024 ||
          widget.maximumEncodedLength < 1 ||
          widget.maximumEncodedLength > privateWorkingMaximumEncodedLength ||
          widget.base64.length > widget.maximumEncodedLength)
        throw const FormatException();
      final bytes = base64Decode(widget.base64);
      privateImageDimensions(
        bytes,
        maximumBytes: privateWorkingMaximumBytes,
        maximumEdge: widget.maximumEdge,
        maximumPixels: widget.maximumEdge * widget.maximumEdge,
      );
      codec = await ui.instantiateImageCodec(bytes);
      final frame = await codec.getNextFrame();
      if (!mounted || generation != _generation) {
        frame.image.dispose();
        return;
      }
      if (frame.image.width > widget.maximumEdge ||
          frame.image.height > widget.maximumEdge) {
        frame.image.dispose();
        throw const FormatException();
      }
      setState(() => _image = frame.image);
    } catch (_) {
      if (mounted && generation == _generation) setState(() => _failed = true);
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
    child: _image != null
        ? ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 360, maxHeight: 360),
            child: RawImage(image: _image, fit: BoxFit.contain),
          )
        : ExcludeSemantics(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Icon(
                _failed ? Icons.broken_image_outlined : Icons.image_outlined,
              ),
            ),
          ),
  );
}
