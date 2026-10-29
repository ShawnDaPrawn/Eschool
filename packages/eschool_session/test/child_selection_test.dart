import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/harness.dart';

final MeChild asha = MeChild.of(
  id: 21,
  name: 'Asha Demo',
  schoolId: 1,
  schoolName: 'Northcliff High',
  image: 'girl_2',
);
final MeChild cara = MeChild.of(
  id: 23,
  name: 'Cara Demo',
  schoolId: 2,
  schoolName: 'Riverside Primary',
  image: 'girl_3',
);

/// A child screen: shows which child it was built for, and counts how
/// often it was built afresh (as a screen's fetch would run).
class _ChildScreen extends StatefulWidget {
  const _ChildScreen();

  static int created = 0;

  @override
  State<_ChildScreen> createState() => _ChildScreenState();
}

class _ChildScreenState extends State<_ChildScreen> {
  late final MeChild? child = context.read<ChildSelectionCubit>().selected;

  @override
  void initState() {
    super.initState();
    _ChildScreen.created++;
  }

  @override
  Widget build(BuildContext context) =>
      Scaffold(body: Center(child: Text('Showing ${child?.name}')));
}

Widget _frame(ChildSelectionCubit cubit) => BlocProvider.value(
  value: cubit,
  child: ChildSwitcherFrame(
    child: BlocBuilder<ChildSelectionCubit, ChildSelectionState>(
      buildWhen: (a, b) => a.selectedId != b.selectedId,
      builder: (context, state) => KeyedSubtree(
        key: ValueKey(state.selectedId),
        child: const _ChildScreen(),
      ),
    ),
  ),
);

