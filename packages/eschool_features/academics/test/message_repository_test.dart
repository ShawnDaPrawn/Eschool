import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  Object ok(Object value) => {'error': false, 'data': value};
  ApiSchoolMessageRepository repository(
    FakeApi api, {
    bool Function()? active,
    String Function()? token,
  }) => ApiSchoolMessageRepository(
    ApiClient(
      authToken: token ?? () => 'synthetic',
      onUnauthorized: () {},
      createDio: () => Dio()..httpClientAdapter = api,
    ),
    schoolId: 1,
    readerId: 12,
    contextActive: active ?? () => true,
  );
  test('all message requests pin school and personal identity', () async {
    final api = FakeApi(
      routes: {
        'GET v2/communication/configuration': ok(messageConfigurationFixture()),
        'GET v2/communication/messages/11': ok(messageReaderFixture()),
        'POST v2/communication/messages/11/read': ok({
          'id': 11,
          'read_at': '2026-10-24T10:00:00Z',
        }),
      },
    );
    final repo = repository(api);
    await repo.configuration();
    await repo.reader(11);
    await repo.read(11);
    expect(
      api.requests.every((request) => request.headers['X-School-Id'] == '1'),
      isTrue,
    );
    expect(
      api.requests.every(
        (request) => request.headers['Authorization'] == 'Bearer synthetic',
      ),
      isTrue,
    );
  });
  for (final data in [
    messageReaderFixture(school: 2),
    messageReaderFixture(account: 99),
  ]) {
    test(
      'counterparty reader binding is refused before display: ${data['school_id']}/${data['reader_id']}',
      () async {
        final api = FakeApi(
          routes: {'GET v2/communication/messages/11': ok(data)},
        );
        await expectLater(
          repository(api).reader(11),
          throwsA(isA<ApiException>()),
        );
      },
    );
  }
  test(
    'account or context replacement prevents any request from an old repository',
    () async {
      var token = 'first', active = true;
      final api = FakeApi(routes: {});
      final repo = repository(api, token: () => token, active: () => active);
      token = 'second';
      await expectLater(repo.reader(11), throwsA(isA<ApiException>()));
      token = 'first';
      active = false;
      await expectLater(repo.configuration(), throwsA(isA<ApiException>()));
      expect(api.requests, isEmpty);
    },
  );
  test(
    'server authorization refusal is preserved and no private reader is returned',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/communication/messages/11': const FakeResponse({
            'message': 'Withdrawn',
          }, status: 404),
        },
      );
      await expectLater(
        repository(api).reader(11),
        throwsA(isA<ApiException>().having((e) => e.statusCode, 'status', 404)),
      );
    },
  );
  test(
    'UTF8 byte budget rejects an oversized private message before transport',
    () async {
      final api = FakeApi(routes: {});
      final write = CommunicationMessageWrite.fromJson({
        'operation_id': '4aa10dbe-a89c-4c4a-a20f-d9ef15de13ae',
        'revision': 0,
        'title': 'Draft',
        'markdown': '😀' * 17000,
        'channels': ['in_app'],
        'audience': {
          'kind': 'group',
          'session_year_id': 2,
          'target_id': 9,
          'parents': true,
        },
      });
      await expectLater(
        repository(api).save(null, write),
        throwsA(isA<ApiException>().having((e) => e.statusCode, 'status', 422)),
      );
      expect(api.requests, isEmpty);
    },
  );
}
