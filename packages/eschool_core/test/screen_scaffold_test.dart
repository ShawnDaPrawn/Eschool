import 'dart:convert';

import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';

/// The back arrow the apps ship, served small.
void _serveBackIcon(WidgetTester tester) {
  final TestDefaultBinaryMessenger messenger =
      tester.binding.defaultBinaryMessenger;
  messenger.setMockMessageHandler('flutter/assets', (message) async {
    final String asset = utf8.decode(message!.buffer.asUint8List());
    if (asset == SharedAssets.backIcon || asset == SharedAssets.rtlBackIcon) {
      return ByteData.sublistView(
        utf8.encode(
          '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">'
          '<path d="M15 4 7 12l8 8" fill="none" stroke="#000000"/></svg>',
        ),
      );
    }
    return messenger.delegate.send('flutter/assets', message);
  });
  addTearDown(() => messenger.setMockMessageHandler('flutter/assets', null));
}

void main() {
  Future<void> pushScreen(WidgetTester tester, Widget screen) async {
    _serveBackIcon(tester);
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.light(),
        home: Builder(
          builder: (context) => Scaffold(
            body: Center(
              child: FilledButton(
                onPressed: () => Navigator.of(
                  context,
                ).push(MaterialPageRoute<void>(builder: (_) => screen)),
                child: const Text('Open'),
              ),
            ),
          ),
        ),
      ),
    );
    await tester.tap(find.text('Open'));
    await tester.pumpAndSettle();
  }

  testWidgets('the header has the title, a back button and the action', (
    tester,
  ) async {
    final SemanticsHandle semantics = tester.ensureSemantics();
    await pushScreen(
      tester,
      ScreenScaffold(
        title: 'My school',
        action: IconButton(
          tooltip: 'Share',
          onPressed: () {},
          icon: const Icon(Icons.share),
        ),
        body: const Text('Body'),
      ),
    );

    expect(find.text('My school'), findsOneWidget);
    expect(find.text('Body'), findsOneWidget);
    expect(find.byTooltip('Back'), findsOneWidget);
    expect(
      tester.getSemantics(find.text('My school')),
      isSemantics(label: 'My school', isHeader: true),
    );
    await expectLater(tester, meetsGuideline(androidTapTargetGuideline));

    await tester.tap(find.byTooltip('Back'));
    await tester.pumpAndSettle();
    expect(find.text('Open'), findsOneWidget);
    semantics.dispose();
  });

  testWidgets('a first screen has no back button', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.light(),
        home: const ScreenScaffold(title: 'Invitation', body: SizedBox()),
      ),
    );

    expect(find.byTooltip('Back'), findsNothing);
  });

  testWidgets('an empty state says what is missing and offers the way on', (
    tester,
  ) async {
    var tapped = 0;
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.light(),
        home: Scaffold(
          body: EmptyStateMessage(
            icon: Icons.inbox,
            title: 'Nothing yet',
            message: 'It shows here when it arrives.',
            action: TextButton(
              onPressed: () => tapped++,
              child: const Text('Refresh'),
            ),
          ),
        ),
      ),
    );

    expect(find.text('Nothing yet'), findsOneWidget);
    expect(find.text('It shows here when it arrives.'), findsOneWidget);
    await tester.tap(find.text('Refresh'));
    expect(tapped, 1);
  });
}
