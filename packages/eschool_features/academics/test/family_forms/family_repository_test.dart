import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';
import 'fixture.dart';

ApiFamilyFormRepository repository(
  FakeApi api, {
  bool cookie = false,
  bool Function()? active,
  String Function()? token,
}) => ApiFamilyFormRepository(
  ApiClient(
    authToken: token ?? () => 'synthetic-family-token',
    onUnauthorized: () {},
    sendsBearerToken: !cookie,
    createDio: () => Dio()..httpClientAdapter = api,
  ),
  schoolId: 1,
  accountId: 7,
  contextActive: active ?? () => true,
);
Map<String, dynamic> ok(Object data) => {
  'error': false,
  'message': 'OK',
  'data': data,
  'code': 200,
};
void main() {
  test(
    'cookie requests pin account/school and do not debug-log medical routes',
    () async {
      final log = <String>[];
      final previous = debugPrint;
      debugPrint = (String? value, {int? wrapWidth}) {
        if (value != null) log.add(value);
      };
      addTearDown(() => debugPrint = previous);
      final api = FakeApi(
        routes: {
          'GET v2/family-forms/configuration': ok(configJson()),
          'GET v2/family-forms/learners/11/health': ok(healthJson()),
        },
      );
      final repo = repository(api, cookie: true);
      expect((await repo.configuration()).readerId, 7);
      expect((await repo.health(11, 12)).categories.single.key, 'health');
      for (final request in api.requests) {
        expect(request.headers['X-Account-Id'], '7');
        expect(request.headers['X-School-Id'], '1');
        expect(request.headers.containsKey('Authorization'), isFalse);
      }
      expect(log.where((entry) => entry.contains('family-forms')), isEmpty);
    },
  );
  test(
    'wrong account/school/person and a late context response refuse bodies',
    () async {
      for (final data in [configJson(reader: 8), configJson(school: 2)]) {
        final api = FakeApi(
          routes: {'GET v2/family-forms/configuration': ok(data)},
        );
        await expectLater(
          repository(api).configuration(),
          throwsA(isA<ApiException>()),
        );
      }
      final person = FakeApi(
        routes: {
          'GET v2/family-forms/learners/11/health': ok(healthJson(person: 13)),
        },
      );
      await expectLater(
        repository(person).health(11, 12),
        throwsA(isA<ApiException>()),
      );
      var active = true;
      final late = FakeApi(
        routes: {
          'GET v2/family-forms/learners/11/health': (RequestOptions _) {
            active = false;
            return ok(healthJson());
          },
        },
      );
      await expectLater(
        repository(late, active: () => active).health(11, 12),
        throwsA(isA<ApiException>()),
      );
    },
  );
  test('token replacement sends no old-scope health request', () async {
    var token = 'first';
    final api = FakeApi(routes: {}), repo = repository(api, token: () => token);
    token = 'second';
    await expectLater(repo.health(11, 12), throwsA(isA<ApiException>()));
    expect(api.requests, isEmpty);
  });
  test(
    'health updates retain false, exact strings and explicit clear without identity fields',
    () async {
      final api = FakeApi(
        routes: {'PUT v2/family-forms/learners/11/health': ok(healthJson())},
      );
      await repository(api).saveHealth(11, 12, {
        'allergies': ProfileValue(text: 'Synthetic precise text'),
        'optional': ProfileValue(clear: true),
        'flag': ProfileValue(flag: false),
      });
      expect(api.requests.single.data, {
        'values': {
          'allergies': {'text': 'Synthetic precise text'},
          'optional': {'clear': true},
          'flag': {'flag': false},
        },
      });
      expect(api.requests.single.method, 'PUT');
    },
  );
  test(
    'attachment metadata URLs are ignored; scoped bytes require fresh purpose reads',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/family-forms/learners/11/health': ok(healthJson(file: true)),
          'GET v2/family-forms/learners/11/health/files/$fixtureFileId':
              const FakeBinaryResponse([1, 2, 3], contentType: 'image/png'),
        },
      );
      final attachment = ProfileAttachment(
        id: fixtureFileId,
        name: 'Synthetic',
        mime: 'image/png',
        size: 3,
        url: 'https://untrusted.example/private',
      );
      expect(await repository(api).healthFile(11, 12, attachment), [1, 2, 3]);
      expect(api.requestedPaths, [
        'GET v2/family-forms/learners/11/health',
        'GET v2/family-forms/learners/11/health/files/$fixtureFileId',
        'GET v2/family-forms/learners/11/health',
      ]);
      for (final request in api.requests) {
        expect(request.uri.host, isNot('untrusted.example'));
        expect(request.headers['X-Account-Id'], '7');
      }
    },
  );
  test(
    'a configuration cannot turn a managed group response into another group',
    () async {
      final data = {...summaryJson(), 'activity_group_id': 4};
      final api = FakeApi(
        routes: {
          'GET v2/family-forms/consents': ok({
            'items': [data],
            'next_page': null,
          }),
        },
      );
      await expectLater(
        repository(api).listing(groupId: 3),
        throwsA(isA<ApiException>()),
      );
      await expectLater(
        repository(api).listing(groupId: 3, learnerId: 11),
        throwsArgumentError,
      );
    },
  );
}
