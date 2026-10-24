import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/screens/home/homeScreen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'teacher opens reports and saves only an allocated subject comment',
    (tester) async {
      var saved = false;
      final api = await startSmokeApp(tester, {
        ...teacherRoutes(),
        'GET v2/me/context': ok({
          ...academicContextFixture(),
          'feature_flags': {'academic_reports': true},
        }),
        'GET v2/academics/reports/configuration': ok(
          reportConfigurationFixture(leader: false, canDesign: false),
        ),
        'GET v2/academics/reports/comments': (RequestOptions request) => ok({
          'session_year_id': 1,
          'page': 1,
          'last_page': 1,
          'rows': [
            reportCommentFixture(
              text: saved ? 'Steady progress in mathematics.' : '',
              revision: saved ? 1 : 0,
              batchRevision: saved ? 2 : 1,
            ),
          ],
        }),
        'GET v2/academics/reports/batches': ok({
          'session_year_id': 1,
          'page': 1,
          'last_page': 1,
          'batches': <Object>[],
        }),
        'POST v2/academics/reports/cards/9/comment': (RequestOptions request) {
          expect(request.data['subject_id'], 2);
          expect(request.data['revision'], 0);
          expect(request.headers['Idempotency-Key'], isNotEmpty);
          saved = true;
          return ok({
            'id': 1,
            'revision': 1,
            'batch_revision': 2,
            'fingerprint': 'a' * 64,
            'text': request.data['text'],
          });
        },
      });
      await pumpUntilFound(tester, find.text('Email or handle'));
      await enterField(tester, 0, teacherEmail);
      await enterField(tester, 1, password);
      await tapWhenReady(
        tester,
        find.widgetWithText(CustomRoundedButton, 'Sign in'),
      );
      await pumpUntilFound(tester, find.byType(HomeScreen));
      await tapWhenReady(tester, find.text('Report cards'));
      await pumpUntilFound(tester, find.byType(ReportHubScreen));
      expect(find.text('Prepare report cards'), findsNothing);
      await tapWhenReady(tester, find.text('Thandi Mokoena'));
      await pumpUntilFound(tester, find.byType(TextField));
      await tester.enterText(
        find.byType(TextField).first,
        'Steady progress in mathematics.',
      );
      await tester.pump();
      await tapWhenReady(
        tester,
        find.widgetWithText(FilledButton, 'Save comment'),
      );
      await pumpUntilFound(
        tester,
        find.text('Comment matches the saved version.'),
      );
      expect(saved, isTrue);
      final writes = api.requests
          .where((r) => r.uri.path.endsWith('/cards/9/comment'))
          .toList();
      expect(writes, hasLength(1));
      expect(writes.single.headers['X-School-Id'], '1');
      expect(api.requested('POST v2/academics/reports/prepare'), isFalse);
      expect(tester.takeException(), isNull);
    },
  );
}
