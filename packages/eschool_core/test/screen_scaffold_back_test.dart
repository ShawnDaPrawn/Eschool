import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('a private root can own exit without a surrounding route pop', (
    tester,
  ) async {
    var exits = 0;
    await tester.pumpWidget(
      MaterialApp(
        home: ScreenScaffold(
          title: 'Private reader',
          onBack: () => exits++,
          body: const Text('Reader'),
        ),
      ),
    );
    await tester.tap(find.byTooltip('Back'));
    await tester.pump();
    expect(exits, 1);
    expect(find.text('Reader'), findsOneWidget);
    await tester.pumpWidget(
      const MaterialApp(
        home: ScreenScaffold(title: 'Default root', body: Text('Root')),
      ),
    );
    expect(find.byTooltip('Back'), findsNothing);
  });
}
