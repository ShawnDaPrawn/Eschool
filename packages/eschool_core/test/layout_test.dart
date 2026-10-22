import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

/// The widths the Build Bible asks every screen to work at.
const double phone = 390;
const double tablet = 820;
const double desktop = 1280;

Future<void> pumpAt(WidgetTester tester, double width, Widget child) async {
  tester.view.physicalSize = Size(width, 900);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
  await tester.pumpWidget(
    MaterialApp(
      theme: AppTheme.light(),
      home: Scaffold(body: child),
    ),
  );
}

/// Reports the width the screen it is placed in believes it has.
class WidthProbe extends StatelessWidget {
  const WidthProbe({super.key});

  static double? seen;

  @override
  Widget build(BuildContext context) {
    seen = MediaQuery.sizeOf(context).width;
    return const SizedBox.expand(key: Key('probe'));
  }
}

void main() {
  group('Breakpoints', () {
    test('phone below 600, tablet to 1024, desktop above', () {
      expect(Breakpoints.forWidth(phone), ScreenSize.phone);
      expect(Breakpoints.forWidth(599), ScreenSize.phone);
      expect(Breakpoints.forWidth(600), ScreenSize.tablet);
      expect(Breakpoints.forWidth(tablet), ScreenSize.tablet);
      expect(Breakpoints.forWidth(1024), ScreenSize.tablet);
      expect(Breakpoints.forWidth(1025), ScreenSize.desktop);
      expect(Breakpoints.forWidth(desktop), ScreenSize.desktop);
    });
  });

  group('ContentColumn', () {
    testWidgets('changes nothing on a phone', (tester) async {
      await pumpAt(tester, phone, const ContentColumn(child: WidthProbe()));

      expect(tester.getSize(find.byKey(const Key('probe'))).width, phone);
      expect(WidthProbe.seen, phone);
      expect(
        find.descendant(
          of: find.byType(ContentColumn),
          matching: find.byType(ColoredBox),
        ),
        findsNothing,
      );
    });

    for (final width in [tablet, desktop]) {
      testWidgets('keeps a centred column at $width px', (tester) async {
        await pumpAt(tester, width, const ContentColumn(child: WidthProbe()));

        final Rect column = tester.getRect(find.byKey(const Key('probe')));
        expect(column.width, Breakpoints.contentMaxWidth);
        expect(column.center.dx, width / 2);
        // The screen inside sizes itself from the column, not the window.
        expect(WidthProbe.seen, Breakpoints.contentMaxWidth);
      });
    }
  });

  group('AdaptiveScaffold', () {
    final List<int> taps = [];

    Widget scaffold({int selected = 0}) => AdaptiveScaffold(
      body: const WidthProbe(),
      bottomNavigationBar: const SizedBox(
        key: Key('bottom-bar'),
        height: 60,
        width: 300,
      ),
      overlays: const [SizedBox(key: Key('overlay'))],
      selectedIndex: selected,
      onDestinationSelected: taps.add,
      destinations: const [
        AdaptiveDestination(
          icon: Icon(Icons.home_outlined),
          selectedIcon: Icon(Icons.home),
          label: 'Home',
        ),
        AdaptiveDestination(
          icon: Icon(Icons.chat_outlined),
          selectedIcon: Icon(Icons.chat),
          label: 'Chat',
          badgeCount: 3,
        ),
      ],
    );

    setUp(taps.clear);

    testWidgets('a phone keeps the app\'s own bottom bar', (tester) async {
      await pumpAt(tester, phone, scaffold());

      expect(find.byKey(const Key('bottom-bar')), findsOneWidget);
      expect(find.byType(NavigationRail), findsNothing);
      expect(find.byKey(const Key('overlay')), findsOneWidget);
      expect(WidthProbe.seen, phone);
    });

    testWidgets('a tablet gets a rail with labels instead', (tester) async {
      await pumpAt(tester, tablet, scaffold());

      final rail = tester.widget<NavigationRail>(find.byType(NavigationRail));
      expect(rail.extended, isFalse);
      expect(rail.labelType, NavigationRailLabelType.all);
      expect(find.byKey(const Key('bottom-bar')), findsNothing);
      expect(find.byKey(const Key('overlay')), findsOneWidget);
      expect(find.text('3'), findsOneWidget);
      expect(WidthProbe.seen, Breakpoints.contentMaxWidth);

      await tester.tap(find.text('Chat'));
      expect(taps, [1]);
    });

    testWidgets('a desktop gets the rail extended', (tester) async {
      await pumpAt(tester, desktop, scaffold(selected: 1));

      final rail = tester.widget<NavigationRail>(find.byType(NavigationRail));
      expect(rail.extended, isTrue);
      expect(rail.selectedIndex, 1);
      expect(WidthProbe.seen, Breakpoints.contentMaxWidth);
    });

    testWidgets('no destination is selected for an index outside the rail', (
      tester,
    ) async {
      await pumpAt(tester, tablet, scaffold(selected: -1));

      expect(
        tester
            .widget<NavigationRail>(find.byType(NavigationRail))
            .selectedIndex,
        isNull,
      );
    });
  });

  group('AdaptivePageRoute', () {
    Future<void> push(WidgetTester tester, {required bool column}) async {
      tester.view.physicalSize = const Size(desktop, 900);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.reset);
      final navigator = GlobalKey<NavigatorState>();
      await tester.pumpWidget(
        MaterialApp(navigatorKey: navigator, home: const SizedBox()),
      );
      navigator.currentState!.push(
        AdaptivePageRoute<void>(
          builder: (_) => const WidthProbe(),
          contentColumn: column,
        ),
      );
      await tester.pumpAndSettle();
    }

    testWidgets('keeps a pushed screen to the column', (tester) async {
      await push(tester, column: true);

      expect(WidthProbe.seen, Breakpoints.contentMaxWidth);
    });

    testWidgets('lets a screen that adapts itself have the window', (
      tester,
    ) async {
      await push(tester, column: false);

      expect(WidthProbe.seen, desktop);
    });
  });
}
