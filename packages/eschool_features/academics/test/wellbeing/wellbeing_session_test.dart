import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class Contexts implements MeContextRepository {
  String role = 'Counsellor';
  bool enabled = true;
  @override
  Future<MeContext?> fetchContext() async => MeContext.fromJson({
    ...academicContextFixture(role: role),
    'user': {
      'id': 12,
      'first_name': 'Synthetic',
      'last_name': 'Counsellor',
      'name': 'Synthetic Counsellor',
      'email': 'synthetic@example.test',
      'image': null,
      'roles': [role],
    },
    'feature_flags': {'module_switches': true},
    'modules': [
      if (enabled) {'key': 'wellbeing', 'read_only': false},
    ],
  });
}

void main() {
  testWidgets(
    'identical same-account refresh permanently invalidates old clinical reader',
    (tester) async {
      final contexts = Contexts();
      final session = MeContextCubit(
        repository: contexts,
        cache: InMemoryMeContextCache(),
      );
      await session.load();
      addTearDown(session.close);
      final api = FakeApi(routes: {}), readers = <ApiWellbeingRepository>[];
      final client = ApiClient(
        authToken: () => 'synthetic',
        onUnauthorized: () {},
        createDio: () => Dio()..httpClientAdapter = api,
      );
      await tester.pumpWidget(
        BlocProvider.value(
          value: session,
          child: MaterialApp(
            home: WellbeingSessionBoundary(
              pinned: session.context!,
              builder: (_, active) {
                readers.add(
                  ApiWellbeingRepository(
                    client,
                    schoolId: 1,
                    accountId: 12,
                    contextActive: active,
                  ),
                );
                return const Scaffold(body: Text('Private clinical reader'));
              },
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      final old = readers.last;
      expect(old.isActive, true);
      await session.load();
      await tester.pumpAndSettle();
      expect(readers.last, isNot(same(old)));
      expect(old.isActive, false);
      await expectLater(old.access(), throwsA(isA<ApiException>()));
      expect(api.requests, isEmpty);
    },
  );
  for (final change in ['role', 'module']) {
    testWidgets(
      'same-account $change withdrawal removes private clinical reader',
      (tester) async {
        final contexts = Contexts();
        // Use the mutable repository for the refresh and the same account payload.
        final current = MeContextCubit(
          repository: contexts,
          cache: InMemoryMeContextCache(),
        );
        await current.load();
        addTearDown(current.close);
        await tester.pumpWidget(
          BlocProvider.value(
            value: current,
            child: MaterialApp(
              home: WellbeingSessionBoundary(
                pinned: current.context!,
                builder: (_, active) =>
                    const Scaffold(body: Text('Private body')),
              ),
            ),
          ),
        );
        await tester.pumpAndSettle();
        expect(find.text('Private body'), findsOneWidget);
        if (change == 'role') {
          contexts.role = 'Teacher';
        } else {
          contexts.enabled = false;
        }
        await current.load();
        await tester.pumpAndSettle();
        expect(find.text('Private body'), findsNothing);
        expect(tester.takeException(), isNull);
      },
    );
  }
}
