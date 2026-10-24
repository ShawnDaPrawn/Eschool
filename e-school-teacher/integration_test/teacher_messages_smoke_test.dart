import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/deepLinks.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/ui/screens/home/homeScreen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'teacher submits a draft and grade head reviews another authors message',
    (tester) async {
      var state = 'draft',
          revision = 1,
          head = false,
          reviewState = 'pending',
          reviewRevision = 1;
      var title = 'Private weekly update',
          body = '# Family information\n\nPrivate school text.';
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
          'feature_flags': {
            'module_switches': true,
            'communication_messages': true,
          },
          'modules': [
            for (final key in [
              'school',
              'people',
              'academics',
              'communication',
              'web_teacher',
            ])
              {'key': key, 'read_only': false},
          ],
        }),
        'GET v2/communication/configuration': (_) => ok({
          ...messageConfigurationFixture(),
          'rules': {
            'allow_school': false,
            'requires_approval': !head,
            'grade_head_exempt': head,
            'head_grades': head ? [8] : [],
            'versions': [
              [4, 0],
            ],
          },
        }),
        'GET v2/communication/messages': ok({
          'page': 1,
          'last_page': 1,
          'items': [],
        }),
        'GET v2/communication/drafts': (_) => ok({
          'page': 1,
          'last_page': 1,
          'items': [
            {
              ...messageSummaryFixture(
                state: state,
                revision: revision,
                title: title,
              ),
              'is_author': true,
              'can_review': false,
            },
          ],
        }),
        'GET v2/communication/drafts/11': (_) => ok(
          messageEditorFixture(
            state: state,
            revision: revision,
            title: title,
            body: body,
          ),
        ),
        'POST v2/communication/drafts': (RequestOptions request) {
          expect(request.data['operation_id'], isNotEmpty);
          expect(request.data['audience']['target_id'], 9);
          title = request.data['title'] as String;
          body = request.data['markdown'] as String;
          revision++;
          return ok({'id': 11, 'revision': revision, 'state': 'draft'});
        },
        'GET v2/communication/drafts/11/preview': (_) =>
            ok(messagePreviewFixture(revision: revision)),
        'POST v2/communication/drafts/11/submit': (RequestOptions request) {
          expect(request.data['fingerprint'], 'a' * 64);
          state = 'pending';
          revision++;
          return ok({'id': 11, 'revision': revision, 'state': state});
        },
        'GET v2/communication/drafts/22': (_) => ok({
          ...messageEditorFixture(
            state: reviewState,
            revision: reviewRevision,
            author: false,
            reviewer: true,
            title: 'Another teachers message',
          ),
          'id': 22,
        }),
        'GET v2/communication/drafts/22/preview': ok({
          ...messagePreviewFixture(revision: reviewRevision),
          'id': 22,
        }),
        'POST v2/communication/drafts/22/decision': (RequestOptions request) {
          expect(head, isTrue);
          expect(request.data['decision'], 'approve');
          expect(
            request.data['reason'],
            'Reviewed the other authors private message.',
          );
          reviewState = 'approved';
          reviewRevision++;
          return ok({
            'id': 22,
            'revision': reviewRevision,
            'state': reviewState,
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
      await tapWhenReady(tester, find.text('School messages'));
      await pumpUntilFound(tester, find.byType(SchoolMessagesScreen));
      await tapWhenReady(tester, find.text('New school message'));
      final subject = find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == 'Message subject',
      );
      final markdown = find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == 'Message Markdown',
      );
      await pumpUntilFound(tester, subject);
      await tester.enterText(subject, 'A private family reminder');
      await tester.enterText(markdown, '# Reminder\n\nBring your workbook.');
      await tester.pump();
      await tapWhenReady(tester, find.text('Save private message'));
      await pumpUntilFound(tester, find.text('A private family reminder'));
      await tapWhenReady(tester, find.text('Preview saved message'));
      await pumpUntilFound(tester, find.text('Audience and delivery preview'));
      await tapWhenReady(tester, find.text('Submit previewed message'));
      await pumpUntilFound(tester, find.textContaining('Pending'));
      expect(state, 'pending');
      // A current appointment now allows this actor to review a different author's draft.
      head = true;
      openDeepLink(
        const DeepLinkTarget(
          Routes.schoolMessages,
          arguments: MessageLinkTarget(22),
        ),
        signedIn: true,
      );
      await pumpUntilFound(tester, find.text('Another teachers message'));
      expect(markdown, findsNothing);
      await tapWhenReady(tester, find.text('Preview saved message'));
      await tapWhenReady(tester, find.text('Approve message'));
      final reason = find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == 'Review reason',
      );
      await pumpUntilFound(tester, reason);
      await tester.enterText(
        reason,
        'Reviewed the other authors private message.',
      );
      await tapWhenReady(tester, find.text('Continue'));
      await pumpUntilFound(tester, find.textContaining('Approved'));
      expect(reviewState, 'approved');
      expect(
        api.requests
            .where((r) => r.uri.path.contains('/communication/'))
            .every((r) => r.headers['X-School-Id'] == '1'),
        isTrue,
      );
      expect(tester.takeException(), isNull);
    },
  );
}