void main() {
  test(
    'finishing an old clear cannot erase a replacement child list',
    () async {
      final subject = ChildSelectionCubit(store: InMemorySelectionStore());
      await subject.setChildren([asha]);
      final cleared = subject.clear();
      expect(subject.state.children, isEmpty);
      await subject.setChildren([cara]);
      await cleared;
      expect(subject.state.selectedId, cara.id);
      await subject.close();
    },
  );
  late InMemorySelectionStore store;
  late ChildSelectionCubit cubit;

  setUp(() {
    store = InMemorySelectionStore();
    cubit = ChildSelectionCubit(store: store);
    _ChildScreen.created = 0;
  });

  tearDown(() => cubit.close());

  group('ChildSelectionCubit', () {
    test('starts with nobody, then picks the first child', () async {
      expect(cubit.state.children, isEmpty);
      expect(cubit.selected, isNull);

      await cubit.setChildren([asha, cara]);

      expect(cubit.selected, asha);
      expect(store.childId, asha.id);
      expect(cubit.state.canSwitch, isTrue);
    });

    test('restores the child picked last time', () async {
      store.childId = cara.id;
      cubit = ChildSelectionCubit(store: store);

      await cubit.setChildren([asha, cara]);

      expect(cubit.selected, cara);
    });

    test('a child no longer listed is replaced by the first', () async {
      store.childId = 99;
      cubit = ChildSelectionCubit(store: store);

      await cubit.setChildren([cara, asha]);

      expect(cubit.selected, cara);
      expect(store.childId, cara.id);
    });

    test('keeps the pick when the list is refreshed', () async {
      await cubit.setChildren([asha, cara]);
      await cubit.select(cara.id);

      await cubit.setChildren([asha, cara]);

      expect(cubit.selected, cara);
    });

    test('picking a child keeps it on the device', () async {
      await cubit.setChildren([asha, cara]);

      expect(await cubit.select(cara.id), cara);

      expect(cubit.state.selectedId, cara.id);
      expect(store.childId, cara.id);
    });

    test('another guardian\'s child cannot be picked', () async {
      await cubit.setChildren([asha]);

      expect(await cubit.select(77), isNull);
      expect(cubit.selected, asha);
      expect(cubit.state.canSwitch, isFalse);
    });

    test('sign-out forgets the children and the pick', () async {
      await cubit.setChildren([asha, cara]);
      await cubit.select(cara.id);

      await cubit.clear();

      expect(cubit.state.children, isEmpty);
      expect(cubit.selected, isNull);
      expect(store.childId, isNull);
    });

    test('a child listed twice is listed once', () async {
      await cubit.setChildren([asha, asha, cara]);

      expect(cubit.state.children, [asha, cara]);
    });
  });

  group('ChildSwitcherFrame', () {
    testWidgets('one child: no switcher', (tester) async {
      await cubit.setChildren([asha]);
      await pumpApp(tester, _frame(cubit));

      expect(find.byType(ChildSwitcherBar), findsNothing);
      expect(find.text('Showing Asha Demo'), findsOneWidget);
    });

    testWidgets('on a phone, a bar with the child and their school', (
      tester,
    ) async {
      await cubit.setChildren([asha, cara]);
      await pumpApp(tester, _frame(cubit));

      expect(find.byType(ChildSwitcherBar), findsOneWidget);
      expect(find.byType(ChildSwitcherRail), findsNothing);
      expect(find.text('Asha Demo'), findsOneWidget);
      expect(find.text('Northcliff High'), findsOneWidget);
    });

    testWidgets('changing child once rebuilds the open screen for them', (
      tester,
    ) async {
      await cubit.setChildren([asha, cara]);
      await pumpApp(tester, _frame(cubit));
      expect(_ChildScreen.created, 1);

      await tester.tap(find.byType(ChildSwitcherBar));
      await tester.pumpAndSettle();
      expect(find.text('Choose a child'), findsOneWidget);
      await tester.tap(find.text('Cara Demo').last);
      await tester.pumpAndSettle();

      expect(find.text('Showing Cara Demo'), findsOneWidget);
      expect(find.text('Riverside Primary'), findsOneWidget);
      expect(_ChildScreen.created, 2);
      expect(store.childId, cara.id);
    });

    testWidgets('on a tablet the bar stays on top', (tester) async {
      await cubit.setChildren([asha, cara]);
      await pumpApp(tester, _frame(cubit), size: tablet);

      expect(find.byType(ChildSwitcherBar), findsOneWidget);
    });

    testWidgets('on a desktop, every child beside the screen', (tester) async {
      await cubit.setChildren([asha, cara]);
      await pumpApp(tester, _frame(cubit), size: desktop);

      expect(find.byType(ChildSwitcherBar), findsNothing);
      expect(find.byType(ChildSwitcherRail), findsOneWidget);
      expect(find.text('Your children'), findsOneWidget);

      await tester.tap(find.text('Cara Demo'));
      await tester.pumpAndSettle();

      expect(find.text('Showing Cara Demo'), findsOneWidget);
    });

    testWidgets('the screen below is told the space it really has', (
      tester,
    ) async {
      await cubit.setChildren([asha, cara]);
      late Size seen;
      await pumpApp(
        tester,
        BlocProvider.value(
          value: cubit,
          child: ChildSwitcherFrame(
            child: Builder(
              builder: (context) {
                seen = MediaQuery.sizeOf(context);
                return const SizedBox();
              },
            ),
          ),
        ),
      );

      expect(seen.height, phone.height - ChildSwitcherFrame.barHeight);
    });

    testWidgets('every control has a name for screen readers', (tester) async {
      final SemanticsHandle semantics = tester.ensureSemantics();
      await cubit.setChildren([asha, cara]);
      await pumpApp(tester, _frame(cubit));

      expect(
        tester.getSemantics(
          find.bySemanticsLabel('Switch child, Asha Demo, Northcliff High'),
        ),
        matchesSemantics(
          label: 'Switch child, Asha Demo, Northcliff High',
          isButton: true,
          hasTapAction: true,
          isFocusable: true,
          hasFocusAction: true,
          hasEnabledState: true,
          isEnabled: true,
        ),
      );

      await tester.tap(find.byType(ChildSwitcherBar));
      await tester.pumpAndSettle();
      expect(
        tester.getSemantics(
          find.ancestor(
            of: find.text('Asha Demo').last,
            matching: find.byType(ChildTile),
          ),
        ),
        isSemantics(isSelected: true, isButton: true, hasTapAction: true),
      );
      semantics.dispose();
    });

    testWidgets('tap targets are large enough', (tester) async {
      await cubit.setChildren([asha, cara]);
      await pumpApp(tester, _frame(cubit));

      await expectLater(tester, meetsGuideline(androidTapTargetGuideline));
      await expectLater(tester, meetsGuideline(labeledTapTargetGuideline));
    });
  });

  test('the switcher\'s labels are shared label keys', () {
    expect(
      sharedLabelKeys,
      containsAll([chooseChildKey, switchChildKey, yourChildrenKey]),
    );
  });
}
