import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class _ContextRepository implements MeContextRepository {
  String role = 'Student';
  bool module = true, child = true;
  @override
  Future<MeContext?> fetchContext() async => MeContext.fromJson({
    'user': {
      'id': 12,
      'first_name': 'Synthetic',
      'last_name': 'Teacher',
      'name': 'Synthetic Teacher',
      'email': 'synthetic@example.test',
      'image': null,
      'roles': [role],
    },
    'current_school': {
      'id': 1,
      'name': 'Synthetic School',
      'short_name': null,
      'crest_url': null,
      'tagline': null,
      'timezone': 'Africa/Johannesburg',
      'branding': {'theme_color': null, 'secondary_color': null},
      'session_year': null,
    },
    'feature_flags': {'daily_practice': true, 'module_switches': true},
    'modules': [
      if (module) {'key': 'daily_practice', 'read_only': false},
    ],
    'approval': {
      'status': child ? 'approved' : 'withdrawn',
      'approved_by_count': 1,
    },
    'children': [
      if (child)
        {
          'id': 11,
          'user_id': 11,
          'first_name': 'Synthetic',
          'last_name': 'Child',
          'name': 'Synthetic Child',
          'image': null,
          'school': {
            'id': 1,
            'name': 'Synthetic School',
            'short_name': null,
            'crest_url': null,
          },
        },
    ],
  });
}

void main() {
  testWidgets(
    'identical profile refresh invalidates the previous practice lease',
    (tester) async {
      final contextRepository = _ContextRepository();
      final session = MeContextCubit(
        repository: contextRepository,
        cache: InMemoryMeContextCache(),
      );
      await session.load();
      addTearDown(session.close);
      final pinned = session.context!;
      final callbacks = <bool Function()>[];
      await tester.pumpWidget(
        BlocProvider.value(
          value: session,
          child: MaterialApp(
            home: PracticeSessionBoundary(
              pinned: pinned,
              builder: (context, active) {
                callbacks.add(active);
                return const Scaffold(body: Text('Own practice question'));
              },
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      final original = callbacks.first;
      expect(original(), isTrue);
      await session.load();
      await tester.pumpAndSettle();
      expect(practiceAccessStillCurrent(pinned, session.context), isTrue);
      expect(original(), isFalse);
      expect(callbacks.last(), isTrue);
      expect(find.text('Own practice question'), findsOneWidget);
      expect(tester.takeException(), isNull);
    },
  );
  for (final change in ['role', 'module', 'guardian']) {
    testWidgets(
      'same account context $change revocation unmounts private school daily_practice',
      (tester) async {
        final repository = _ContextRepository();
        final session = MeContextCubit(
          repository: repository,
          cache: InMemoryMeContextCache(),
        );
        await session.load();
        addTearDown(session.close);
        final pinned = session.context!;
        await tester.pumpWidget(
          BlocProvider.value(
            value: session,
            child: MaterialApp(
              home: PracticeSessionBoundary(
                pinned: pinned,
                builder: (_, active) =>
                    const Scaffold(body: Text('Private topic evidence')),
              ),
            ),
          ),
        );
        expect(find.text('Private topic evidence'), findsOneWidget);
        if (change == 'role') repository.role = 'Counsellor';
        if (change == 'module') repository.module = false;
        if (change == 'guardian') repository.child = false;
        await session.load();
        await tester.pumpAndSettle();
        expect(practiceAccessStillCurrent(pinned, session.context), isFalse);
        expect(find.text('Private topic evidence'), findsNothing);
        expect(
          find.text('Reopen practice to check your access.'),
          findsOneWidget,
        );
        expect(tester.takeException(), isNull);
      },
    );
  }
}
