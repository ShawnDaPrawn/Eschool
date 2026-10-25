import 'package:dio/dio.dart';
import 'package:eschool_academics/testing.dart';
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

class _Fixture extends ClinicalStaffFixture {
  bool permitted = true;
  @override
  Map<String, dynamic> profile() {
    final value = super.profile();
    value['roles'] = ['Facilitator'];
    value['features'] = {
      'private_notices': true,
      'consultations': permitted,
      'groups': false,
      'messages': false,
      'counselling': false,
    };
    return value;
  }
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'dedicated facilitator participates without classic auth and removes withdrawn route',
    (tester) async {
      expect(StaffBuildIdentity.current.canStartFacilitator, isTrue);
      final fixture = _Fixture();
      final votes = ConsultationSmokeFixture(accountId: 12);
      fixture.api.routes.addAll(votes.routes);
      fixture.api.routes['GET v2/staff/private-notices'] = (_) =>
          fixture.result({
            'school_id': 1,
            'reader_id': 12,
            'writable': true,
            'page': 1,
            'last_page': 1,
            'unread_count': 1,
            'items': [
              {
                'id': 81,
                'feature': 'consultations',
                'title': 'School consultation available',
                'message':
                    'Open school consultations to review an available ballot.',
                'writable': true,
                'read_at': null,
              },
            ],
          });
      fixture.api.routes['POST v2/staff/private-notices/81/read'] = (_) =>
          fixture.result({
            'id': 81,
            'school_id': 1,
            'reader_id': 12,
            'read_at': '2026-10-25T10:00:00Z',
          });
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
      await tapWhenReady(
        tester,
        find.byKey(const ValueKey('private-staff-notice-81')),
      );
      await pumpUntilFound(tester, find.text('Synthetic school consultation'));
      await tapWhenReady(tester, find.text('Synthetic school consultation'));
      await pumpUntilFound(
        tester,
        find.text('Synthetic private consultation instructions.'),
      );
      await tapWhenReady(
        tester,
        find.byKey(const ValueKey('consultation-option-11')),
      );
      final answer = find.byKey(const ValueKey('consultation-answer-4'));
      await tester.ensureVisible(answer);
      await tester.pumpAndSettle();
      await tester.enterText(answer, 'Synthetic private facilitator answer');
      await tapWhenReady(tester, find.text('Record my response'));
      await pumpUntilFound(
        tester,
        find.text('Your response is recorded. You can participate only once.'),
      );
      expect(votes.votes, 1);
      expect(votes.lastVote!.keys, ['answers']);
      fixture.permitted = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(
        find.text('Synthetic private consultation instructions.'),
        findsNothing,
      );
      expect(find.text('Polls, surveys and elections'), findsNothing);
      expect(Hive.isBoxOpen('authBox'), isFalse);
      final requests = fixture.api.requests.where(
        (r) => r.path.contains('/consultations'),
      );
      expect(requests, isNotEmpty);
      expect(
        requests.every(
          (r) =>
              r.headers['X-Account-Id'] == '12' &&
              r.headers['X-School-Id'] == '1',
        ),
        isTrue,
      );
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
      repo.dispose();
    },
  );
}
