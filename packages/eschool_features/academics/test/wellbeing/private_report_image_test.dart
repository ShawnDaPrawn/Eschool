import 'package:eschool_academics/src/wellbeing/private_report_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

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
}
