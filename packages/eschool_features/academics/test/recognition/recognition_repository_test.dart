import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';

Map<String, dynamic> access({int account = 12, int school = 1}) => {
  'module': 'house_points',
  'school_id': school,
  'account_id': account,
  'session_year_id': 7,
  'today': '2026-02-10',
  'family': true,
  'can_write': false,
  'manager': false,
  'grade_ids': [],
};
ApiRecognitionRepository repo(
  FakeApi api, {
  bool cookie = false,
  bool Function()? active,
  String Function()? token,
}) => ApiRecognitionRepository(
  ApiClient(
    authToken: token ?? () => 'synthetic',
    onUnauthorized: () {},
    sendsBearerToken: !cookie,
    createDio: () => Dio()..httpClientAdapter = api,
  ),
  schoolId: 1,
  accountId: 12,
  contextActive: active ?? () => true,
);
void main() {
  test(
    'cookie reads bind account/school without logging private recognition URLs',
    () async {
      final logged = <String>[], before = debugPrint;
      debugPrint = (String? message, {int? wrapWidth}) {
        if (message != null) logged.add(message);
      };
      addTearDown(() => debugPrint = before);
      final api = FakeApi(
        routes: {
          'GET v2/recognition/house_points/access': {
            'error': false,
            'data': access(),
          },
        },
      );
      expect(
        (await repo(api, cookie: true).access('house_points')).accountId,
        12,
      );
      expect(api.requests.single.headers['X-School-Id'], '1');
      expect(api.requests.single.headers['X-Account-Id'], '12');
      expect(api.requests.single.headers.containsKey('Authorization'), isFalse);
      expect(logged.where((s) => s.contains('/recognition/')), isEmpty);
    },
  );
  test('changed token refuses before any private request', () async {
    var token = 'first';
    final api = FakeApi(routes: {}), r = repo(api, token: () => token);
    token = 'second';
    await expectLater(r.access('house_points'), throwsA(isA<ApiException>()));
    expect(api.requests, isEmpty);
  });
  test(
    'another account or school cannot substitute an access response',
    () async {
      for (final data in [access(account: 99), access(school: 99)]) {
        final api = FakeApi(
          routes: {
            'GET v2/recognition/house_points/access': {
              'error': false,
              'data': data,
            },
          },
        );
        await expectLater(
          repo(api).access('house_points'),
          throwsA(isA<ApiException>()),
        );
      }
    },
  );
  test(
    'context closure during a private response never returns its payload',
    () async {
      var active = true;
      final api = FakeApi(
        routes: {
          'GET v2/recognition/house_points/access': (RequestOptions options) {
            active = false;
            return {'error': false, 'data': access()};
          },
        },
      );
      await expectLater(
        repo(api, active: () => active).access('house_points'),
        throwsA(isA<ApiException>()),
      );
    },
  );
  test('counter year and caller identifiers are checked', () async {
    final api = FakeApi(
      routes: {
        'GET v2/recognition/house_points/counters': {
          'error': false,
          'data': {'session_year_id': 99, 'revision': 0, 'items': []},
        },
      },
    );
    await expectLater(repo(api).counters(7), throwsA(isA<ApiException>()));
    await expectLater(
      repo(api).points(0, 7, family: true),
      throwsArgumentError,
    );
  });
}
