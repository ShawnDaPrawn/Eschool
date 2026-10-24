import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  for (final changeChild in [false, true]) {
    testWidgets(
      'academic data clears on ${changeChild ? 'child' : 'school'} switch',
      (tester) async {
        final store = InMemorySelectionStore(schoolId: 1, childId: 11);
        final schools = SchoolSelectionCubit(store: store);
        final children = ChildSelectionCubit(store: store);
        await children.setChildren([
          MeChild.of(id: 11, name: 'First learner', schoolId: 1),
          MeChild.of(id: 12, name: 'Second learner', schoolId: 1),
        ]);
        addTearDown(schools.close);
        addTearDown(children.close);
        await tester.pumpWidget(
          MultiBlocProvider(
            providers: [
              BlocProvider.value(value: schools),
              BlocProvider.value(value: children),
            ],
            child: MaterialApp(
              home: Builder(
                builder: (context) => Scaffold(
                  body: FilledButton(
                    onPressed: () => Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => AcademicContextBoundary(
                          schoolId: 1,
                          childId: changeChild ? 11 : null,
                          child: const Scaffold(
                            body: Text('Private learner marks'),
                          ),
                        ),
                      ),
                    ),
                    child: const Text('Open marks'),
                  ),
                ),
              ),
            ),
          ),
        );
        await tester.tap(find.text('Open marks'));
        await tester.pumpAndSettle();
        expect(find.text('Private learner marks'), findsOneWidget);
        if (changeChild) {
          await children.select(12);
        } else {
          await schools.follow(2);
        }
        await tester.pump();
        expect(find.text('Private learner marks'), findsNothing);
        await tester.pumpAndSettle();
        expect(find.text('Open marks'), findsOneWidget);
      },
    );
  }
}
