import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:eschool_academics/src/peer_tutoring/peer_pending.dart';

void main() {
  WidgetController.hitTestWarningShouldBeFatal = true;
  late PeerSmokeFixture fixture;
  late FakeApi transport;
  late ApiPeerRepository repository;
  var active = true;
  ApiPeerRepository build({String persona = 'staff'}) => ApiPeerRepository(
    ApiClient(
      authToken: () => 'synthetic',
      onUnauthorized: () {},
      createDio: () => Dio()..httpClientAdapter = transport,
    ),
    schoolId: 1,
    accountId: 12,
    persona: persona,
    contextActive: () => active,
  );
  setUp(() {
    active = true;
    fixture = PeerSmokeFixture();
    transport = FakeApi(routes: fixture.routes);
    repository = build();
  });
  PeerIntent complete() => PeerIntent(
    action: PeerAction.complete,
    target: PeerSmokeFixture.existing,
    body: {
      'persona': 'staff',
      'operation_id': 'a3d950bd-8713-435c-899b-efc72d96a22f',
      'revision': 1,
      'minutes': 35,
    },
  );
  for (final replacement in [
    {'school_id': 2},
    {'reader_id': 99},
    {'persona': 'parent'},
  ]) {
    test('configuration rejects replaced scope $replacement', () async {
      transport.routes['GET v2/peer-tutoring/configuration'] = (_) =>
          fixture.result({...fixture.configuration(), ...replacement});
      await expectLater(
        repository.configuration(),
        throwsA(isA<ApiException>()),
      );
    });
  }
  test(
    'private transport binds purpose school and reader without query writes',
    () async {
      await repository.configuration();
      await repository.roster(4);
      await repository.matches(4, 22);
      fixture.loseNext = false;
      final row = await repository.execute(complete());
      expect(row.serviceMinutes, 35);
      final request = transport.requests.last;
      expect(request.headers['X-School-Id'], '1');
      expect(request.headers['X-Account-Id'], '12');
      expect(request.queryParameters, isEmpty);
      expect((request.data as Map)['minutes'], 35);
      expect(transport.requests.first.queryParameters['persona'], 'staff');
    },
  );
  test(
    'unknown acknowledgement retries original UUID for one service effect',
    () async {
      await repository.configuration();
      final intent = complete();
      await expectLater(
        repository.execute(intent),
        throwsA(isA<ApiException>()),
      );
      final result = await repository.execute(intent);
      expect(result.serviceMinutes, 35);
      expect(fixture.effects, 1);
      expect(fixture.writes[0], fixture.writes[1]);
    },
  );
  test('family cannot borrow staff mutations or another group', () async {
    fixture = PeerSmokeFixture(persona: 'parent', reader: 12);
    transport = FakeApi(routes: fixture.routes);
    repository = build(persona: 'parent');
    await repository.configuration();
    await expectLater(
      repository.execute(complete()),
      throwsA(isA<ApiException>()),
    );
    await expectLater(repository.roster(4), throwsA(isA<ApiException>()));
    expect(fixture.effects, 0);
  });
  test(
    'family refuses unowned session or unexpected private partner ledger',
    () async {
      fixture = PeerSmokeFixture(persona: 'parent', reader: 12);
      transport = FakeApi(routes: fixture.routes);
      repository = build(persona: 'parent');
      await repository.configuration();
      transport.routes['GET v2/peer-tutoring/sessions'] = (_) => fixture.result(
        {
          'school_id': 1,
          'reader_id': 12,
          'items': [
            {...fixture.row(), 'tutor_learner_id': 33, 'tutee_learner_id': 44},
          ],
          'next_page': null,
        },
      );
      await expectLater(repository.sessions(), throwsA(isA<ApiException>()));
      transport.routes['GET v2/peer-tutoring/sessions'] = (_) => fixture.result(
        {
          'school_id': 1,
          'reader_id': 12,
          'items': [
            {...fixture.row(), 'tutor_learner_id': 22, 'tutee_learner_id': 11},
          ],
          'next_page': null,
        },
      );
      await expectLater(repository.sessions(), throwsA(isA<ApiException>()));
    },
  );
  test('late response cannot restore replaced account context', () async {
    await repository.configuration();
    transport.routes['GET v2/peer-tutoring/sessions'] = (_) {
      active = false;
      return fixture.result({
        'school_id': 1,
        'reader_id': 12,
        'items': [fixture.row()],
        'next_page': null,
      });
    };
    await expectLater(repository.sessions(), throwsA(isA<ApiException>()));
  });
  test('sealed retry expires and refuses replaced account context', () async {
    var now = DateTime.utc(2026, 2, 10);
    final pending = PeerPending(repository, complete(), clock: () => now);
    expect(pending.open(repository, authorized: true).body, complete().body);
    active = false;
    expect(() => pending.open(repository, authorized: true), throwsA(anything));
    active = true;
    final expired = PeerPending(repository, complete(), clock: () => now);
    now = now.add(const Duration(minutes: 16));
    expect(expired.expired, isTrue);
    expect(() => expired.open(repository, authorized: true), throwsA(anything));
    pending.close();
    expired.close();
  });
  Future<void> frame(WidgetTester tester, {double width = 390}) async {
    tester.view.physicalSize = Size(width, 1000);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(
      MaterialApp(home: PeerTutoringScreen(repository: repository)),
    );
    await tester.pumpAndSettle();
  }

  Future<void> tap(WidgetTester tester, Finder finder) async {
    await tester.ensureVisible(finder);
    await tester.pumpAndSettle();
    await tester.tap(finder);
    await tester.pumpAndSettle();
  }

  for (final width in [390.0, 1280.0]) {
    testWidgets('supervisor confirms exact service once at $width', (
      tester,
    ) async {
      await frame(tester, width: width);
      await tap(tester, find.text('Confirm supervised service'));
      await tester.enterText(find.byType(TextField).first, '35');
      await tap(tester, find.text('Confirm session change'));
      expect(find.text('Retry original request'), findsOneWidget);
      await tap(tester, find.text('Retry original request'));
      expect(fixture.effects, 1);
      expect(fixture.writes[0], fixture.writes[1]);
      expect(find.text('Verified service minutes: 35'), findsOneWidget);
      expect(find.text('Correct completed service'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets(
    'a throttled retry preserves the submitted operation until reconciliation',
    (tester) async {
      final key =
          'POST v2/peer-tutoring/sessions/${PeerSmokeFixture.existing}/complete';
      final original = transport.routes[key] as Object Function(RequestOptions);
      var calls = 0;
      transport.routes[key] = (RequestOptions request) {
        calls++;
        if (calls == 2)
          return const FakeResponse({
            'error': true,
            'message': 'Synthetic retry throttle',
          }, status: 429);
        return original(request);
      };
      await frame(tester, width: 1280);
      await tap(tester, find.text('Confirm supervised service'));
      await tester.enterText(find.byType(TextField).first, '35');
      await tap(tester, find.text('Confirm session change'));
      await tap(tester, find.text('Retry original request'));
      expect(find.text('Retry original request'), findsOneWidget);
      await tap(tester, find.text('Retry original request'));
      expect(fixture.effects, 1);
      expect(fixture.writes[0], fixture.writes[1]);
      expect(find.text('Verified service minutes: 35'), findsOneWidget);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'background clears reasons and withdrawal refuses late private history',
    (tester) async {
      await frame(tester);
      await tap(tester, find.text('Cancel peer session'));
      await tester.enterText(
        find.byType(TextField).first,
        'Sensitive supervision reason',
      );
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.paused);
      await tester.pumpAndSettle();
      expect(find.byType(TextField), findsNothing);
      fixture.allowed = false;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.text('Synthetic supervised room'), findsNothing);
      expect(find.text('Sensitive supervision reason'), findsNothing);
      expect(
        find.text('Your peer tutoring access changed. Reopen this screen.'),
        findsOneWidget,
      );
      expect(fixture.effects, 0);
      expect(tester.takeException(), isNull);
    },
  );
}
