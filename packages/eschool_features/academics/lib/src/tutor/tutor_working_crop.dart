import 'dart:async';
import 'dart:math' as math;
import 'dart:typed_data';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter/gestures.dart';

import '../localization/academic_text.dart';
import 'tutor_repository.dart';

/// Bounds dimensions before any decoder allocates pixels. ImageDescriptor's
/// encoded dimensions are intentionally unsupported by Flutter's web engine.
({int width, int height}) tutorPhotoDimensions(Uint8List bytes) {
  if (bytes.isEmpty || bytes.length > 4194304)
    throw const FormatException('image limit');
  int width = 0, height = 0;
  final data = ByteData.sublistView(bytes);
  const png = [137, 80, 78, 71, 13, 10, 26, 10];
  if (bytes.length >= 33 &&
      List.generate(8, (i) => bytes[i]).join(',') == png.join(',')) {
    if (data.getUint32(8) != 13 ||
        String.fromCharCodes(bytes.sublist(12, 16)) != 'IHDR')
      throw const FormatException('invalid PNG');
    width = data.getUint32(16);
    height = data.getUint32(20);
  } else if (bytes.length >= 4 && bytes[0] == 255 && bytes[1] == 216) {
    var offset = 2;
    while (offset + 3 < bytes.length) {
      if (bytes[offset++] != 255) throw const FormatException('invalid JPEG');
      while (offset < bytes.length && bytes[offset] == 255) {
        offset++;
      }
      if (offset >= bytes.length) break;
      final marker = bytes[offset++];
      if (marker == 217 || marker == 218 || offset + 2 > bytes.length) break;
      if (marker == 1 || (marker >= 208 && marker <= 215)) continue;
      final length = data.getUint16(offset);
      if (length < 2 || offset + length > bytes.length)
        throw const FormatException('invalid JPEG');
      if (const {
        192,
        193,
        194,
        195,
        197,
        198,
        199,
        201,
        202,
        203,
        205,
        206,
        207,
      }.contains(marker)) {
        if (length < 8) throw const FormatException('invalid JPEG');
        height = data.getUint16(offset + 3);
        width = data.getUint16(offset + 5);
        break;
      }
      offset += length;
    }
  }
  if (width < 1 ||
      height < 1 ||
      width > 8192 ||
      height > 8192 ||
      width * height > 16777216)
    throw const FormatException('image limit');
  return (width: width, height: height);
}

/// A preview and rectangle use the same normalized PNG pixels sent to Files.
/// This avoids JPEG orientation/device metadata changing the approved crop.
class TutorWorkingCrop extends StatefulWidget {
  const TutorWorkingCrop({
    required this.bytes,
    required this.session,
    required this.operation,
    required this.onApproved,
    required this.onCancel,
    super.key,
  });
  final Uint8List bytes;
  final String session, operation;
  final ValueChanged<TutorPhotoIntent> onApproved;
  final VoidCallback onCancel;
  @override
  State<TutorWorkingCrop> createState() => _TutorWorkingCropState();
}

class _TutorWorkingCropState extends State<TutorWorkingCrop> {
  ui.Image? _image;
  Uint8List? _normalized;
  Rect? _selection;
  Offset? _start;
  bool _approved = false;
  String? _error;
  @override
  void initState() {
    super.initState();
    unawaited(_decode());
  }

  Future<void> _decode() async {
    ui.Codec? codec;
    ui.Image? image;
    try {
      final dimensions = tutorPhotoDimensions(widget.bytes);
      final scale = math.min(
        1.0,
        768 / math.max(dimensions.width, dimensions.height),
      );
      codec = await ui.instantiateImageCodec(
        widget.bytes,
        targetWidth: math.max(1, (dimensions.width * scale).round()),
        targetHeight: math.max(1, (dimensions.height * scale).round()),
        allowUpscaling: false,
      );
      image = (await codec.getNextFrame()).image;
      final data = await image.toByteData(format: ui.ImageByteFormat.png);
      if (data == null || data.lengthInBytes > 4194304)
        throw StateError('image limit');
      if (!mounted) {
        image.dispose();
        image = null;
        return;
      }
      setState(() {
        _image = image;
        _normalized = data.buffer.asUint8List(
          data.offsetInBytes,
          data.lengthInBytes,
        );
      });
      image = null; // The widget now owns this image.
    } catch (_) {
      image?.dispose();
      if (mounted)
        setState(() => _error = 'Choose a smaller JPEG or PNG working photo.');
    } finally {
      codec?.dispose();
    }
  }

  @override
  void dispose() {
    _image?.dispose();
    _normalized = null;
    super.dispose();
  }

