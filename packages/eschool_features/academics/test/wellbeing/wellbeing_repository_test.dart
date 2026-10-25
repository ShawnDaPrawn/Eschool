import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';

Map<String, dynamic> access() => {
  'error': false,
  'data': {
    'can_configure': false,
    'professional': true,
    'can_triage': true,
    'can_casework': true,
    'can_write': true,
    'today': '2026-02-10',
    'school_slug': 'synthetic',
    'emergency_guidance': 'Trusted help',
    'timezone': 'Africa/Johannesburg',
  },
};
ApiWellbeingRepository repository(
  FakeApi api, {
  bool cookie = false,
  String Function()? token,
  bool Function()? active,
}) => ApiWellbeingRepository(
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
  test(
    'cookie clinical reader pins both identities and suppresses private logs',
    () async {
      final logged = <String>[], original = debugPrint;
      debugPrint = (String? value, {int? wrapWidth}) {
        if (value != null) logged.add(value);
      };
      addTearDown(() => debugPrint = original);
      final api = FakeApi(routes: {'GET v2/wellbeing/access': access()});
      await repository(api, cookie: true).access();
      expect(api.requests.single.headers['X-School-Id'], '1');
      expect(api.requests.single.headers['X-Account-Id'], '2');
      expect(api.requests.single.headers.containsKey('Authorization'), false);
      expect(logged.where((x) => x.contains('/wellbeing/')), isEmpty);
    },
  );
  test(
    'changed bearer token or generation prevents any clinical request',
    () async {
      var token = 'old', active = true;
      final api = FakeApi(routes: {}),
          repo = repository(api, token: () => token, active: () => active);
      token = 'new';
      await expectLater(repo.access(), throwsA(isA<ApiException>()));
      token = 'old';
      active = false;
      await expectLater(repo.access(), throwsA(isA<ApiException>()));
      expect(api.requests, isEmpty);
    },
  );
  test('late clinical response is refused after scope changes', () async {
    var active = true;
    final api = FakeApi(
      routes: {
        'GET v2/wellbeing/access': (RequestOptions _) {
          active = false;
          return access();
        },
      },
    );
    await expectLater(
      repository(api, active: () => active).access(),
      throwsA(isA<ApiException>()),
    );
    expect(api.requests, hasLength(1));
  });
  test(
    'professional choices use selected school calendar and bounded filters',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/wellbeing/cases/10/professionals': {
            'error': false,
            'data': {
              'items': [],
              'page': 2,
              'last_page': 3,
              'selected_available': false,
            },
          },
        },
      );
      await repository(api).professionals(
        10,
        '2026-02-11',
        search: '  Named  ',
        page: 2,
        selected: 7,
      );
      expect(api.requests.single.queryParameters, {
        'on_date': '2026-02-11',
        'page': 2,
        'search': 'Named',
        'selected': 7,
      });
    },
  );
  test(
    'appointments cannot return a private record from another case',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/wellbeing/cases/10/appointments': {
            'error': false,
            'data': [
              {
                'id': 1,
                'support_case_id': 99,
                'professional_user_id': 2,
                'starts_at': '2026-02-10T08:00:00Z',
                'ends_at': '2026-02-10T08:30:00Z',
                'location': 'Private other case',
                'notes': 'Other case',
                'status': 'scheduled',
                'revision': 1,
              },
            ],
          },
        },
      );
      await expectLater(
        repository(api).appointments(10, '2026-02-10', '2026-02-11'),
        throwsA(isA<ApiException>()),
      );
    },
  );
}
