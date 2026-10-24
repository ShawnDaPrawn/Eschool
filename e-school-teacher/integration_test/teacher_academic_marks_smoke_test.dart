import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/screens/home/homeScreen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'package:eschool_academics/testing.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'teacher opens group and term, captures questions and previews release',
    (tester) async {
      final matrix = markMatrix().toJson();
      matrix['capabilities'] = {
        'capture': true,
        'correct': false,
        'moderate': false,
        'release': true,
      };
      final api = await startSmokeApp(tester, {
        ...teacherRoutes(),
        'GET v2/me/context': ok(academicContextFixture()),
        'GET v2/academics/teaching-groups': ok({
          'groups': [
            {
              'id': 1,
              'name': 'Maths 7A',
              'year_name': '2026',
              'subject_id': 1,
              'grade_level_id': 8,
              'session_year_id': 1,
              'class_section_id': null,
              'active': true,
              'revision': 1,
            },
          ],
          'current_page': 1,
          'last_page': 1,
          'total': 1,
        }),
        'GET v2/academics/reference': ok({
          'grades': [
            {'id': 8, 'sort_order': 7},
          ],
        }),
        'GET v2/academics/terms': ok({
          'terms': [
            {
              'id': 1,
              'session_year_id': 1,
              'name': 'Term 1',
              'starts_on': '2026-01-01',
              'ends_on': '2026-03-31',
              'grade_from': 0,
              'grade_to': 12,
              'marks_lock_on': null,
              'reports_on': null,
              'current': true,
              'needs_review': false,
            },
          ],
        }),
        'GET v2/academics/marks/groups/1': ok(matrix),
        'POST v2/academics/marks/groups/1/tasks/10/preview': ok({
          'previews': [
            {'learner_id': 11, 'result': result(7505)},
          ],
        }),
        'POST v2/academics/marks/groups/1/tasks/10/capture':
            (RequestOptions request) {
              final rows = (request.data as Map)['rows'] as List;
              expect((rows.single as Map)['question_marks'][0]['mark'], '5.01');
              expect((rows.single as Map)['expected_revision'], 0);
              return ok({'marks': <Object>[], 'recompute_pending': true});
            },
        'POST v2/academics/marks/groups/1/release-preview': ok(
          releasePreviewFixture(),
        ),
        'POST v2/academics/marks/groups/1/release': (RequestOptions request) {
          expect((request.data as Map)['fingerprint'], 'preview-fingerprint');
          return ok(releaseFixture());
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
      await tapWhenReady(tester, find.text('Assessment marks'));
      await pumpUntilFound(tester, find.byType(MarkGroupsScreen));
      await tapWhenReady(tester, find.text('Maths 7A'));
      await tapWhenReady(tester, find.text('Term 1'));
      await pumpUntilFound(tester, find.byType(MarkCaptureScreen));
      await pumpUntilFound(tester, find.byType(TextField));
      await tester.enterText(find.byType(TextField).first, '5.01');
      await tapWhenReady(
        tester,
        find.widgetWithText(FilledButton, 'Save marks'),
      );
      await pumpUntilFound(tester, find.text('Marks saved'));
      await tapWhenReady(tester, find.byType(Checkbox).first);
      await tapWhenReady(tester, find.text('Release selected results'));
      expect(
        api.requested('POST v2/academics/marks/groups/1/release'),
        isFalse,
      );
      await tapWhenReady(tester, find.text('Preview release'));
      await pumpUntilFound(tester, find.text('75.05%'));
      await tapWhenReady(
        tester,
        find.widgetWithText(FilledButton, 'Release selected results'),
      );
      await pumpUntilFound(tester, find.text('Results released'));
      expect(api.requested('POST v2/academics/marks/groups/1/release'), isTrue);
      for (final request in api.requests.where(
        (r) => r.uri.path.contains('/academics/'),
      )) {
        expect(request.headers['X-School-Id'], '1');
      }
      expect(tester.takeException(), isNull);
    },
  );
}
