import 'dart:async';
import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'activity_test_repository.dart';

class _ContextRepository implements MeContextRepository {
  String role = 'Teacher';
  bool enabled = true, child = true, readonly = false;
  Completer<MeContext?>? pending;
  @override
  Future<MeContext?> fetchContext() async =>
      pending?.future ??
      MeContext.fromJson({
        ...academicContextFixture(role: role),
        'user': {
          'id': 12,
          'first_name': 'Synthetic',
          'last_name': 'Reader',
          'name': 'Synthetic Reader',
          'email': 'synthetic@example.test',
          'image': null,
          'roles': [role],
        },
        'feature_flags': {'module_switches': true},
        'modules': [
          if (enabled) {'key': 'groups', 'read_only': readonly},
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
                'id': 1,
                'name': 'Synthetic School',
                'short_name': null,
                'crest_url': null,
              },
            },
        ],
      });
}

Future<MeContextCubit> _session(_ContextRepository repository) async {
  final cubit = MeContextCubit(
    repository: repository,
    cache: InMemoryMeContextCache(),
  );
  await cubit.load();
  addTearDown(cubit.close);
  return cubit;
}

Finder field(String label) => find.byWidgetPredicate(
  (w) => w is TextField && w.decoration?.labelText == label,
);
Future<void> tap(WidgetTester tester, String label) async {
  final target = find.text(label);
  await tester.ensureVisible(target);
  await tester.tap(target);
  await tester.pumpAndSettle();
}

void main() {
  testWidgets(
    'coalesced same-context refresh discards draft and reauthorizes revoked group grant',
    (tester) async {
      final contexts = _ContextRepository(), data = FakeActivityRepository();
      final me = await _session(contexts);
      final pinned = me.context!;
      await tester.pumpWidget(
        BlocProvider.value(
          value: me,
          child: MaterialApp(
            home: ActivitySessionBoundary(
              pinned: pinned,
              builder: (_, active) => ActivityGroupsScreen(repository: data),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tap(tester, 'Hockey');
      await tap(tester, 'New material');
      await tester.enterText(field('Material Markdown'), 'PRIVATE OLD DRAFT');
      data.readError = ApiException(
        'Group allocation revoked',
        statusCode: 403,
      );
      await me.load();
      await tester.pumpAndSettle();
      expect(activityAccessStillCurrent(pinned, me.context), isTrue);
      expect(find.text('PRIVATE OLD DRAFT', skipOffstage: false), findsNothing);
      expect(find.text('Hockey'), findsNothing);
      expect(find.text('Not available for this account'), findsOneWidget);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'old repository generation stays invalid after identical context returns',
    (tester) async {
      final me = await _session(_ContextRepository());
      final api = FakeApi(routes: {}), readers = <ApiActivityRepository>[];
      final client = ApiClient(
        authToken: () => 'synthetic-token',
        onUnauthorized: () {},
        createDio: () => Dio()..httpClientAdapter = api,
      );
      await tester.pumpWidget(
        BlocProvider.value(
          value: me,
          child: MaterialApp(
            home: ActivitySessionBoundary(
              pinned: me.context!,
              builder: (_, active) {
                readers.add(
                  ApiActivityRepository(
                    client,
                    schoolId: 1,
                    accountId: 12,
                    contextActive: active,
                  ),
                );
                return const Scaffold(body: Text('Private reader'));
              },
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      final old = readers.last;
      expect(old.isActive, isTrue);
      await me.load();
      await tester.pumpAndSettle();
      expect(readers.last, isNot(same(old)));
      expect(readers.last.isActive, isTrue);
      expect(old.isActive, isFalse);
      await expectLater(old.groups(), throwsA(isA<ApiException>()));
      expect(api.requests, isEmpty);
    },
  );
  for (final change in ['role', 'module', 'guardian', 'readonly']) {
    testWidgets('same-account $change change removes private activity reader', (
      tester,
    ) async {
      final contexts = _ContextRepository(), me = await _session(contexts);
      final pinned = me.context!;
      await tester.pumpWidget(
        BlocProvider.value(
          value: me,
          child: MaterialApp(
            home: ActivitySessionBoundary(
              pinned: pinned,
              builder: (_, active) =>
                  const Scaffold(body: Text('Private group content')),
            ),
          ),
        ),
      );
      if (change == 'role') contexts.role = 'Counsellor';
      if (change == 'module') contexts.enabled = false;
      if (change == 'guardian') contexts.child = false;
      if (change == 'readonly') contexts.readonly = true;
      await me.load();
      await tester.pumpAndSettle();
      expect(activityAccessStillCurrent(pinned, me.context), isFalse);
      expect(
        find.text('Private group content', skipOffstage: false),
        findsNothing,
      );
      expect(
        find.text('Your activity access changed. Reopen the group.'),
        findsOneWidget,
      );
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets('pending authority refresh hides the old reader immediately', (
    tester,
  ) async {
    final contexts = _ContextRepository(), me = await _session(contexts);
    final pinned = me.context!;
    await tester.pumpWidget(
      BlocProvider.value(
        value: me,
        child: MaterialApp(
          home: ActivitySessionBoundary(
            pinned: pinned,
            builder: (_, active) =>
                const Scaffold(body: Text('Private group content')),
          ),
        ),
      ),
    );
    contexts.pending = Completer<MeContext?>();
    final load = me.load();
    await tester.pump();
    expect(
      find.text('Private group content', skipOffstage: false),
      findsNothing,
    );
    contexts.pending!.complete(pinned);
    await load;
    await tester.pumpAndSettle();
    expect(find.text('Private group content'), findsOneWidget);
  });
}