  void _move(Offset point, Size display) {
    if (_image == null || _start == null) return;
    final end = Offset(
      point.dx.clamp(0.0, display.width),
      point.dy.clamp(0.0, display.height),
    );
    final rect = Rect.fromPoints(_start!, end);
    setState(() {
      _selection = Rect.fromLTWH(
        rect.left * _image!.width / display.width,
        rect.top * _image!.height / display.height,
        rect.width * _image!.width / display.width,
        rect.height * _image!.height / display.height,
      );
      _approved = false;
    });
  }

  void _use() {
    final rect = _selection;
    if (!_approved ||
        rect == null ||
        _normalized == null ||
        rect.width < 1 ||
        rect.height < 1)
      return;
    final x = rect.left.floor(), y = rect.top.floor();
    final width = math.max(
      1,
      math.min(_image!.width - x, rect.right.ceil() - x),
    );
    final height = math.max(
      1,
      math.min(_image!.height - y, rect.bottom.ceil() - y),
    );
    widget.onApproved(
      TutorPhotoIntent(
        session: widget.session,
        operation: widget.operation,
        bytes: _normalized!,
        x: x,
        y: y,
        width: width,
        height: height,
        approved: true,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final image = _image;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const AcademicText(
              'Select only your working',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            const AcademicText(
              'Drag around your working. Leave names and identifying details outside the selection.',
            ),
            if (_error != null) AcademicText(_error!),
            if (image == null && _error == null)
              const Center(child: CircularProgressIndicator()),
            if (image != null)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: LayoutBuilder(
                  builder: (context, constraints) {
                    final ratio = image.height / image.width;
                    final width = math.min(constraints.maxWidth, 420 / ratio);
                    final size = Size(width, width * ratio);
                    return Center(
                      child: SizedBox(
                        width: size.width,
                        height: size.height,
                        child: RawGestureDetector(
                          gestures: {
                            EagerGestureRecognizer:
                                GestureRecognizerFactoryWithHandlers<
                                  EagerGestureRecognizer
                                >(EagerGestureRecognizer.new, (_) {}),
                          },
                          child: Listener(
                            key: const Key('tutor-crop-area'),
                            behavior: HitTestBehavior.opaque,
                            onPointerDown: (event) {
                              _start = event.localPosition;
                              _move(event.localPosition, size);
                            },
                            onPointerMove: (event) =>
                                _move(event.localPosition, size),
                            onPointerUp: (_) => _start = null,
                            onPointerCancel: (_) => _start = null,
                            child: CustomPaint(
                              painter: _WorkingCropPainter(
                                image,
                                _selection,
                                Theme.of(context).colorScheme,
                              ),
                            ),
                          ),
                        ),
                      ),
                    );
                  },
                ),
              ),
            if (image != null)
              TextButton(
                onPressed: () => setState(() {
                  _selection = Rect.fromLTWH(
                    0,
                    0,
                    image.width.toDouble(),
                    image.height.toDouble(),
                  );
                  _approved = false;
                }),
                child: const AcademicText(
                  'Select the whole photo if it contains only working',
                ),
              ),
            CheckboxListTile(
              contentPadding: EdgeInsets.zero,
              value: _approved,
              title: const AcademicText(
                'I checked this selection contains only my working.',
              ),
              onChanged:
                  _selection == null ||
                      _selection!.width < 1 ||
                      _selection!.height < 1
                  ? null
                  : (value) => setState(() => _approved = value == true),
            ),
            Wrap(
              spacing: 12,
              runSpacing: 8,
              children: [
                FilledButton(
                  onPressed: _approved ? _use : null,
                  child: const AcademicText('Use this working crop'),
                ),
                TextButton(
                  onPressed: widget.onCancel,
                  child: const AcademicText('Cancel'),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _WorkingCropPainter extends CustomPainter {
  _WorkingCropPainter(this.image, this.selection, this.colors);
  final ui.Image image;
  final Rect? selection;
  final ColorScheme colors;
  @override
  void paint(Canvas canvas, Size size) {
    canvas.drawImageRect(
      image,
      Rect.fromLTWH(0, 0, image.width.toDouble(), image.height.toDouble()),
      Offset.zero & size,
      Paint(),
    );
    final selected = selection;
    if (selected == null) return;
    final rect = Rect.fromLTWH(
      selected.left * size.width / image.width,
      selected.top * size.height / image.height,
      selected.width * size.width / image.width,
      selected.height * size.height / image.height,
    );
    final outside = Path()
      ..fillType = PathFillType.evenOdd
      ..addRect(Offset.zero & size)
      ..addRect(rect);
    canvas.drawPath(
      outside,
      Paint()..color = colors.scrim.withValues(alpha: 0.45),
    );
    canvas.drawRect(
      rect,
      Paint()
        ..style = PaintingStyle.stroke
        ..strokeWidth = 2
        ..color = colors.primary,
    );
  }

  @override
  bool shouldRepaint(_WorkingCropPainter old) =>
      old.image != image || old.selection != selection || old.colors != colors;
}
