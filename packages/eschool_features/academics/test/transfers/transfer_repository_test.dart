import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';

ApiTransferRepository repo(
  FakeApi api, {
  String Function()? token,
  bool Function()? active,
  bool cookie = false,
}) => ApiTransferRepository(
  ApiClient(
    authToken: token ?? () => 'synthetic',
    onUnauthorized: () {},
    createDio: () => Dio()..httpClientAdapter = api,
    sendsBearerToken: !cookie,
  ),
  schoolId: 1,
  accountId: 21,
  contextActive: active ?? () => true,
);
void main() {
  test(
    'cookie private requests pin account and school and suppress private debug URLs',
    () async {
      final logged = <String>[], before = debugPrint;
      debugPrint = (String? s, {int? wrapWidth}) {
        if (s != null) logged.add(s);
      };
      addTearDown(() => debugPrint = before);
      final api = FakeApi(
        routes: {
          'GET v2/transfers/access': {
            'error': false,
            'data': transferAccessFixture(),
          },
        },
      );
      expect((await repo(api, cookie: true).access()).guardian, isTrue);
      expect(api.requests.single.headers['X-School-Id'], '1');
      expect(api.requests.single.headers['X-Account-Id'], '21');
      expect(api.requests.single.headers.containsKey('Authorization'), isFalse);
      expect(logged.where((s) => s.contains('/transfers/')), isEmpty);
    },
  );
  test('token replacement refuses before any private read', () async {
    var token = 'first';
    final api = FakeApi(routes: {}), r = repo(api, token: () => token);
    token = 'second';
    await expectLater(r.consents(), throwsA(isA<ApiException>()));
    expect(api.requests, isEmpty);
  });
  test('download bytes preserve the expected cookie account header', () async {
    final api = FakeApi(
      routes: {
        'GET v2/transfers/consents/7/archive': const FakeBinaryResponse([
          0x50,
          0x4b,
          3,
          4,
        ], contentType: 'application/zip'),
      },
    );
    expect(await repo(api, cookie: true).archive(7), [0x50, 0x4b, 3, 4]);
    expect(api.requests.single.headers['X-Account-Id'], '21');
    expect(api.requests.single.headers['X-School-Id'], '1');
  });
  test(
    'withdrawal uses body-only exact intent without cached private receipts',
    () async {
      final api = FakeApi(
        routes: {
          'POST v2/transfers/packages/7/withdraw': {
            'error': false,
            'data': {'id': 7, 'revision': 2, 'kind': 'package'},
          },
        },
      );
      final r = repo(api);
      await r.write(7, 'withdraw', {
        'operation_id': 'd3b4c78f-8827-4671-8a52-1f57b813fbf1',
        'revision': 1,
      });
      expect(api.requests.single.queryParameters, isEmpty);
      expect(api.requests.single.data, containsPair('revision', 1));
      expect(api.requests.single.headers['X-Account-Id'], '21');
    },
  );
  test(
    'a wrong record and late token replacement never return a private body',
    () async {
      var active = true;
      final api = FakeApi(
        routes: {
          'GET v2/transfers/consents/7': {
            'error': false,
            'data': transferDetailFixture(id: 8),
          },
        },
      );
      await expectLater(repo(api).detail(7), throwsA(isA<ApiException>()));
      api.routes['GET v2/transfers/consents/7'] = (RequestOptions _) {
        active = false;
        return {'error': false, 'data': transferDetailFixture()};
      };
      await expectLater(
        repo(api, active: () => active).detail(7),
        throwsA(isA<ApiException>()),
      );
    },
  );
}
