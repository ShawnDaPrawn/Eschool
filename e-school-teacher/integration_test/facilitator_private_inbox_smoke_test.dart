import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/app/staffBuildIdentity.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:integration_test/integration_test.dart';

import 'support/wellbeing_fixture.dart';
import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;

class PrivateInboxFixture extends ClinicalStaffFixture {
  bool inboxGranted = true;
  bool marked = false;
  @override
  Map<String, dynamic> profile() {
    final value = super.profile();
    (value['features'] as Map<String, dynamic>)['private_notices'] =
        inboxGranted;
    return value;
  }

  PrivateInboxFixture() {
    api.routes['GET v2/staff/private-notices'] = (RequestOptions _) => result({
      'school_id': 1,
      'reader_id': 12,
      'writable': true,
      'items': [
        {
          'id': 71,
          'feature': 'wellbeing',
          'title': 'Private school update',
          'message':
              'Open your private school area to review an available update.',
          'writable': true,
          'read_at': marked ? '2026-02-10T09:00:00Z' : null,
        },
      ],
      'page': 1,
      'last_page': 1,
      'unread_count': marked ? 0 : 1,
    });
    api.routes['POST v2/staff/private-notices/71/read'] = (RequestOptions _) {
      marked = true;
      return result({
        'id': 71,
        'school_id': 1,
        'reader_id': 12,
        'read_at': '2026-02-10T09:00:00Z',
      });
    };
  }
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'dedicated staff inbox opens only its current permitted private area and erases withdrawn access',
    (tester) async {
      expect(StaffBuildIdentity.current.canStartFacilitator, isTrue);
      final fixture = PrivateInboxFixture();
      final repo = FacilitatorRepository(
        mode: AuthMode.bearer,
        createDio: () => Dio()..httpClientAdapter = fixture.api,
      );
      await initializeApp(facilitatorRepository: repo);
      await pumpUntilFound(tester, find.text('Facilitator sign-in'));
      Finder field(String label) => find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == label,
      );
      await tester.enterText(field('School code'), 'jacaranda');
      await tester.enterText(field('Email or handle'), 'coach@example.test');
      await tester.enterText(field('Password'), 'SyntheticStaff2026');
      await tapWhenReady(tester, find.text('Sign in'));
      await pumpUntilFound(tester, find.text('Your school activities'));
      await tapWhenReady(tester, find.text('Private staff updates'));
      await pumpUntilFound(tester, find.text('Unread private updates: 1'));
      expect(find.text('Private clinical browser baseline'), findsNothing);
      await tapWhenReady(
        tester,
        find.byKey(const ValueKey('private-staff-notice-71')),
      );
      await pumpUntilFound(tester, find.text('Synthetic Case Learner'));
      expect(fixture.marked, isTrue);
      await tapWhenReady(tester, find.text('Synthetic Case Learner'));
      await pumpUntilFound(
        tester,
        find.text('Private clinical browser baseline'),
      );
      expect(
        fixture.api.requests
            .where((r) => r.path.contains('/staff/private-notices'))
            .every(
              (r) =>
                  r.headers['X-School-Id'] == '1' &&
                  r.headers['X-Account-Id'] == '12',
            ),
        isTrue,
      );
      fixture.inboxGranted = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Private clinical browser baseline'), findsNothing);
      expect(find.text('Private staff updates'), findsNothing);
      expect(Hive.isBoxOpen('authBox'), isFalse);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
      repo.dispose();
    },
  );
}
