import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';

ApiPrivateGalleryRepository repository(
  FakeApi api, {
  bool cookie = false,
  bool Function()? active,
  String Function()? token,
}) => ApiPrivateGalleryRepository(
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
Map<String, dynamic> configuration({int account = 12}) => {
  'school_id': 1,
  'account_id': account,
  'session_year_id': 7,
  'today': '2026-02-10',
  'years': [],
  'children': [],
  'groups': [],
  'guardian_required': true,
  'settings_revision': 0,
  'can_manage': false,
  'can_write': false,
};
void main() {
  test(
    'multipart upload binds the immutable action and uses only a generic filename',
    () async {
      final api = FakeApi(
        routes: {
          'POST v2/gallery/photos': {
            'error': false,
            'data': {'id': 8, 'revision': 1},
          },
        },
      );
      await repository(api).upload({
        'operation_id': 'synthetic-exact-action',
        'scope': 'family',
        'learner_id': 2,
        'caption': 'Private caption',
      }, Uint8List.fromList([0xff, 0xd8, 0xff, 0xd9]));
      final request = api.requests.single;
      expect(request.headers['X-School-Id'], '1');
      expect(request.headers['X-Account-Id'], '12');
      final body = request.data as FormData;
      expect(
        Map.fromEntries(body.fields)['operation_id'],
        'synthetic-exact-action',
      );
      expect(Map.fromEntries(body.fields)['caption'], 'Private caption');
      expect(body.files.single.value.filename, 'photo.jpg');
      expect(body.files.single.value.length, 4);
    },
  );

  test(
    'cookie gallery requests pin both principals without a bearer token',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/gallery/configuration': {
            'error': false,
            'data': configuration(),
          },
        },
      );
      expect(
        (await repository(api, cookie: true).configuration()).accountId,
        12,
      );
      final request = api.requests.single;
      expect(request.headers['X-School-Id'], '1');
      expect(request.headers['X-Account-Id'], '12');
      expect(request.headers.containsKey('Authorization'), isFalse);
    },
  );
  test(
    'account substitution refuses an otherwise valid gallery body',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/gallery/configuration': {
            'error': false,
            'data': configuration(account: 99),
          },
        },
      );
      await expectLater(
        repository(api).configuration(),
        throwsA(isA<ApiException>()),
      );
    },
  );
  test(
    'changed credentials or role context prevent any photo or metadata request',
    () async {
      var token = 'first';
      final api = FakeApi(routes: {}),
          repo = repository(FakeApi(routes: {}), token: () => token);
      token = 'second';
      await expectLater(repo.configuration(), throwsA(isA<ApiException>()));
      final inactive = repository(api, active: () => false);
      await expectLater(inactive.photo(2), throwsA(isA<ApiException>()));
      expect(api.requests, isEmpty);
    },
  );
  test('a withdrawn context rejects a late private response', () async {
    var active = true;
    final api = FakeApi(
      routes: {
        'GET v2/gallery/configuration': (RequestOptions _) {
          active = false;
          return {'error': false, 'data': configuration()};
        },
      },
    );
    await expectLater(
      repository(api, active: () => active).configuration(),
      throwsA(isA<ApiException>()),
    );
  });
}
