import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  final config = {
    'session_year_id': 7,
    'year_name': '2026',
    'can_manage': false,
    'teachers': <Object>[],
    'current_page': 1,
    'last_page': 1,
    'total': 0,
    'absences': <Object>[],
  };
  ApiCoverRepository repository(
    FakeApi api, {
    String Function()? token,
    bool Function()? active,
  }) => ApiCoverRepository(
    ApiClient(
      authToken: token ?? () => 'synthetic',
      onUnauthorized: () {},
      createDio: () => Dio()..httpClientAdapter = api,
    ),
    schoolId: 1,
    accountId: 2,
    contextActive: active ?? () => true,
  );
  test('bootstrap defaults the year and all requests pin the school', () async {
    final api = FakeApi(
      routes: {
        'GET v2/pastoral/cover/configuration': {'error': false, 'data': config},
        'GET v2/pastoral/cover/mine': {
          'error': false,
          'data': {'occurrences': []},
        },
      },
    );
    final repo = repository(api);
    final result = await repo.configuration('2026-02-10', '2026-02-17');
    await repo.mine(result.sessionYearId, '2026-02-10', '2026-02-17');
    expect(
      api.requests.first.uri.queryParameters.containsKey('session_year_id'),
      isFalse,
    );
    expect(api.requests.last.uri.queryParameters['session_year_id'], '7');
    expect(api.requests.every((r) => r.headers['X-School-Id'] == '1'), isTrue);
    expect(api.requests.every((r) => r.headers['X-Account-Id'] == '2'), isTrue);
  });
  test(
    'replaced account or school is rejected before a network request',
    () async {
      var token = 'first', active = true;
      final api = FakeApi(routes: {}), repo = repository(FakeApi(routes: {}));
      final guarded = repository(api, token: () => token, active: () => active);
      token = 'other';
      await expectLater(
        guarded.configuration('2026-02-10', '2026-02-17'),
        throwsA(isA<ApiException>()),
      );
      token = 'first';
      active = false;
      await expectLater(
        guarded.configuration('2026-02-10', '2026-02-17'),
        throwsA(isA<ApiException>()),
      );
      expect(api.requests, isEmpty);
      expect(repo.accountId, 2);
    },
  );
}
