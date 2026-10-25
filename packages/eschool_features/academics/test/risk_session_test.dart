import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'risk_test.dart' show RiskFake;
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class _ContextRepository implements MeContextRepository {
  String role = 'Teacher';
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
    'feature_flags': {'early_warning': true, 'module_switches': true},
    'modules': [
      if (module) {'key': 'early_warning', 'read_only': false},
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
    'same-account permission refresh reloads configuration even when context fields stay identical',
    (tester) async {
      final contextRepository = _ContextRepository();
      final session = MeContextCubit(
        repository: contextRepository,
        cache: InMemoryMeContextCache(),
      );
      await session.load();
      addTearDown(session.close);
      final pinned = session.context!, repository = RiskFake();
      await tester.pumpWidget(
        BlocProvider.value(
          value: session,
          child: MaterialApp(
            home: RiskSessionBoundary(
              pinned: pinned,
              child: RiskInterventionsScreen(
                repository: repository,
                canRead: () => true,
              ),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Review actions'), findsOneWidget);
      final before = repository.configurationCalls;
      await session.load();
      await tester.pumpAndSettle();
      expect(riskAccessStillCurrent(pinned, session.context), isTrue);
      expect(repository.configurationCalls, greaterThan(before));
      expect(tester.takeException(), isNull);
    },
  );
  for (final change in ['role', 'module', 'guardian']) {
    testWidgets(
      'same account context $change revocation unmounts private risk follow-up',
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
              home: RiskSessionBoundary(
                pinned: pinned,
                child: const Scaffold(body: Text('Private topic evidence')),
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
        expect(riskAccessStillCurrent(pinned, session.context), isFalse);
        expect(find.text('Private topic evidence'), findsNothing);
        expect(
          find.text('Reopen staff follow-up to check your access.'),
          findsOneWidget,
        );
        expect(tester.takeException(), isNull);
      },
    );
  }
}
