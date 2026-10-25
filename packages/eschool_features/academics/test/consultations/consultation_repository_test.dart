import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';

Map<String, dynamic> consultationAccessJson({
  int school = 1,
  int account = 7,
}) => {
  'school_id': school,
  'account_id': account,
  'session_year_id': 2,
  'today': '2026-02-10',
  'timezone': 'Africa/Johannesburg',
  'writable': true,
  'can_manage': false,
};
ApiConsultationRepository repo(
  FakeApi api, {
  bool cookie = true,
  bool Function()? active,
  String Function()? token,
}) => ApiConsultationRepository(
  ApiClient(
    authToken: token ?? () => 'synthetic-consultation-token',
    onUnauthorized: () {},
    sendsBearerToken: !cookie,
    createDio: () => Dio()..httpClientAdapter = api,
  ),
  schoolId: 1,
  accountId: 7,
  contextActive: active ?? () => true,
);
void main() {
  test(
    'cookie requests bind exact account and school and suppress private debug output',
    () async {
      final logs = <String>[], before = debugPrint;
      debugPrint = (String? value, {int? wrapWidth}) {
        if (value != null) logs.add(value);
      };
      addTearDown(() => debugPrint = before);
      final api = FakeApi(
        routes: {
          'GET v2/consultations/access': {
            'error': false,
            'data': consultationAccessJson(),
          },
        },
      );
      expect((await repo(api).access()).accountId, 7);
      expect(api.requests.single.headers['X-School-Id'], '1');
      expect(api.requests.single.headers['X-Account-Id'], '7');
      expect(api.requests.single.headers.containsKey('Authorization'), isFalse);
      expect(logs.where((line) => line.contains('consultations')), isEmpty);
    },
  );
  test('replacement projection and changed bearer token are refused', () async {
    for (final data in [
      consultationAccessJson(account: 99),
      consultationAccessJson(school: 99),
    ]) {
      final api = FakeApi(
        routes: {
          'GET v2/consultations/access': {'error': false, 'data': data},
        },
      );
      await expectLater(repo(api).access(), throwsA(isA<ApiException>()));
    }
    var token = 'first';
    final api = FakeApi(routes: {}),
        r = repo(api, cookie: false, token: () => token);
    token = 'replacement';
    await expectLater(r.access(), throwsA(isA<ApiException>()));
    expect(api.requests, isEmpty);
  });
  test('a late reply from the previous context is discarded', () async {
    var active = true;
    final api = FakeApi(
      routes: {
        'GET v2/consultations/access': (RequestOptions _) {
          active = false;
          return {'error': false, 'data': consultationAccessJson()};
        },
      },
    );
    await expectLater(
      repo(api, active: () => active).access(),
      throwsA(isA<ApiException>()),
    );
  });
  test(
    'anonymous vote posts answers only without actor key or idempotency receipt',
    () async {
      final api = FakeApi(
        routes: {
          'POST v2/consultations/9/vote': (RequestOptions r) {
            expect(r.headers.containsKey('Idempotency-Key'), isFalse);
            expect(r.queryParameters, isEmpty);
            expect(r.data, {
              'answers': [
                {'question_id': 3, 'text': 'Synthetic private answer'},
              ],
            });
            return {
              'error': false,
              'data': {'participated': true},
            };
          },
        },
      );
      expect(
        (await repo(api).vote(9, [
          ConsultationVoteAnswer(
            questionId: 3,
            text: 'Synthetic private answer',
          ),
        ])).participated,
        isTrue,
      );
    },
  );
  test(
    'a private page cannot substitute the requested page identifier',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/consultations': {
            'error': false,
            'data': {'items': [], 'page': 2, 'last_page': 2},
          },
        },
      );
      await expectLater(repo(api).listing(), throwsA(isA<ApiException>()));
    },
  );
}
