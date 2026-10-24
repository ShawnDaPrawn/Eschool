import 'dart:async';
import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/src/reports/report_comment_screen.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'report_cubit_test.dart' show FakeReportRepository;

Object ok(Object data) => {'error': false, 'data': data};
ApiReportRepository repo(FakeApi api) => ApiReportRepository(
  ApiClient(
    authToken: () => 'synthetic-token',
    onUnauthorized: () {},
    createDio: () => Dio()..httpClientAdapter = api,
  ),
  schoolId: 1,
);
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
      theme: ThemeData(useMaterial3: true),
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
  await tester.pump();
  if (finder.evaluate().isEmpty) {
    await tester.scrollUntilVisible(
      finder,
      200,
      scrollable: find.byType(Scrollable).last,
    );
  }
  await tester.ensureVisible(finder);
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

void main() {
  for (final width in [390.0, 1024.0, 1280.0]) {
    testWidgets('family retained report history pages at $width pixels', (
      tester,
    ) async {
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repository = FakeReportRepository();
      await frame(
        tester,
        FamilyReportsScreen(
          repository: repository,
          learner: 11,
          learnerName: 'Thandi',
        ),
        width: width,
        scale: width == 390 ? 1.5 : 1,
      );
      expect(find.text('2026 · Annual report'), findsOneWidget);
      await tap(tester, find.text('Earlier reports'));
      expect(find.text('2026 · Annual report'), findsNWidgets(2));
      expect(repository.familyRequests, [11, 11]);
      expect(tester.takeException(), isNull);
    });
    testWidgets(
      'staff preview selects exact learners and retains annual null at $width pixels',
      (tester) async {
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final writes = <RequestOptions>[];
        final api = FakeApi(
          routes: {
            'GET v2/academics/reports/configuration': ok(
              reportConfigurationFixture(),
            ),
            'GET v2/academics/reports/comments': ok({
              'session_year_id': 1,
              'page': 1,
              'last_page': 1,
              'rows': <Object>[],
            }),
            'GET v2/academics/reports/batches': ok({
              'session_year_id': 1,
              'page': 1,
              'last_page': 1,
              'batches': <Object>[],
            }),
            'GET v2/academics/reports/roster': ok({
              'session_year_id': 1,
              'term_id': null,
              'class_section_id': 3,
              'grade_level_id': 8,
              'learners': [
                {'id': 11, 'name': 'Thandi Mokoena'},
                {'id': 12, 'name': 'Sipho Mokoena'},
              ],
            }),
            'POST v2/academics/reports/preview': (RequestOptions request) {
              writes.add(request);
              return ok({
                'selection': request.data,
                'fingerprint': 'a' * 64,
                'can_prepare': true,
                'design': reportBatchFixture()['design'],
                'cards': [reportPayloadFixture()],
              });
            },
            'POST v2/academics/reports/prepare': (RequestOptions request) {
              writes.add(request);
              return ok(reportBatchFixture(detail: false));
            },
            'GET v2/academics/reports/batches/8': ok(reportBatchFixture()),
          },
        );
        await frame(
          tester,
          ReportHubScreen(repository: repo(api)),
          width: width,
        );
        await tap(
          tester,
          find.widgetWithText(FilledButton, 'Prepare report cards'),
        );
        await tap(tester, find.text('Thandi Mokoena'));
        await tap(
          tester,
          find.widgetWithText(FilledButton, 'Preview selected reports'),
        );
        await tester.scrollUntilVisible(
          find.text('75.00%'),
          200,
          scrollable: find.byType(Scrollable).last,
        );
        expect(find.text('75.00%'), findsOneWidget);
        expect(writes, hasLength(1));
        expect(writes.single.data['learner_ids'], [11]);
        expect(writes.single.data.containsKey('term_id'), isTrue);
        expect(writes.single.data['term_id'], isNull);
        await tap(
          tester,
          find.widgetWithText(FilledButton, 'Prepare reviewed reports'),
        );
        await tester.pumpAndSettle();
        expect(find.text('Grade-head sign-off'), findsOneWidget);
        expect(writes.last.headers['Idempotency-Key'], isNotEmpty);
        expect(writes.last.data['learner_ids'], [11]);
        expect(tester.takeException(), isNull);
      },
    );
  }
  testWidgets(
    'comment conflict preserves the draft until explicit comparison and save',
    (tester) async {
      var revision = 0;
      final writes = <RequestOptions>[];
      final api = FakeApi(
        routes: {
          'POST v2/academics/reports/cards/9/comment':
              (RequestOptions request) {
                writes.add(request);
                if (revision == 0) {
                  revision = 1;
                  return const FakeResponse({
                    'error': true,
                    'message': 'The comment changed',
                  }, status: 409);
                }
                return ok({
                  'id': 1,
                  'revision': 2,
                  'batch_revision': 3,
                  'fingerprint': 'a' * 64,
                  'text': request.data['text'],
                });
              },
          'GET v2/academics/reports/comments': ok({
            'session_year_id': 1,
            'page': 1,
            'last_page': 1,
            'rows': [
              reportCommentFixture(
                text: 'Latest saved comment',
                revision: 1,
                batchRevision: 2,
              ),
            ],
          }),
        },
      );
      await frame(
        tester,
        ReportCommentScreen(
          repository: repo(api),
          row: AcademicReportCommentRow.fromJson(reportCommentFixture()),
          year: 1,
        ),
      );
      await tester.enterText(find.byType(TextField).first, 'My draft survives');
      await tap(tester, find.widgetWithText(FilledButton, 'Save comment'));
      expect(find.text('My draft survives'), findsOneWidget);
      expect(find.text('The comment changed'), findsOneWidget);
      expect(
        tester
            .widget<FilledButton>(
              find.widgetWithText(FilledButton, 'Save comment'),
            )
            .onPressed,
        isNull,
      );
      await tap(tester, find.text('Compare with saved'));
      expect(find.text('Latest saved comment'), findsOneWidget);
      await tap(tester, find.text('Keep my draft'));
      await tap(tester, find.widgetWithText(FilledButton, 'Save comment'));
      expect(writes, hasLength(2));
      expect(writes.last.data['revision'], 1);
      expect(writes.last.data['batch_revision'], 2);
      expect(writes.last.data['text'], 'My draft survives');
      expect(
        writes.last.headers['Idempotency-Key'],
        isNot(writes.first.headers['Idempotency-Key']),
      );
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'replacing the selected child suppresses a late previous history response',
    (tester) async {
      final pending = Completer<AcademicReportFamilyData>();
      final repository = FakeReportRepository();
      repository.list = (learner, page) async => learner == 11
          ? await pending.future
          : AcademicReportFamilyData.fromJson({
              ...reportFamilyFixture(AppConfig.apiV2Url, learner: 12),
              'reports': <Object>[],
            });
      Widget app(int learner) => MaterialApp(
        home: FamilyReportsScreen(
          repository: repository,
          learner: learner,
          learnerName: learner == 11 ? 'Thandi' : 'Sipho',
        ),
      );
      await tester.pumpWidget(app(11));
      await tester.pump();
      await tester.pumpWidget(app(12));
      await tester.pumpAndSettle();
      pending.complete(
        AcademicReportFamilyData.fromJson(
          reportFamilyFixture(AppConfig.apiV2Url),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Sipho · Report cards'), findsOneWidget);
      expect(find.text('2026 · Annual report'), findsNothing);
      expect(repository.familyRequests, [11, 12]);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets('unsaved comment back navigation requires a deliberate discard', (
    tester,
  ) async {
    await frame(
      tester,
      Builder(
        builder: (context) => TextButton(
          onPressed: () => Navigator.of(context).push(
            MaterialPageRoute<void>(
              builder: (_) => ReportCommentScreen(
                repository: FakeReportRepository(),
                row: AcademicReportCommentRow.fromJson(reportCommentFixture()),
                year: 1,
              ),
            ),
          ),
          child: const Text('Open comment'),
        ),
      ),
    );
    await tap(tester, find.text('Open comment'));
    await tester.enterText(find.byType(TextField).first, 'Unsaved');
    await tester.pump();
    await tester.pageBack();
    await tester.pumpAndSettle();
    expect(find.text('Keep editing this comment?'), findsOneWidget);
    await tap(tester, find.text('Keep editing'));
    expect(find.text('Unsaved'), findsOneWidget);
    await tester.pump();
    await tester.pageBack();
    await tester.pumpAndSettle();
    await tap(tester, find.text('Discard and leave'));
    expect(find.text('Open comment'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}
