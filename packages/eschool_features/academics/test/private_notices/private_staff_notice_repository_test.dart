import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  ApiPrivateStaffNoticeRepository repository(
    FakeApi fake, {
    bool Function()? active,
  }) => ApiPrivateStaffNoticeRepository(
    ApiClient(
      authToken: () => '',
      sendsBearerToken: false,
      onUnauthorized: () {},
      createDio: () => Dio()..httpClientAdapter = fake,
    ),
    schoolId: 1,
    accountId: 7,
    contextActive: active ?? () => true,
  );
  Map<String, dynamic> page({int reader = 7}) => {
    'school_id': 1,
    'reader_id': reader,
    'writable': true,
    'items': <dynamic>[],
    'page': 1,
    'last_page': 1,
    'unread_count': 0,
  };
  test(
    'cookie scope is explicit and private notice paths never enter debug logs',
    () async {
      final logs = <String>[];
      final old = debugPrint;
      debugPrint = (String? value, {int? wrapWidth}) {
        if (value != null) logs.add(value);
      };
      addTearDown(() => debugPrint = old);
      final fake = FakeApi(
        routes: {
          'GET v2/staff/private-notices': (RequestOptions request) {
            expect(request.headers['X-School-Id'], '1');
            expect(request.headers['X-Account-Id'], '7');
            expect(request.headers.containsKey('Authorization'), isFalse);
            expect(request.queryParameters, {'page': 1});
            return {'error': false, 'data': page()};
          },
        },
      );
      expect((await repository(fake).notices(1)).readerId, 7);
      expect(logs.where((line) => line.contains('private-notices')), isEmpty);
    },
  );
  test(
    'replacement account projection and late context reply are refused',
    () async {
      final wrong = FakeApi(
        routes: {
          'GET v2/staff/private-notices': {
            'error': false,
            'data': page(reader: 9),
          },
        },
      );
      await expectLater(
        repository(wrong).notices(1),
        throwsA(isA<ApiException>()),
      );
      var active = true;
      final late = FakeApi(
        routes: {
          'GET v2/staff/private-notices': (RequestOptions _) {
            active = false;
            return {'error': false, 'data': page()};
          },
        },
      );
      await expectLater(
        repository(late, active: () => active).notices(1),
        throwsA(isA<ApiException>()),
      );
    },
  );
  test(
    'marker has no editable query or body and must echo its notice identity',
    () async {
      final fake = FakeApi(
        routes: {
          'POST v2/staff/private-notices/19/read': (RequestOptions request) {
            expect(request.queryParameters, isEmpty);
            expect(request.data, <String, dynamic>{});
            return {
              'error': false,
              'data': {
                'id': 20,
                'school_id': 1,
                'reader_id': 7,
                'read_at': '2026-02-10T12:00:00Z',
              },
            };
          },
        },
      );
      await expectLater(
        repository(fake).markRead(19),
        throwsA(isA<ApiException>()),
      );
    },
  );
}
