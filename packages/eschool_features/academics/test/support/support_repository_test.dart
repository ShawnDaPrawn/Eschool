import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/foundation.dart';

ApiSupportRepository repository(
  FakeApi api, {
  String Function()? token,
  bool Function()? active,
  bool cookie = false,
}) => ApiSupportRepository(
  ApiClient(
    authToken: token ?? () => 'synthetic',
    onUnauthorized: () {},
    createDio: () => Dio()..httpClientAdapter = api,
    sendsBearerToken: !cookie,
  ),
  schoolId: 1,
  accountId: 2,
  contextActive: active ?? () => true,
);
void main() {
  test('support request URLs are not printed by private transport', () async {
    final logged = <String>[];
    final original = debugPrint;
    debugPrint = (String? value, {int? wrapWidth}) {
      if (value != null) logged.add(value);
    };
    addTearDown(() => debugPrint = original);
    final api = FakeApi(
      routes: {
        'GET v2/pastoral/support/access': {
          'error': false,
          'data': {
            'audiences': ['professional'],
            'can_write': true,
            'today': '2026-02-10',
          },
        },
      },
    );
    expect((await repository(api).access()).audiences, ['professional']);
    expect(
      logged.where((line) => line.contains('/pastoral/support/')),
      isEmpty,
    );
  });
  test(
    'cookie calls bind expected account and school without a bearer token',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/pastoral/support/access': {
            'error': false,
            'data': {
              'audiences': ['teacher'],
              'can_write': false,
              'today': '2026-02-10',
            },
          },
        },
      );
      final access = await repository(api, cookie: true).access();
      expect(access.audiences, ['teacher']);
      expect(access.today, '2026-02-10');
      expect(api.requests.single.headers['X-Account-Id'], '2');
      expect(api.requests.single.headers['X-School-Id'], '1');
      expect(api.requests.single.headers.containsKey('Authorization'), false);
    },
  );
  test(
    'account token or active scope changes fail before a private request',
    () async {
      var token = 'before', active = true;
      final api = FakeApi(routes: {}),
          repo = repository(api, token: () => token, active: () => active);
      token = 'after';
      await expectLater(repo.access(), throwsA(isA<ApiException>()));
      token = 'before';
      active = false;
      await expectLater(repo.access(), throwsA(isA<ApiException>()));
      expect(api.requests, isEmpty);
    },
  );
  test(
    'a wrong plan identifier cannot become the displayed private record',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/pastoral/support/plans/10': {
            'error': false,
            'data': {
              'id': 11,
              'learner_id': 3,
              'learner_name': 'Wrong learner',
              'revision': 1,
              'state': 'active',
              'review_due_on': null,
              'started_on': '2026-02-01',
              'sias_stage': 'SNA1',
              'needs': 'Private wrong record',
              'accommodations': 'Private',
              'reviews_has_more': false,
              'disclosures_has_more': false,
              'reviews': [],
              'disclosures': [],
            },
          },
        },
      );
      await expectLater(repository(api).plan(10), throwsA(isA<ApiException>()));
    },
  );
  test(
    'a late private reply is rejected after current access changes',
    () async {
      var active = true;
      final api = FakeApi(
        routes: {
          'GET v2/pastoral/support/access': (RequestOptions _) {
            active = false;
            return {
              'error': false,
              'data': {
                'audiences': ['professional'],
                'can_write': true,
                'today': '2026-02-10',
              },
            };
          },
        },
      );
      await expectLater(
        repository(api, active: () => active).access(),
        throwsA(isA<ApiException>()),
      );
      expect(api.requests, hasLength(1));
    },
  );
  test(
    'selected choices use independent ID filters without inheriting a search page',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/pastoral/support/configuration/principal': {
            'error': false,
            'data': {
              'audience': 'principal',
              'page': 1,
              'last_page': 1,
              'learners': [
                {'id': 3, 'name': 'Learner'},
              ],
              'staff': [
                {'id': 4, 'name': 'Professional'},
              ],
              'staff_last_page': 1,
            },
          },
        },
      );
      await repository(
        api,
      ).configuration('principal', learnerId: 3, staffId: 4);
      expect(api.requests.single.queryParameters, {
        'page': 1,
        'learner_id': 3,
        'user_id': 4,
      });
    },
  );
}
