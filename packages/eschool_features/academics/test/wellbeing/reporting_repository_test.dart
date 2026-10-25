import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';

Map<String, dynamic> view() => {
  'error': false,
  'data': {
    'anonymous': false,
    'body': 'Private named report',
    'urgency': 'routine',
    'status': 'open',
    'revision': 1,
    'submitted_at': null,
    'emergency_guidance': 'Trusted help',
    'threads': [],
    'has_older': false,
    'next_before': null,
  },
};
ApiReportingRepository repository(
  FakeApi api, {
  bool cookie = false,
  bool Function()? active,
}) => ApiReportingRepository(
  ApiClient(
    authToken: () => 'synthetic',
    onUnauthorized: () {},
    createDio: () => Dio()..httpClientAdapter = api,
    sendsBearerToken: !cookie,
  ),
  schoolId: 1,
  accountId: 2,
  contextActive: active ?? () => true,
);
void main() {
  test(
    'own report code travels in private POST body with cookie identity pins',
    () async {
      final logged = <String>[], original = debugPrint;
      debugPrint = (String? value, {int? wrapWidth}) {
        if (value != null) logged.add(value);
      };
      addTearDown(() => debugPrint = original);
      final api = FakeApi(routes: {'POST v2/wellbeing/own/follow-up': view()}),
          secret = 'a' * 64;
      await repository(api, cookie: true).read(secret);
      final sent = api.requests.single;
      expect(sent.method, 'POST');
      expect(sent.data, {'secret': secret});
      expect(sent.queryParameters, isEmpty);
      expect(sent.uri.toString(), isNot(contains(secret)));
      expect(sent.headers['X-Account-Id'], '2');
      expect(sent.headers['X-School-Id'], '1');
      expect(sent.headers.containsKey('Authorization'), false);
      expect(
        logged.where((x) => x.contains(secret) || x.contains('/wellbeing/')),
        isEmpty,
      );
    },
  );
  test('anonymous page has no account child code query or fragment', () {
    final api = FakeApi(routes: {}),
        uri = repository(api).anonymousForm('synthetic');
    expect(uri.path, '/school/synthetic/report');
    expect(uri.query, isEmpty);
    expect(uri.fragment, isEmpty);
    expect(uri.userInfo, isEmpty);
    expect(api.requests, isEmpty);
  });
  test(
    'late own report result is discarded after local authority changes',
    () async {
      var active = true;
      final api = FakeApi(
        routes: {
          'POST v2/wellbeing/own/follow-up': (RequestOptions _) {
            active = false;
            return view();
          },
        },
      );
      await expectLater(
        repository(api, active: () => active).read('a' * 64),
        throwsA(isA<ApiException>()),
      );
    },
  );
}
