import 'package:dio/dio.dart';
import 'package:eschool_academics/src/risk/risk_repository.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';
import 'risk_test.dart' show riskMeta;

void main() {
  ApiRiskRepository repo(
    FakeApi api, {
    bool Function()? active,
    String Function()? token,
    bool bearer = true,
  }) => ApiRiskRepository(
    ApiClient(
      authToken: token ?? () => 'Synthetic token',
      sendsBearerToken: bearer,
      onUnauthorized: () {},
      createDio: () => Dio()..httpClientAdapter = api,
    ),
    schoolId: 1,
    readerId: 12,
    contextActive: active ?? () => true,
  );
  test(
    'private requests bind school/cookie account and suppress record URL logs',
    () async {
      final logged = <String>[];
      final old = debugPrint;
      debugPrint = (String? m, {int? wrapWidth}) {
        if (m != null) logged.add(m);
      };
      addTearDown(() => debugPrint = old);
      final api = FakeApi(
        routes: {
          'GET v2/growth/risk/interventions/9': (RequestOptions r) {
            expect(r.headers['X-School-Id'], '1');
            expect(r.headers['X-Account-Id'], '12');
            expect(r.headers.containsKey('Authorization'), isFalse);
            return {
              'error': false,
              'data': {
                ...riskMeta(),
                'action': 'Private body',
                'outcome': null,
              },
            };
          },
        },
      );
      expect((await repo(api, bearer: false).detail(9)).action, 'Private body');
      expect(
        logged.where(
          (l) => l.contains('/interventions/9') || l.contains('Private body'),
        ),
        isEmpty,
      );
    },
  );
  test(
    'another record or owner cannot substitute a private response',
    () async {
      for (final swap in [
        {'id': 99},
        {'owner_id': 99},
      ]) {
        final api = FakeApi(
          routes: {
            'GET v2/growth/risk/interventions/9': {
              'error': false,
              'data': {
                ...riskMeta(),
                ...swap,
                'action': 'Wrong body',
                'outcome': null,
              },
            },
          },
        );
        await expectLater(repo(api).detail(9), throwsA(isA<ApiException>()));
      }
    },
  );
  test(
    'dispatched reply is refused when route scope or bearer identity changes',
    () async {
      var active = true;
      var token = 'First token';
      final api = FakeApi(
        routes: {
          'GET v2/growth/risk/interventions/9': (RequestOptions r) {
            active = false;
            token = 'Another token';
            return {
              'error': false,
              'data': {...riskMeta(), 'action': 'Late body', 'outcome': null},
            };
          },
        },
      );
      await expectLater(
        repo(api, active: () => active, token: () => token).detail(9),
        throwsA(isA<ApiException>()),
      );
    },
  );
  test(
    'queue cannot claim body access for another owner or another reporting year',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/growth/risk/interventions': {
            'error': false,
            'data': {
              'session_year_id': 7,
              'page': 1,
              'last_page': 1,
              'interventions': [
                {
                  'id': 9,
                  'learner_id': 11,
                  'owner_id': 99,
                  'grade_level_id': 5,
                  'review_on': '2026-02-20',
                  'state': 'active',
                  'revision': 1,
                  'can_read_body': true,
                },
              ],
            },
          },
        },
      );
      await expectLater(repo(api).queue(7), throwsA(isA<ApiException>()));
    },
  );
}
