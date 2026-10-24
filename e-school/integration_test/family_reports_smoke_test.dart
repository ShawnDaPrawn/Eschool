import 'dart:convert';

import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'parent opens and renders only the selected child released report',
    (tester) async {
      final api = await startSmokeApp(tester, {
        ...parentOfTwoRoutes(),
        'GET v2/me/context': ok({
          ...academicContextFixture(role: 'Parent'),
          'feature_flags': {'academic_reports': true},
          'children': [
            for (final (id, name) in [(11, 'Thandi'), (12, 'Sipho')])
              {
                'id': id,
                'user_id': id,
                'first_name': name,
                'last_name': 'Mokoena',
                'name': '$name Mokoena',
                'image': null,
                'school': {
                  'id': 1,
                  'name': 'Smoke Test School',
                  'short_name': null,
                  'crest_url': null,
                },
              },
          ],
        }),
        'GET v2/academics/reports/learners/11': ok({
          ...reportFamilyFixture(AppConfig.apiV2Url),
          'last_page': 1,
        }),
        'GET v2/academics/reports/cards/9/access': ok({
          'id': 9,
          'learner_id': 11,
          'download_url': reportDownloadFixture(AppConfig.apiV2Url),
        }),
        'GET v2/academics/reports/cards/9/download': FakeBinaryResponse(
          base64Decode(reportSmokePdfBase64),
        ),
        'POST v2/academics/reports/cards/9/read': ok({
          'id': 9,
          'read_at': '2026-02-10T10:00:00Z',
        }),
      });
      await tapWhenReady(tester, find.text('Login as Parent'));
      await pumpUntilFound(tester, find.text('Email or handle'));
      await enterField(tester, 0, parentEmail);
      await enterField(tester, 1, password);
      await tapWhenReady(
        tester,
        find.widgetWithText(CustomRoundedButton, 'Sign in'),
      );
      await pumpUntilFound(tester, find.byType(ParentHomeScreen));
      await tapWhenReady(tester, find.text('Report cards'));
      await pumpUntilFound(tester, find.byType(FamilyReportsScreen));
      await tapWhenReady(tester, find.text('2026 · Annual report'));
      await pumpUntilFound(tester, find.byType(ReportPdfScreen));
      await pumpUntilFound(tester, find.text('Report preview ready'));
      final requests = api.requests
          .where((r) => r.uri.path.contains('/academics/reports/'))
          .toList();
      expect(requests.length, greaterThanOrEqualTo(4));
      for (final request in requests) {
        expect(request.headers['X-School-Id'], '1');
        expect(request.uri.path.contains('/learners/12'), isFalse);
      }
      expect(api.requested('GET v2/academics/reports/configuration'), isFalse);
      expect(api.requested('GET v2/academics/reports/batches'), isFalse);
      expect(api.requested('GET v2/academics/reports/cards/9/access'), isTrue);
      expect(tester.takeException(), isNull);
    },
  );
}
