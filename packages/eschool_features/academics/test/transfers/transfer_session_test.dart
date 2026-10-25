import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class _ContextRepository implements MeContextRepository {
  String role = 'Parent';
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
    'feature_flags': {'school_transfers': true, 'module_switches': true},
    'modules': [
      if (module) {'key': 'school_transfers', 'read_only': false},
    ],
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
    'old account-level repository guards never reactivate after refresh',
    (tester) async {
      final source = _ContextRepository()..child = false;
      final session = MeContextCubit(
        repository: source,
        cache: InMemoryMeContextCache(),
      );
      await session.load();
      addTearDown(session.close);
      final guards = <bool Function()>[];
      await tester.pumpWidget(
        BlocProvider.value(
          value: session,
          child: MaterialApp(
            home: TransferSessionBoundary(
              pinned: session.context!,
              builder: (active) {
                guards.add(active);
                return const Scaffold(body: Text('Private transfer records'));
              },
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(guards.single(), isTrue);
      await session.load();
      await tester.pumpAndSettle();
      expect(guards, hasLength(2));
      expect(guards.first(), isFalse);
      expect(guards.last(), isTrue);
    },
  );
  for (final change in ['role', 'module', 'guardian']) {
    testWidgets('$change withdrawal erases private transfer records', (
      tester,
    ) async {
      final source = _ContextRepository();
      final session = MeContextCubit(
        repository: source,
        cache: InMemoryMeContextCache(),
      );
      await session.load();
      addTearDown(session.close);
      final pinned = session.context!;
      await tester.pumpWidget(
        BlocProvider.value(
          value: session,
          child: MaterialApp(
            home: TransferSessionBoundary(
              pinned: pinned,
              builder: (_) =>
                  const Scaffold(body: Text('Private transfer records')),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Private transfer records'), findsOneWidget);
      if (change == 'role') source.role = 'Student';
      if (change == 'module') source.module = false;
      if (change == 'guardian') source.child = false;
      await session.load();
      await tester.pumpAndSettle();
      expect(find.text('Private transfer records'), findsNothing);
      expect(transferAccessStillCurrent(pinned, session.context), isFalse);
    });
  }
}
