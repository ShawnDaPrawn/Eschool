import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class _ContextSource implements MeContextRepository {
  String role = 'Parent';
  bool module = true, readonly = false, child = true;
  int school = 1, account = 7;
  @override
  Future<MeContext?> fetchContext() async => MeContext.fromJson({
    'user': {
      'id': account,
      'first_name': 'Synthetic',
      'last_name': 'Reader',
      'name': 'Synthetic Reader',
      'email': 'synthetic@example.test',
      'image': null,
      'roles': [role],
    },
    'current_school': {
      'id': school,
      'name': 'Synthetic School',
      'short_name': null,
      'crest_url': null,
      'tagline': null,
      'timezone': 'Africa/Johannesburg',
      'branding': {'theme_color': null, 'secondary_color': null},
      'session_year': null,
    },
    'feature_flags': {'module_switches': true},
    'modules': [
      if (module) {'key': 'ask_school', 'read_only': readonly},
    ],
    'children': [
      if (child)
        {
          'id': 17,
          'user_id': 17,
          'first_name': 'Synthetic',
          'last_name': 'Child',
          'name': 'Synthetic Child',
          'image': null,
          'school': {
            'id': school,
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
    'coalesced identical context refresh permanently invalidates the old reader generation',
    (tester) async {
      final source = _ContextSource(),
          session = MeContextCubit(
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
            home: AssistanceSessionBoundary(
              pinned: session.context!,
              builder: (_, active) {
                guards.add(active);
                return const Scaffold(
                  body: Text('Authorized assistance reader'),
                );
              },
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      final first = guards.first;
      expect(first(), isTrue);
      await session.load();
      await tester.pumpAndSettle();
      expect(first(), isFalse);
      expect(guards.last(), isTrue);
      expect(guards.length, greaterThan(1));
      expect(tester.takeException(), isNull);
    },
  );
  for (final change in [
    'account',
    'school',
    'role',
    'guardian',
    'module',
    'readonly',
  ]) {
    testWidgets('$change refresh removes the pinned assistance body', (
      tester,
    ) async {
      final source = _ContextSource(),
          session = MeContextCubit(
            repository: source,
            cache: InMemoryMeContextCache(),
          );
      await session.load();
      addTearDown(session.close);
      await tester.pumpWidget(
        BlocProvider.value(
          value: session,
          child: MaterialApp(
            home: AssistanceSessionBoundary(
              pinned: session.context!,
              builder: (_, active) =>
                  const Scaffold(body: Text('PRIVATE PINNED SUPPORT')),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      switch (change) {
        case 'account':
          source.account = 99;
        case 'school':
          source.school = 2;
        case 'role':
          source.role = 'Facilitator';
        case 'guardian':
          source.child = false;
        case 'module':
          source.module = false;
        case 'readonly':
          source.readonly = true;
      }
      await session.load();
      await tester.pumpAndSettle();
      expect(
        find.text('PRIVATE PINNED SUPPORT', skipOffstage: false),
        findsNothing,
      );
      expect(tester.takeException(), isNull);
    });
  }
}
