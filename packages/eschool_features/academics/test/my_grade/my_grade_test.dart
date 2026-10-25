import 'dart:async';
import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import '../message_test_repository.dart';

Map<String, dynamic> access({int school = 1, int reader = 7}) => {
  'school_id': school,
  'reader_id': reader,
  'session_year_id': 2,
  'today': '2026-10-25',
  'writable': true,
  'grades': [
    {'id': 5, 'name': 'Grade 5'},
  ],
};
Map<String, dynamic> dashboard() => {
  'school_id': 1,
  'reader_id': 7,
  'session_year_id': 2,
  'grade_level_id': 5,
  'on_date': '2026-10-25',
  'writable': true,
  'page': 1,
  'last_page': 1,
  'total': 1,
  'risk_available': true,
  'learners': [
    {
      'learner_id': 11,
      'name': 'Synthetic private learner',
      'class_section_id': 6,
      'register_id': 9,
      'attendance_state': 'absent',
      'signals': [],
    },
  ],
  'discipline': {
    'available': true,
    'items': [
      {
        'id': 4,
        'learner_id': 11,
        'learner_name': 'Synthetic private learner',
        'revision': 1,
        'occurred_on': '2026-10-25',
        'status': 'pending_review',
      },
    ],
    'page': 1,
    'last_page': 1,
    'total': 1,
  },
  'awards': {'available': true, 'items': []},
  'actions': {'registers': true, 'messages': true, 'interventions': true},
};
ApiMyGradeRepository apiRepository(FakeApi api, {bool Function()? active}) =>
    ApiMyGradeRepository(
      ApiClient(
        authToken: () => '',
        sendsBearerToken: false,
        onUnauthorized: () {},
        createDio: () => Dio()..httpClientAdapter = api,
      ),
      schoolId: 1,
      readerId: 7,
      contextActive: active ?? () => true,
    );

class Grades implements MyGradeRepository {
  @override
  final int schoolId = 1, readerId = 7;
  @override
  bool isActive = true;
  bool denied = false;
  Completer<MyGradeDashboard>? delayed;
  @override
  Future<MyGradeAccess> access() async {
    if (denied) throw ApiException('withdrawn', statusCode: 403);
    return MyGradeAccess.fromJson(TestsData.access());
  }

  @override
  Future<MyGradeDashboard> dashboard(
    int grade, {
    String? onDate,
    int page = 1,
    int disciplinePage = 1,
  }) async =>
      delayed?.future ?? MyGradeDashboard.fromJson(TestsData.dashboard());
}

class TestsData {
  static Map<String, dynamic> access() => {
    'school_id': 1,
    'reader_id': 7,
    'session_year_id': 2,
    'today': '2026-10-25',
    'writable': true,
    'grades': [
      {'id': 5, 'name': 'Grade 5'},
    ],
  };
  static Map<String, dynamic> dashboard() => _dashboard();
}

final _dashboard = dashboard;

Future<void> resetLifecycle(WidgetTester tester) async {
  await tester.pumpWidget(const SizedBox());
  if (tester.binding.lifecycleState == AppLifecycleState.paused)
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.hidden);
  if (tester.binding.lifecycleState == AppLifecycleState.hidden)
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
  if (tester.binding.lifecycleState != AppLifecycleState.resumed)
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
  await tester.pump();
}

void background(WidgetTester tester) {
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.hidden);
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.paused);
}

void foreground(WidgetTester tester) {
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.hidden);
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
}

class GradeMessages extends FakeMessageRepository {
  GradeMessages(this.head);
  final bool head;
  @override
  Future<CommunicationConfiguration> configuration({int? year}) async {
    final json = (await super.configuration(year: year)).toJson();
    (json['scopes'] as List).add({
      'kind': 'grade',
      'target_id': 5,
      'name': 'Grade 5',
    });
    (json['rules'] as Map)['head_grades'] = head ? [5] : <int>[];
    (json['rules'] as Map)['grade_head_exempt'] = head;
    return CommunicationConfiguration.fromJson(json);
  }
}

