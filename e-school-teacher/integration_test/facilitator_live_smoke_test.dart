import 'dart:convert';

import 'package:crypto/crypto.dart';
import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:integration_test/integration_test.dart';

import 'support/wellbeing_fixture.dart';
import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;

const lease = '75133b39-0476-4ba0-a627-a152d042687a';
const key = 'qualification-browser-key';
const secret = 'qualification-browser-secret-32-characters';

class _Fixture extends ClinicalStaffFixture {
  bool allowed = true;
  String group = 'Synthetic live hockey';
  @override
  Map<String, dynamic> profile() {
    final p = super.profile();
    p['roles'] = ['Facilitator'];
    p['features'] = {
      'groups': allowed,
      'live_updates': allowed,
      'messages': false,
      'counselling': false,
    };
    return p;
  }

  Map<String, dynamic> groupRow() => {
    'id': 7,
    'school_id': 1,
    'session_year_id': 7,
    'parent_id': null,
    'name': group,
    'discipline': 'Sport',
    'season': '2026',
    'starts_on': '2026-01-01',
    'ends_on': '2026-12-31',
    'gender': 'any',
    'minimum_grade': null,
    'maximum_grade': null,
    'minimum_age': null,
    'maximum_age': null,
    'active': true,
    'revision': 1,
  };
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'dedicated staff uses a real private browser socket for presence and protected group refresh',
    (tester) async {
      final fixture = _Fixture();
      fixture.api.routes.addAll({
        'POST v2/live/session': (_) => fixture.result({
          'lease_id': lease,
          'channel': 'private-ne.school.1.user.12.session.$lease',
          'key': key,
          'host': '127.0.0.1',
          'port': 8088,
          'scheme': 'ws',
          'heartbeat_seconds': 20,
          'expires_in': 45,
        }),
        'POST v2/live/authorize': (RequestOptions request) {
          final body = Map<String, dynamic>.from(request.data as Map);
          final signature = Hmac(sha256, utf8.encode(secret)).convert(
            utf8.encode('${body['socket_id']}:${body['channel_name']}'),
          );
          return fixture.result({'auth': '$key:$signature'});
        },
        'POST v2/live/groups/7/presence': (_) => fixture.result({
          'type': 'group.presence',
          'school_id': 1,
          'group_id': 7,
          'revision': 1,
          'online_count': 2,
        }),
        'DELETE v2/live/groups/7/presence': (_) => fixture.result({
          'type': 'group.presence',
          'school_id': 1,
          'group_id': 7,
          'revision': 2,
          'online_count': 0,
        }),
        'GET v2/activities/groups': (_) => fixture.result({
          'items': [fixture.groupRow()],
          'page': 1,
          'last_page': 1,
          'total': 1,
        }),
        'GET v2/activities/groups/7': (_) => fixture.result({
          'group': fixture.groupRow(),
          'sessions': [],
          'materials': [],
          'can_manage': false,
          'can_capture': false,
        }),
      });
      final repository = FacilitatorRepository(
        mode: AuthMode.bearer,
        createDio: () => Dio()..httpClientAdapter = fixture.api,
      );
      await initializeApp(facilitatorRepository: repository);
      await pumpUntilFound(tester, find.text('Facilitator sign-in'));
      Finder field(String label) => find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == label,
      );
      await tester.enterText(field('School code'), 'jacaranda');
      await tester.enterText(field('Email or handle'), 'coach@example.test');
      await tester.enterText(field('Password'), 'SyntheticStaff2026');
      await tapWhenReady(tester, find.text('Sign in'));
      await pumpUntilFound(tester, find.text('Your school activities'));
      await tapWhenReady(tester, find.text('Activity groups'));
      await pumpUntilFound(tester, find.text('Synthetic live hockey'));
      await tapWhenReady(tester, find.text('Synthetic live hockey'));
      await pumpUntilFound(tester, find.text('Online now: 2'));
      // The PHP/Reverb integration qualifies source/queue authority separately. This
      // loopback publisher qualifies actual browser transport and the rendered app.
      await Dio().get(
        'http://127.0.0.1:8089/?type=presence&revision=3&count=3',
      );
      await pumpUntilFound(tester, find.text('Online now: 3'));
      fixture.group = 'Fresh protected live group';
      await Dio().get('http://127.0.0.1:8089/?type=group&revision=4');
      await pumpUntilFound(tester, find.text('Fresh protected live group'));
      final requests = fixture.api.requests
          .where((r) => r.path.contains('/live/'))
          .toList();
      expect(
        requests.where((r) => r.path.endsWith('/authorize')),
        hasLength(greaterThanOrEqualTo(2)),
      );
      for (final request in requests) {
        expect(request.headers['X-School-Id'], '1');
        expect(request.headers['X-Account-Id'], '12');
      }
      expect(Hive.isBoxOpen('authBox'), isFalse);
      fixture.allowed = false;
      await repository.refresh();
      await tester.pump(const Duration(milliseconds: 500));
      expect(
        find.text('Fresh protected live group', skipOffstage: false),
        findsNothing,
      );
      expect(
        find.textContaining('Online now:', skipOffstage: false),
        findsNothing,
      );
      await tester.pumpWidget(const SizedBox());
      await repository.signOut();
    },
  );
}
