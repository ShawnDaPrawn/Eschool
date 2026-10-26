import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

PathwayProjectionRequest scenario({int learner = 11, int? mark}) =>
    PathwayProjectionRequest.fromJson({
      'learner_id': learner,
      'revision': 1,
      'source': 'scenario',
      'marks': [
        {'subject_id': 4, 'percentage_bp': mark},
      ],
    });
void main() {
  late PathwaySmokeFixture fixture;
  late FakeApi api;
  late ApiPathwayRepository repository;
  late bool active;
  setUp(() {
    fixture = PathwaySmokeFixture();
    api = FakeApi(routes: fixture.routes);
    active = true;
    repository = ApiPathwayRepository(
      ApiClient(
        authToken: () => 'synthetic',
        onUnauthorized: () {},
        createDio: () => Dio()..httpClientAdapter = api,
      ),
      schoolId: 1,
      accountId: 21,
      contextActive: () => active,
    );
  });
  Future<PathwayReference> open() async {
    await repository.configuration();
    return repository.read(PathwaySmokeFixture.programme);
  }

  test(
    'pure readonly projection binds school/account and preserves a blank mark',
    () async {
      final row = await open();
      final value = await repository.project(row, scenario());
      expect(value.score!.projectedScore, isNull);
      expect(value.score!.subjects.first.percentageBp, isNull);
      expect(fixture.calculations.single['marks'], [
        {'subject_id': 4, 'percentage_bp': null},
      ]);
      final sent = api.requests.last;
      expect(sent.headers['X-School-Id'], '1');
      expect(sent.headers['X-Account-Id'], '21');
      expect(sent.queryParameters, isEmpty);
      expect(api.requests.where((r) => r.method != 'GET').length, 1);
      expect((sent.data as Map).containsKey('operation_id'), isFalse);
    },
  );
  test(
    'current guardian withdrawal prevents calculation after opening',
    () async {
      final row = await open();
      fixture.allowed = false;
      await expectLater(
        repository.project(row, scenario()),
        throwsA(isA<ApiException>()),
      );
      expect(fixture.calculations, isEmpty);
    },
  );
  test('replacement school response is refused', () async {
    api.routes['GET v2/pathways/configuration'] = (_) =>
        fixture.result({...fixture.configuration(), 'school_id': 2});
    await expectLater(repository.configuration(), throwsA(isA<ApiException>()));
  });
  test('replacement account response is refused', () async {
    api.routes['GET v2/pathways/configuration'] = (_) =>
        fixture.result({...fixture.configuration(), 'reader_id': 22});
    await expectLater(repository.configuration(), throwsA(isA<ApiException>()));
  });
  test('another learner cannot reach calculation', () async {
    final row = await open();
    await expectLater(
      repository.project(row, scenario(learner: 99)),
      throwsA(isA<ApiException>()),
    );
    expect(fixture.calculations, isEmpty);
  });
  test('replaced source revision requires explicit reopening', () async {
    final row = await open();
    fixture.revision = 2;
    await expectLater(
      repository.project(row, scenario()),
      throwsA(isA<ApiException>().having((e) => e.statusCode, 'status', 409)),
    );
    expect(fixture.calculations, isEmpty);
  });
  test('late projection cannot restore a withdrawn actor', () async {
    final row = await open();
    api.routes['POST v2/pathways/references/${row.id}/projection'] =
        (RequestOptions r) {
          active = false;
          return fixture.result(
            fixture.projection(Map<String, dynamic>.from(r.data as Map)),
          );
        };
    await expectLater(
      repository.project(row, scenario()),
      throwsA(isA<ApiException>()),
    );
  });
  test('wrong projection learner is refused', () async {
    final row = await open();
    api.routes['POST v2/pathways/references/${row.id}/projection'] =
        (RequestOptions r) => fixture.result({
          ...fixture.projection(Map<String, dynamic>.from(r.data as Map)),
          'learner_id': 99,
        });
    await expectLater(
      repository.project(row, scenario()),
      throwsA(isA<ApiException>()),
    );
  });
  Future<void> frame(WidgetTester tester, double width) async {
    tester.view.physicalSize = Size(width, 900);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(
      MaterialApp(home: PathwayScreen(repository: repository)),
    );
    await tester.pumpAndSettle();
  }

  Future<void> tap(WidgetTester tester, Finder finder) async {
    await tester.ensureVisible(finder);
    await tester.tap(finder);
    await tester.pumpAndSettle();
  }

  for (final width in [390.0, 1280.0]) {
    testWidgets('scenario, unknown and exact decimal input at $width', (
      tester,
    ) async {
      await frame(tester, width);
      await tap(tester, find.text('Synthetic programme'));
      await tap(tester, find.byKey(const ValueKey('pathway-calculate')));
      expect(find.text('Projected admission score: Unknown'), findsOneWidget);
      await tester.enterText(
        find.byKey(const ValueKey('pathway-mark-4')),
        '62,50',
      );
      await tester.pump();
      expect(find.text('Projected admission score: Unknown'), findsNothing);
      await tap(tester, find.byKey(const ValueKey('pathway-calculate')));
      expect(find.text('Projected admission score: 4'), findsOneWidget);
      expect(
        (fixture.calculations.last['marks'] as List).first['percentage_bp'],
        6250,
      );
      expect(find.byKey(const ValueKey('pathway-mark-6')), findsNothing);
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets(
    'guide shows mandatory and elective rules without inventing score',
    (tester) async {
      await frame(tester, 390);
      await tap(tester, find.text('Synthetic Grade 9 guide'));
      await tap(tester, find.byKey(const ValueKey('pathway-calculate')));
      expect(find.text('Review these subject choices'), findsOneWidget);
      await tap(tester, find.byKey(const ValueKey('pathway-choice-4')));
      await tap(tester, find.byKey(const ValueKey('pathway-choice-5')));
      await tap(tester, find.byKey(const ValueKey('pathway-calculate')));
      expect(
        find.text('Meets the entered subject-choice rules'),
        findsOneWidget,
      );
      expect(fixture.calculations.last['source'], 'selection');
      expect(find.textContaining('Projected admission score:'), findsNothing);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'background erases entered percentages and reopens only fresh sources',
    (tester) async {
      await frame(tester, 390);
      await tap(tester, find.text('Synthetic programme'));
      await tester.enterText(
        find.byKey(const ValueKey('pathway-mark-4')),
        '62.50',
      );
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.paused);
      await tester.pumpAndSettle();
      expect(find.byType(TextField), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.text('Synthetic programme'), findsOneWidget);
      await tap(tester, find.text('Synthetic programme'));
      expect(
        tester
            .widget<TextField>(find.byKey(const ValueKey('pathway-mark-4')))
            .controller!
            .text,
        isEmpty,
      );
      expect(fixture.calculations, isEmpty);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'released evidence requires an explicit year and preserves provisional coverage',
    (tester) async {
      await frame(tester, 390);
      await tap(tester, find.text('Synthetic programme'));
      await tap(tester, find.text('Released school marks'));
      await tap(tester, find.byKey(const ValueKey('pathway-calculate')));
      expect(
        find.text('Choose a school year for released marks.'),
        findsOneWidget,
      );
      expect(fixture.calculations, isEmpty);
      await tap(tester, find.byType(DropdownButtonFormField<int>).last);
      await tap(tester, find.text('Synthetic year').last);
      await tap(tester, find.byKey(const ValueKey('pathway-calculate')));
      expect(find.text('Projected admission score: 4'), findsOneWidget);
      expect(find.textContaining('Synthetic released grade'), findsOneWidget);
      expect(find.textContaining('75.00%'), findsOneWidget);
      expect(find.textContaining('Provisional'), findsOneWidget);
      expect(fixture.calculations.single['session_year_id'], 2);
      expect(fixture.calculations.single['term_id'], isNull);
      expect(fixture.calculations.single['source'], 'released');
      expect(fixture.calculations.single['marks'], isNull);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'withdrawn source clears entered marks and refuses new private calculation',
    (tester) async {
      await frame(tester, 390);
      await tap(tester, find.text('Synthetic programme'));
      await tester.enterText(
        find.byKey(const ValueKey('pathway-mark-4')),
        '62.50',
      );
      fixture.allowed = false;
      await tap(tester, find.byKey(const ValueKey('pathway-calculate')));
      expect(find.byType(TextField), findsNothing);
      expect(fixture.calculations, isEmpty);
      expect(find.text('Access withdrawn'), findsOneWidget);
    },
  );
}
