import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';

const id = '8aef50e7-2429-4d2c-a523-668ae436c4d4';
Map<String, dynamic> card({int school = 1}) => {
  'id': id,
  'school_id': school,
  'person_id': 7,
  'type': 'staff',
  'status': 'active',
  'revision': 1,
  'issued_at': '2026-10-25T10:00:00Z',
  'frozen_at': null,
  'replaced_by': null,
  'has_chip': false,
};
Map<String, dynamic> config() => {
  'can_manage': false,
  'can_manage_readers': false,
  'writable': true,
  'types': ['staff'],
};
ApiCardRepository repo(
  FakeApi api, {
  bool cookie = false,
  bool Function()? active,
  String Function()? token,
}) => ApiCardRepository(
  ApiClient(
    authToken: token ?? () => 'synthetic',
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
    'cookie card requests pin account/school and suppress private debug output',
    () async {
      final logs = <String>[], old = debugPrint;
      debugPrint = (String? v, {int? wrapWidth}) {
        if (v != null) logs.add(v);
      };
      addTearDown(() => debugPrint = old);
      final api = FakeApi(
        routes: {
          'GET v2/cards/mine': {
            'error': false,
            'data': {
              'items': [card()],
              'next_page': null,
            },
          },
        },
      );
      expect((await repo(api, cookie: true).mine()).items.single.personId, 7);
      expect(api.requests.single.headers['X-School-Id'], '1');
      expect(api.requests.single.headers['X-Account-Id'], '7');
      expect(api.requests.single.headers.containsKey('Authorization'), isFalse);
      expect(logs.where((s) => s.contains('cards')), isEmpty);
    },
  );
  test('wrong-school card and late context response are refused', () async {
    final bad = FakeApi(
      routes: {
        'GET v2/cards/mine': {
          'error': false,
          'data': {
            'items': [card(school: 2)],
            'next_page': null,
          },
        },
      },
    );
    await expectLater(repo(bad).mine(), throwsA(isA<ApiException>()));
    var active = true;
    final late = FakeApi(
      routes: {
        'GET v2/cards/configuration': (RequestOptions _) {
          active = false;
          return {'error': false, 'data': config()};
        },
      },
    );
    await expectLater(
      repo(late, active: () => active).configuration(),
      throwsA(isA<ApiException>()),
    );
  });
  test(
    'changed bearer and inactive context prevent any proof or pixel request',
    () async {
      var token = 'first';
      final api = FakeApi(routes: {}), client = repo(api, token: () => token);
      token = 'second';
      await expectLater(client.credential(id), throwsA(isA<ApiException>()));
      await expectLater(
        repo(api, active: () => false).portrait(id),
        throwsA(isA<ApiException>()),
      );
      expect(api.requests, isEmpty);
    },
  );
  test(
    'portrait upload keeps exact intent and uses a generic filename',
    () async {
      final api = FakeApi(
        routes: {
          'POST v2/cards/portraits': {
            'error': false,
            'data': {'person_id': 7, 'portrait_id': id, 'revision': 2},
          },
        },
      );
      await repo(
        api,
      ).upload(7, 'staff', id, Uint8List.fromList([0xff, 0xd8, 0xff, 0xd9]));
      final request = api.requests.single, form = request.data as FormData;
      expect(Map.fromEntries(form.fields)['operation_id'], id);
      expect(form.files.single.value.filename, 'portrait.jpg');
      expect(request.queryParameters, isEmpty);
    },
  );
  test(
    'old server absence hides entry but network failure remains retryable',
    () async {
      final old = FakeApi(
        routes: {'GET v2/cards/configuration': const FakeResponse.notFound()},
      );
      expect(await cardsDeployed(repo(old)), isFalse);
      final offline = FakeApi(
        routes: {
          'GET v2/cards/configuration': (RequestOptions request) =>
              throw DioException(
                requestOptions: request,
                type: DioExceptionType.connectionError,
              ),
        },
      );
      await expectLater(
        cardsDeployed(repo(offline)),
        throwsA(isA<ApiException>()),
      );
    },
  );
}
