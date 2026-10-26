import 'dart:typed_data';
import 'dart:ui' as ui;

import '../media/private_image_dimensions.dart';
import '../tutor/tutor_repository.dart';

/// Retain/upload only the approved rectangle, never the surrounding frame.
Future<TutorPhotoIntent> examApprovedCrop(TutorPhotoIntent input) async {
  final dimensions = privateImageDimensions(
    input.bytes,
    maximumEdge: 768,
    maximumPixels: 768 * 768,
  );
  if (input.x + input.width > dimensions.width ||
      input.y + input.height > dimensions.height)
    throw const FormatException('Invalid working rectangle.');
  ui.Codec? codec;
  ui.Image? source, result;
  ui.Picture? picture;
  // WebCodecs' JS interop requires a native Uint8List, while the exact human
  // intent deliberately exposes an unmodifiable view. Keep that intent frozen
  // and give the decoder its own bounded, short-lived buffer.
  final decoderBytes = Uint8List.fromList(input.bytes);
  try {
    codec = await ui.instantiateImageCodec(decoderBytes);
    source = (await codec.getNextFrame()).image;
    final recorder = ui.PictureRecorder();
    ui.Canvas(recorder).drawImageRect(
      source,
      ui.Rect.fromLTWH(
        input.x.toDouble(),
        input.y.toDouble(),
        input.width.toDouble(),
        input.height.toDouble(),
      ),
      ui.Rect.fromLTWH(0, 0, input.width.toDouble(), input.height.toDouble()),
      ui.Paint(),
    );
    picture = recorder.endRecording();
    result = await picture.toImage(input.width, input.height);
    final data = await result.toByteData(format: ui.ImageByteFormat.png);
    if (data == null || data.lengthInBytes > privateWorkingMaximumBytes)
      throw const FormatException('Choose a smaller working selection.');
    final bytes = Uint8List.fromList(
      data.buffer.asUint8List(data.offsetInBytes, data.lengthInBytes),
    );
    return TutorPhotoIntent(
      session: input.session,
      operation: input.operation,
      bytes: bytes,
      x: 0,
      y: 0,
      width: input.width,
      height: input.height,
      approved: true,
    );
  } finally {
    result?.dispose();
    picture?.dispose();
    source?.dispose();
    codec?.dispose();
    decoderBytes.fillRange(0, decoderBytes.length, 0);
  }
}
