import 'dart:typed_data';
import 'dart:ui' as ui;

import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/src/exam_coach/exam_approved_crop.dart';
import 'package:eschool_academics/src/media/private_image_dimensions.dart';
import 'package:flutter_test/flutter_test.dart';

import 'exam_fixture.dart';

void main() {
  testWidgets(
    'retry retention contains only the approved rectangle, never surrounding identifying pixels',
    (tester) async {
      await tester.runAsync(() async {
        final recorder = ui.PictureRecorder();
        final canvas = ui.Canvas(recorder);
        canvas.drawRect(
          const ui.Rect.fromLTWH(0, 0, 10, 10),
          ui.Paint()..color = const ui.Color(0xffff0000),
        );
        canvas.drawRect(
          const ui.Rect.fromLTWH(10, 0, 10, 10),
          ui.Paint()..color = const ui.Color(0xff0000ff),
        );
        final picture = recorder.endRecording();
        final source = await picture.toImage(20, 10);
        final data = await source.toByteData(format: ui.ImageByteFormat.png);
        final input = TutorPhotoIntent(
          session: examVersion,
          operation: examOperation,
          bytes: data!.buffer.asUint8List(
            data.offsetInBytes,
            data.lengthInBytes,
          ),
          x: 10,
          y: 0,
          width: 10,
          height: 10,
          approved: true,
        );
        final crop = await examApprovedCrop(input);
        expect((crop.x, crop.y, crop.width, crop.height), (0, 0, 10, 10));
        expect(privateImageDimensions(crop.bytes), (width: 10, height: 10));
        final codec = await ui.instantiateImageCodec(crop.bytes);
        final output = (await codec.getNextFrame()).image;
        final rgba = await output.toByteData(
          format: ui.ImageByteFormat.rawRgba,
        );
        expect(Uint8List.view(rgba!.buffer, rgba.offsetInBytes, 4), [
          0,
          0,
          255,
          255,
        ]);
        output.dispose();
        codec.dispose();
        source.dispose();
        picture.dispose();
      });
    },
  );
}
