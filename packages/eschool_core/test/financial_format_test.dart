import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test(
    'financial amounts preserve signed cents, explicit currency and large integers',
    () {
      expect(formatFinancialAmount('ZAR', 0), 'ZAR 0.00');
      expect(formatFinancialAmount('USD', -1), 'USD -0.01');
      expect(formatFinancialAmount('ZAR', 1001), 'ZAR 10.01');
      expect(
        formatFinancialAmount('ZAR', 9000000000000000),
        'ZAR 90000000000000.00',
      );
      expect(formatFinancialAmount('ZAR', -101), 'ZAR -1.01');
    },
  );
  test('civil dates never pass through a timezone conversion', () {
    expect(formatFinancialDateOnly('2026-01-01'), '01/01/2026');
    expect(formatFinancialDateOnly('2026-12-31'), '31/12/2026');
    expect(formatFinancialDateOnly(''), '');
  });
  testWidgets('instants use Material local date and clock preferences', (
    tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        home: Builder(
          builder: (context) {
            final media = MediaQuery.of(
              context,
            ).copyWith(alwaysUse24HourFormat: true);
            return MediaQuery(
              data: media,
              child: Builder(
                builder: (context) => Text(
                  formatFinancialInstant(context, DateTime(2026, 6, 15, 14, 5)),
                ),
              ),
            );
          },
        ),
      ),
    );
    expect(find.textContaining('14:05'), findsOneWidget);
    expect(find.textContaining('2026-06-15T'), findsNothing);
  });
}
