import 'dart:async';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:eschool_academics/src/registers/register_repository.dart';
import 'package:eschool_academics/src/registers/register_store.dart';
import 'package:eschool_academics/src/registers/register_screen.dart';
import 'package:eschool_academics/src/registers/register_hub_screen.dart';
import 'package:eschool_academics/src/registers/family_attendance_screen.dart';
import 'package:eschool_academics/src/registers/today_attendance_card.dart';
import 'package:eschool_academics/src/testing/register_fixtures.dart';
import 'register_cubit_test.dart' show FakeRegisterRepository;

Future<void> frame(
  WidgetTester tester,
  Widget child, {
  double width = 1024,
  double scale = 1,
}) async {
  tester.view.physicalSize = Size(width, 900);
  tester.view.devicePixelRatio = 1;
  await tester.pumpWidget(
    MaterialApp(
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.indigo),
      ),
      builder: (context, child) => MediaQuery(
        data: MediaQuery.of(
          context,
        ).copyWith(textScaler: TextScaler.linear(scale)),
        child: child!,
      ),
      home: child,
    ),
  );
  await tester.pumpAndSettle();
}

Future<void> tap(WidgetTester tester, Finder finder) async {
  await tester.ensureVisible(finder);
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

void main() {
  for (final width in [390.0, 1024.0, 1280.0]) {
    testWidgets(
      '35 learner tap register captures atomically at ${width.toInt()}px',
      (tester) async {
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final repository = FakeRegisterRepository();
        await frame(
          tester,
          RegisterScreen.withRepository(repository, MemoryRegisterStore(), 4),
          width: width,
        );
        expect(find.text('35 unmarked'), findsOneWidget);
        await tap(
          tester,
          find.widgetWithText(OutlinedButton, 'Mark remaining present'),
        );
        expect(find.text('35 present'), findsOneWidget);
        expect(repository.writes, isEmpty);
        await tap(tester, find.widgetWithText(FilledButton, 'Save register'));
        expect(repository.writes.single.rows.length, 35);
        expect(
          repository.server.learners.every((row) => row.state == 'present'),
          isTrue,
        );
        expect(find.text('Register synced'), findsOneWidget);
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox.shrink());
      },
    );
  }
  testWidgets(
    'QR keyboard capture deduplicates and works with an offline queue',
    (tester) async {
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repository = FakeRegisterRepository()
        ..syncError = StateError('Offline');
      await frame(
        tester,
        RegisterScreen.withRepository(repository, MemoryRegisterStore(), 4),
        width: 390,
      );
      final input = find.byKey(const ValueKey('register-qr-input'));
      await tester.ensureVisible(input);
      await tester.enterText(input, 'NEAR1.synthetic-11.signature');
      await tester.testTextInput.receiveAction(TextInputAction.done);
      await tester.pumpAndSettle();
      await tester.enterText(input, 'NEAR1.synthetic-11.signature');
      await tester.testTextInput.receiveAction(TextInputAction.done);
      await tester.pumpAndSettle();
      expect(find.text('1 present'), findsOneWidget);
      await tap(tester, find.widgetWithText(FilledButton, 'Save register'));
      expect(repository.writes.single.rows.single.source_, 'qr');
      expect(find.text('1 saved operations pending sync'), findsOneWidget);
      expect(find.text('Offline copy'), findsOneWidget);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets(
    'read-only dates cannot capture and large text retains usable scrolling',
    (tester) async {
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repository = FakeRegisterRepository()
        ..server = AcademicRegisterMatrixData.fromJson(
          registerMatrixFixture(canWrite: false),
        );
      await frame(
        tester,
        RegisterScreen.withRepository(repository, MemoryRegisterStore(), 4),
        width: 390,
        scale: 2,
      );
      expect(
        tester
            .widget<FilledButton>(
              find.widgetWithText(FilledButton, 'Save register'),
            )
            .onPressed,
        isNull,
      );
      expect(find.textContaining('read-only'), findsOneWidget);
      expect(repository.writes, isEmpty);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets(
    'explicit conflict review creates a draft without automatically resubmitting',
    (tester) async {
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repository = FakeRegisterRepository()
        ..syncError = ApiException(
          'Competing edit',
          isApiMessage: true,
          statusCode: 409,
        );
      await frame(
        tester,
        RegisterScreen.withRepository(repository, MemoryRegisterStore(), 4),
      );
      await tap(
        tester,
        find.widgetWithText(OutlinedButton, 'Mark remaining present'),
      );
      repository.server = AcademicRegisterMatrixData.fromJson(
        registerMatrixFixture(revision: 2, entryRevision: 1, state: 'absent'),
      );
      await tap(tester, find.widgetWithText(FilledButton, 'Save register'));
      await tap(
        tester,
        find.widgetWithText(TextButton, 'Review saved changes'),
      );
      expect(find.text('Current: absent · Saved: present'), findsWidgets);
      await tap(
        tester,
        find.widgetWithText(FilledButton, 'Use reviewed changes'),
      );
      expect(repository.writes.length, 1);
      expect(find.text('Unsaved draft'), findsOneWidget);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets('hub opens the authorized homeroom into the shared register', (
    tester,
  ) async {
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    final repository = FakeRegisterRepository();
    final store = MemoryRegisterStore();
    await frame(
      tester,
      RegisterHubScreen(repository: repository, store: () async => store),
    );
    await tap(tester, find.widgetWithText(ListTile, 'Grade 7 A'));
    expect(find.byType(RegisterScreen), findsOneWidget);
    expect(find.text('35 unmarked'), findsOneWidget);
    expect(tester.takeException(), isNull);
    await tester.pumpWidget(const SizedBox.shrink());
  });
  testWidgets(
    'family reads only the selected learner and renders a legacy half-day plus QR',
    (tester) async {
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      Map<String, Object?> ok(Object data) => {
        'error': false,
        'message': 'Fetched',
        'code': 200,
        'data': data,
      };
      final adapter = FakeApi(
        routes: {
          'GET v2/academics/registers/learners/11/today': ok({
            'learner_id': 11,
            'on_date': '2026-02-10',
            'entries': [
              {
                'register_id': null,
                'label': 'Homeroom register',
                'kind': 'homeroom',
                'state': 'first_half',
                'source': 'legacy',
                'amended_at': null,
              },
            ],
          }),
          'GET v2/academics/registers/learners/11/qr': ok({
            'qr_code': 'NEAR1.synthetic-11.signature',
          }),
        },
      );
      final client = ApiClient(
        authToken: () => 'synthetic-token',
        onUnauthorized: () {},
        createDio: () => Dio()..httpClientAdapter = adapter,
      );
      await frame(
        tester,
        FamilyAttendanceScreen(
          repository: ApiRegisterRepository(client, schoolId: 7),
          learner: 11,
          learnerName: 'Thandi Mokoena',
        ),
        width: 390,
      );
      expect(find.text('First half absent'), findsOneWidget);
      expect(find.byType(QrImageView), findsOneWidget);
      expect(adapter.requests.length, 2);
      for (final request in adapter.requests) {
        expect(request.headers['X-School-Id'], '7');
        expect(request.uri.path, contains('/learners/11/'));
      }
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets(
    'a late old-child home response never replaces the selected child',
    (tester) async {
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final old = Completer<AcademicAttendanceTodayData>();
      final repository = _FamilyRepository(old);
      Widget card(int learner) => TodayAttendanceCard(
        repository: repository,
        learner: learner,
        scope: 'family:$learner',
      );
      tester.view.physicalSize = const Size(390, 900);
      tester.view.devicePixelRatio = 1;
      await tester.pumpWidget(MaterialApp(home: Scaffold(body: card(11))));
      await tester.pump();
      await tester.pumpWidget(MaterialApp(home: Scaffold(body: card(12))));
      await tester.pumpAndSettle();
      expect(find.text('Absent'), findsOneWidget);
      old.complete(
        AcademicAttendanceTodayData.fromJson({
          'learner_id': 11,
          'on_date': '2026-02-10',
          'entries': [
            {
              'register_id': 4,
              'label': 'Old child',
              'kind': 'homeroom',
              'state': 'present',
              'source': 'tap',
              'amended_at': null,
            },
          ],
        }),
      );
      await tester.pumpAndSettle();
      expect(find.text('Absent'), findsOneWidget);
      expect(find.text('Present'), findsNothing);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
}

class _FamilyRepository extends FakeRegisterRepository {
  _FamilyRepository(this.old);
  final Completer<AcademicAttendanceTodayData> old;
  @override
  Future<AcademicAttendanceTodayData> today(int learner) async => learner == 11
      ? old.future
      : AcademicAttendanceTodayData.fromJson({
          'learner_id': learner,
          'on_date': '2026-02-10',
          'entries': [
            {
              'register_id': 5,
              'label': 'Selected child',
              'kind': 'homeroom',
              'state': 'absent',
              'source': 'tap',
              'amended_at': null,
            },
          ],
        });
}