void main() {
  test(
    'cookie grade requests pin school/account and reject foreign identity or late withdrawal',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/my-grade/access': {'error': false, 'data': access()},
        },
      );
      expect((await apiRepository(api).access()).grades.single.id, 5);
      expect(api.requests.single.headers['X-School-Id'], '1');
      expect(api.requests.single.headers['X-Account-Id'], '7');
      expect(api.requests.single.headers.containsKey('Authorization'), isFalse);
      final foreign = FakeApi(
        routes: {
          'GET v2/my-grade/access': {'error': false, 'data': access(reader: 8)},
        },
      );
      await expectLater(
        apiRepository(foreign).access(),
        throwsA(isA<ApiException>()),
      );
      var current = true;
      final late = FakeApi(
        routes: {
          'GET v2/my-grade/access': (RequestOptions _) {
            current = false;
            return {'error': false, 'data': access()};
          },
        },
      );
      await expectLater(
        apiRepository(late, active: () => current).access(),
        throwsA(isA<ApiException>()),
      );
    },
  );
  test(
    'bound register refuses a replacement account before any HTTP',
    () async {
      final api = FakeApi(routes: {});
      final repository = ApiRegisterRepository(
        ApiClient(
          authToken: () => 'synthetic',
          onUnauthorized: () {},
          createDio: () => Dio()..httpClientAdapter = api,
        ),
        schoolId: 1,
        accountId: 7,
        contextActive: () => false,
      );
      await expectLater(repository.matrix(9), throwsA(isA<ApiException>()));
      expect(api.requests, isEmpty);
    },
  );
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets(
      'grade-head private dashboard fits $width and opens the exact record',
      (tester) async {
        await resetLifecycle(tester);
        addTearDown(() => resetLifecycle(tester));
        tester.view.physicalSize = Size(width, 1000);
        tester.view.devicePixelRatio = 1;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final repo = Grades();
        int? record;
        await tester.pumpWidget(
          MaterialApp(
            home: MyGradeScreen(
              repository: repo,
              onRegister: (_, __) async {},
              onDiscipline: (row, _) async {
                record = row.id;
              },
              onMessage: (_) async {},
              onRisk: (_) async {},
              onAwards: (_) async {},
            ),
          ),
        );
        await tester.pumpAndSettle();
        expect(find.text('Synthetic private learner'), findsNWidgets(2));
        final target = find
            .widgetWithText(ListTile, 'Synthetic private learner')
            .last;
        await tester.ensureVisible(target);
        await tester.tap(target);
        await tester.pumpAndSettle();
        expect(record, 4);
        expect(tester.takeException(), isNull);
        background(tester);
        await tester.pump();
        expect(find.text('Synthetic private learner'), findsNothing);
        repo.denied = true;
        foreground(tester);
        await tester.pumpAndSettle();
        expect(find.text('Synthetic private learner'), findsNothing);
        expect(
          find.text(
            'Could not open My grade. Refresh your access and try again.',
          ),
          findsOneWidget,
        );
      },
    );
  }
  testWidgets(
    'private editor retains draft offstage but never resurfaces after owner withdrawal',
    (tester) async {
      await resetLifecycle(tester);
      addTearDown(() => resetLifecycle(tester));
      var current = true;
      final changes = ValueNotifier<int>(0);
      final resume = Completer<bool>();
      final controller = TextEditingController(
        text: 'Synthetic pending correction',
      );
      addTearDown(changes.dispose);
      addTearDown(controller.dispose);
      await tester.pumpWidget(
        MaterialApp(
          home: MyGradePrivateAction(
            current: () => current,
            owner: changes,
            reauthorize: () => resume.future,
            child: Scaffold(body: TextField(controller: controller)),
          ),
        ),
      );
      background(tester);
      await tester.pump();
      expect(find.byType(TextField), findsNothing);
      expect(controller.text, 'Synthetic pending correction');
      foreground(tester);
      await tester.pump();
      expect(find.byType(TextField), findsNothing);
      current = false;
      changes.value++;
      await tester.pump();
      resume.complete(true);
      await tester.pumpAndSettle();
      expect(find.byType(TextField), findsNothing);
      expect(
        find.text('Reopen My grade to check your access.'),
        findsOneWidget,
      );
    },
  );
  for (final head in [true, false]) {
    testWidgets(
      'grade parent message default requires fresh head scope: $head',
      (tester) async {
        await resetLifecycle(tester);
        addTearDown(() => resetLifecycle(tester));
        final repo = GradeMessages(head);
        await tester.pumpWidget(
          MaterialApp(
            home: SchoolMessageEditorScreen(
              repository: repo,
              initialGradeId: 5,
            ),
          ),
        );
        await tester.pumpAndSettle();
        final audience = find.widgetWithText(
          SwitchListTile,
          'Parents and guardians of these learners',
        );
        expect(tester.widget<SwitchListTile>(audience).value, head);
        Finder field(String label) => find.byWidgetPredicate(
          (w) => w is TextField && w.decoration?.labelText == label,
        );
        await tester.enterText(
          field('Message subject'),
          'Synthetic grade follow-up',
        );
        await tester.enterText(
          field('Message Markdown'),
          'Synthetic private grade parent message',
        );
        final save = find.text('Save private message');
        await tester.ensureVisible(save);
        await tester.tap(save);
        await tester.pumpAndSettle();
        expect(repo.writes, hasLength(1));
        expect(repo.writes.single.audience.parents, head);
        expect(
          repo.writes.single.audience.kind.value,
          head ? 'grade' : 'group',
        );
        expect(repo.writes.single.audience.targetId, head ? 5 : 9);
        expect(tester.takeException(), isNull);
      },
    );
  }

  testWidgets(
    'late dashboard cannot return private learner data while hidden',
    (tester) async {
      await resetLifecycle(tester);
      addTearDown(() => resetLifecycle(tester));
      final repo = Grades()..delayed = Completer<MyGradeDashboard>();
      await tester.pumpWidget(
        MaterialApp(
          home: MyGradeScreen(
            repository: repo,
            onRegister: (_, __) async {},
            onDiscipline: (_, __) async {},
            onMessage: (_) async {},
            onRisk: (_) async {},
            onAwards: (_) async {},
          ),
        ),
      );
      await tester.pump();
      background(tester);
      await tester.pump();
      repo.delayed!.complete(MyGradeDashboard.fromJson(dashboard()));
      await tester.pump();
      expect(find.text('Synthetic private learner'), findsNothing);
      await tester.pumpWidget(const SizedBox());
      foreground(tester);
    },
  );
}
