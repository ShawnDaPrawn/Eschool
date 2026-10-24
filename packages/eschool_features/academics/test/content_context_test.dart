import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class ContextRepository implements MeContextRepository {
  int account = 12;
  @override
  Future<MeContext?> fetchContext() async => MeContext.fromJson({
    'user': {
      'id': account,
      'first_name': 'Test',
      'last_name': 'Reader',
      'name': 'Test Reader',
      'email': 'test@example.test',
      'image': null,
      'roles': ['Parent'],
    },
  });
}

void main() {
  for (final change in ['account', 'school', 'child', 'new child link']) {
    testWidgets('private pushed reader closes on $change switch', (
      tester,
    ) async {
      final store = InMemorySelectionStore(schoolId: 1, childId: 11);
      final schools = SchoolSelectionCubit(store: store),
          children = ChildSelectionCubit(store: store);
      await children.setChildren([
        MeChild.of(id: 11, name: 'First', schoolId: 1),
        MeChild.of(id: 12, name: 'Second', schoolId: 1),
      ]);
      final repository = ContextRepository();
      final session = MeContextCubit(
        repository: repository,
        cache: InMemoryMeContextCache(),
      );
      await session.load();
      addTearDown(schools.close);
      addTearDown(children.close);
      addTearDown(session.close);
      await tester.pumpWidget(
        MultiBlocProvider(
          providers: [
            BlocProvider.value(value: schools),
            BlocProvider.value(value: children),
            BlocProvider.value(value: session),
          ],
          child: MaterialApp(
            home: Builder(
              builder: (context) => Scaffold(
                body: TextButton(
                  onPressed: () => Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => ContentContextBoundary(
                        schoolId: 1,
                        readerId: 12,
                        learnerId: 11,
                        child: Builder(
                          builder: (context) => Scaffold(
                            body: TextButton(
                              onPressed: () => Navigator.of(context).push(
                                MaterialPageRoute(
                                  builder: (_) => const Scaffold(
                                    body: Text('Private body'),
                                  ),
                                ),
                              ),
                              child: const Text('Open reader'),
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                  child: const Text('Open library'),
                ),
              ),
            ),
          ),
        ),
      );
      await tester.tap(find.text('Open library'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Open reader'));
      await tester.pumpAndSettle();
      expect(find.text('Private body'), findsOneWidget);
      if (change == 'new child link') {
        final navigator = tester.state<NavigatorState>(
          find.byType(Navigator).first,
        );
        navigator.popUntil((route) => route.isFirst);
        navigator.push(
          MaterialPageRoute<void>(
            builder: (_) => const Scaffold(body: Text('New child link')),
          ),
        );
      }
      if (change == 'account') {
        repository.account = 13;
        await session.load();
      } else if (change == 'school') {
        await schools.follow(2);
      } else {
        await children.select(12);
      }
      await tester.pumpAndSettle();
      expect(find.text('Private body'), findsNothing);
      expect(
        find.text(
          change == 'new child link' ? 'New child link' : 'Open library',
        ),
        findsOneWidget,
      );
    });
  }
}
