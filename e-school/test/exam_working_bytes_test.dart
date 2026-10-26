import 'dart:convert';
import 'dart:ui' as ui;

import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_academics/src/exam_coach/exam_approved_crop.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  test('engine-normalized crop, sealed exact intent and multipart retain the approved bytes', () async {
    var stage = 'decode input';
    try {
      final raw = base64Decode(ExamCoachSmokeFixture.pixels);
      final codec = await ui.instantiateImageCodec(raw);
      final image = (await codec.getNextFrame()).image;
      final normalized = await image.toByteData(format: ui.ImageByteFormat.png);
      expect(normalized, isNotNull);
      final bytes = normalized!.buffer.asUint8List(
        normalized.offsetInBytes,
        normalized.lengthInBytes,
      );
      stage = 'crop normalized image';
      final crop = await examApprovedCrop(
        TutorPhotoIntent(
          session: ExamCoachSmokeFixture.version,
          operation: ExamCoachSmokeFixture.operation,
          bytes: bytes,
          x: 0,
          y: 0,
          width: 20,
          height: 20,
          approved: true,
        ),
      );
      stage = 'seal exact approved bytes';
      final pending = PrivatePendingIntent(
        school: 1,
        reader: 11,
        body: {'kind': 'photo', 'bytes': base64Encode(crop.bytes)},
      );
      expect(
        pending.open(school: 1, reader: 11, authorized: true)['bytes'],
        base64Encode(crop.bytes),
      );
      stage = 'build multipart';
      final form = ExamPhotoIntent(
        3,
        ExamCoachSmokeFixture.version,
        '1',
        crop,
      ).form();
      expect(form.files.single.value.filename, 'working.png');
      pending.close();
      image.dispose();
      codec.dispose();
    } catch (error, stack) {
      // Synthetic fixture only: report the stage without any user content.
      print(
        'Exam synthetic preparation failed at $stage: ${error.runtimeType}: $error\n$stack',
      );
      rethrow;
    }
  });
}
