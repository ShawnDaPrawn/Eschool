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
    'teacher saves private content with explicit review before publication',
    (tester) async {
      var published = false;
      final api = await startSmokeApp(tester, {
        ...teacherRoutes(),
        'GET v2/me/context': ok({
          ...academicContextFixture(),
          'user': {
            'id': 12,
            'first_name': 'Teacher',
            'last_name': 'Demo',
            'name': 'Teacher Demo',
            'email': teacherEmail,
            'image': null,
            'roles': ['Teacher'],
          },
          'feature_flags': {'academic_content': true},
        }),
        'GET v2/academics/content/configuration': ok(
          contentConfigurationFixture(),
        ),
        'GET v2/academics/content/items': ok(contentItemsFixture()),
        'GET v2/academics/content/items/10': (RequestOptions request) =>
            ok(contentItemFixture(state: published ? 'published' : 'draft')),
        'GET v2/academics/content/versions/11/preview': ok(
          contentPreviewFixture(),
        ),
        'PUT v2/academics/content/versions/11': (RequestOptions request) {
          expect(request.data['markdown'], '# Reviewed fractions');
          expect(request.data['operation_id'], isNotEmpty);
          return ok({
            'id': 10,
            'version_id': 11,
            'item_revision': 2,
            'version_revision': 2,
          });
        },
        'POST v2/academics/content/versions/11/publish':
            (RequestOptions request) {
              expect(request.data['reviewed'], true);
              expect(request.data['fingerprint'], 'a' * 64);
              published = true;
              return ok({
                'id': 10,
                'version_id': 11,
                'item_revision': 2,
                'version_revision': 2,
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
      await tapWhenReady(tester, find.text('Learning content'));
      await pumpUntilFound(tester, find.byType(ContentHubScreen));
      await tapWhenReady(tester, find.text('Fractions'));
      await pumpUntilFound(tester, find.byType(ContentEditorScreen));
      final field = find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == 'Markdown draft',
      );
      await tester.ensureVisible(field);
      await tester.enterText(field, '# Reviewed fractions');
      await tester.pump();
      await tapWhenReady(tester, find.text('Save reviewed draft'));
      await pumpUntilFound(tester, find.text('Content change saved'));
      await tapWhenReady(tester, find.text('Preview publication'));
      await pumpUntilFound(tester, find.text('Review content publication'));
      expect(published, false);
      await tapWhenReady(
        tester,
        find.text(
          'I reviewed the text, topic tags and published teaching groups.',
        ),
      );
      await tapWhenReady(tester, find.text('Publish reviewed content'));
      await pumpUntilFound(tester, find.text('Version 1 · Published'));
      expect(published, true);
      final requests = api.requests
          .where((r) => r.uri.path.contains('/academics/content/'))
          .toList();
      expect(requests.every((r) => r.headers['X-School-Id'] == '1'), true);
      expect(tester.takeException(), isNull);
    },
  );
}
