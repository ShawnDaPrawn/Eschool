import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';
import 'dart:ui' as ui;
import 'package:eschool_academics/src/wellbeing/private_report_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:eschool_academics/src/media/private_image_dimensions.dart';

Future<String> photographedPaper() async {
  const width = 640, height = 768;
  final pixels = Uint8List(width * height * 4);
  var state = 7309;
  int next() {
    state = (1664525 * state + 1013904223) & 0xffffffff;
    return (state >> 16) & 255;
  }

  for (var i = 0; i < pixels.length; i += 4) {
    final shade = 230 + next() % 19;
    pixels[i] = shade + next() % 5 - 2;
    pixels[i + 1] = shade;
    pixels[i + 2] = shade + next() % 5 - 2;
    pixels[i + 3] = 255;
  }
  final ready = Completer<ui.Image>();
  ui.decodeImageFromPixels(
    pixels,
    width,
    height,
    ui.PixelFormat.rgba8888,
    ready.complete,
  );
  final image = await ready.future;
  try {
    final data = (await image.toByteData(format: ui.ImageByteFormat.png))!;
    return base64Encode(
      data.buffer.asUint8List(data.offsetInBytes, data.lengthInBytes),
    );
  } finally {
    image.dispose();
  }
}

void main() {
  testWidgets(
    'private decoded thumbnail avoids global ImageCache and is disposed with reader',
    (tester) async {
      final before = PaintingBinding.instance.imageCache.currentSize;
      await tester.pumpWidget(
        const MaterialApp(
          home: PrivateReportImage(
            base64:
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNQcGj4DwADRAHgMqq/hAAAAABJRU5ErkJggg==',
            label: 'Private synthetic report image',
          ),
        ),
      );
      await tester.runAsync(() async {
        await Future<void>.delayed(const Duration(milliseconds: 30));
      });
      await tester.pump();
      expect(find.byType(RawImage), findsOneWidget);
      final image = tester.widget<RawImage>(find.byType(RawImage)).image!;
      expect(PaintingBinding.instance.imageCache.currentSize, before);
      await tester.pumpWidget(const SizedBox());
      expect(image.debugDisposed, isTrue);
    },
  );
  testWidgets(
    'malformed thumbnail fails privately without an image exception',
    (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: PrivateReportImage(
            base64: 'not-valid-base64',
            label: 'Private report image',
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.byType(RawImage), findsNothing);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'a large normalized paper preview requires explicit opt-in and avoids the global cache',
    (tester) async {
      late String encoded;
      await tester.runAsync(() async {
        encoded = await photographedPaper();
      });
      expect(encoded.length, greaterThan(524288));
      expect(
        encoded.length,
        lessThanOrEqualTo(privateWorkingMaximumEncodedLength),
      );
      final before = PaintingBinding.instance.imageCache.currentSize;
      await tester.pumpWidget(
        MaterialApp(
          home: PrivateReportImage(
            base64: encoded,
            label: 'Synthetic working',
            maximumEdge: 768,
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.byType(RawImage), findsNothing);
      await tester.pumpWidget(
        MaterialApp(
          home: PrivateReportImage(
            base64: encoded,
            label: 'Synthetic working',
            maximumEdge: 768,
            maximumEncodedLength: privateWorkingMaximumEncodedLength,
          ),
        ),
      );
      await tester.runAsync(() async {
        await Future<void>.delayed(const Duration(milliseconds: 60));
      });
      await tester.pump();
      expect(find.byType(RawImage), findsOneWidget);
      final image = tester.widget<RawImage>(find.byType(RawImage)).image!;
      expect(image.width, 640);
      expect(image.height, 768);
      expect(PaintingBinding.instance.imageCache.currentSize, before);
      await tester.pumpWidget(const SizedBox());
      expect(image.debugDisposed, isTrue);
      expect(tester.takeException(), isNull);
    },
  );

  testWidgets(
    'oversized raster headers are refused before allocating decoded pixels',
    (tester) async {
      final bytes = Uint8List(33);
      bytes.setAll(0, [137, 80, 78, 71, 13, 10, 26, 10]);
      final header = ByteData.sublistView(bytes);
      header.setUint32(8, 13);
      bytes.setAll(12, 'IHDR'.codeUnits);
      header.setUint32(16, 65535);
      header.setUint32(20, 65535);
      expect(
        () => privateImageDimensions(
          bytes,
          maximumBytes: 1048576,
          maximumEdge: 768,
        ),
        throwsFormatException,
      );
      await tester.pumpWidget(
        MaterialApp(
          home: PrivateReportImage(
            base64: base64Encode(bytes),
            label: 'Invalid oversized working',
            maximumEdge: 768,
            maximumEncodedLength: privateWorkingMaximumEncodedLength,
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.byType(RawImage), findsNothing);
      expect(tester.takeException(), isNull);
    },
  );
}
