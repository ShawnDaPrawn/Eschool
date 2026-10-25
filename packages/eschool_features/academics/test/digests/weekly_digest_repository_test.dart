import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';

Map<String, dynamic> access({int account = 21}) => {
  'school_id': 1,
  'account_id': account,
  'parent': true,
  'can_manage': false,
  'children': [],
  'language': 'en',
  'preference_revision': 0,
  'languages': ['en'],
  'approved_languages': ['en'],
};
ApiWeeklyDigestRepository repository(
  FakeApi api, {
  bool cookie = false,
  bool Function()? active,
  String Function()? token,
}) => ApiWeeklyDigestRepository(
  ApiClient(
    authToken: token ?? () => 'synthetic',
    onUnauthorized: () {},
    sendsBearerToken: !cookie,
    createDio: () => Dio()..httpClientAdapter = api,
  ),
  schoolId: 1,
  accountId: 21,
  contextActive: active ?? () => true,
);
void main() {
  test(
    'cookie parent reads bind school/account and omit bearer credentials',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/digests/access': {'error': false, 'data': access()},
        },
      );
      expect((await repository(api, cookie: true).access()).accountId, 21);
      expect(api.requests.single.headers['X-School-Id'], '1');
      expect(api.requests.single.headers['X-Account-Id'], '21');
      expect(api.requests.single.headers.containsKey('Authorization'), isFalse);
    },
  );
  test(
    'changed credentials or role state refuse before making a private request',
    () async {
      var token = 'first';
      final api = FakeApi(routes: {}),
          repo = repository(api, token: () => token);
      token = 'second';
      await expectLater(repo.access(), throwsA(isA<ApiException>()));
      await expectLater(
        repository(api, active: () => false).weeks(11),
        throwsA(isA<ApiException>()),
      );
      expect(api.requests, isEmpty);
    },
  );
  test('an account-substituted response is refused', () async {
    final api = FakeApi(
      routes: {
        'GET v2/digests/access': {'error': false, 'data': access(account: 99)},
      },
    );
    await expectLater(repository(api).access(), throwsA(isA<ApiException>()));
  });
  test('late private response is refused after authority withdrawal', () async {
    var active = true;
    final api = FakeApi(
      routes: {
        'GET v2/digests/access': (RequestOptions _) {
          active = false;
          return {'error': false, 'data': access()};
        },
      },
    );
    await expectLater(
      repository(api, active: () => active).access(),
      throwsA(isA<ApiException>()),
    );
  });
}
