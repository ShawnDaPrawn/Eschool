import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';

Map<String, dynamic> supportConfig({int school = 1, int reader = 12}) => {
  'school_id': school,
  'reader_id': reader,
  'can_parent': true,
  'can_office': false,
  'writable': true,
  'languages': ['en'],
  'answer_available': false,
  'translation_available': false,
};
ApiAssistanceRepository supportRepository(
  FakeApi api, {
  bool cookie = true,
  bool Function()? active,
  String Function()? token,
}) => ApiAssistanceRepository(
  ApiClient(
    authToken: token ?? () => 'synthetic-support-token',
    onUnauthorized: () {},
    sendsBearerToken: !cookie,
    createDio: () => Dio()..httpClientAdapter = api,
  ),
  schoolId: 1,
  readerId: 12,
  contextActive: active ?? () => true,
);

void main() {
  test(
    'private cookie and bearer reads pin account/school and never debug log URL',
    () async {
      final logs = <String>[], before = debugPrint;
      debugPrint = (String? value, {int? wrapWidth}) {
        if (value != null) logs.add(value);
      };
      addTearDown(() => debugPrint = before);
      for (final cookie in [true, false]) {
        final api = FakeApi(
          routes: {
            'GET v2/school-assistance/configuration': {
              'error': false,
              'data': supportConfig(),
            },
          },
        );
        expect(
          (await supportRepository(
            api,
            cookie: cookie,
          ).configuration()).readerId,
          12,
        );
        final request = api.requests.single;
        expect(request.headers['X-School-Id'], '1');
        expect(request.headers['X-Account-Id'], '12');
        expect(request.headers.containsKey('Authorization'), !cookie);
        expect(request.queryParameters, isEmpty);
      }
      expect(logs.where((line) => line.contains('school-assistance')), isEmpty);
    },
  );
  test(
    'substituted school or account and changed credentials never expose response',
    () async {
      for (final config in [
        supportConfig(school: 2),
        supportConfig(reader: 13),
      ]) {
        final api = FakeApi(
          routes: {
            'GET v2/school-assistance/configuration': {
              'error': false,
              'data': config,
            },
          },
        );
        await expectLater(
          supportRepository(api).configuration(),
          throwsA(isA<ApiException>()),
        );
      }
      var token = 'first';
      final api = FakeApi(routes: {}),
          repo = supportRepository(api, cookie: false, token: () => token);
      token = 'replacement';
      await expectLater(repo.configuration(), throwsA(isA<ApiException>()));
      expect(api.requests, isEmpty);
    },
  );
  test(
    'late context response is rejected and nested write body is frozen before transport',
    () async {
      var active = true;
      final api = FakeApi(
        routes: {
          'GET v2/school-assistance/configuration': (RequestOptions r) {
            active = false;
            return {'error': false, 'data': supportConfig()};
          },
        },
      );
      await expectLater(
        supportRepository(api, active: () => active).configuration(),
        throwsA(isA<ApiException>()),
      );
      final writes = FakeApi(
        routes: {
          'POST v2/school-assistance/family/learners/3/threads': {
            'error': false,
            'data': {'school_id': 1, 'reader_id': 12, 'id': 'opaque'},
          },
        },
      );
      final body = <String, dynamic>{
        'operation_id': 'a8705678-17aa-48aa-88bb-123456789abc',
        'text': 'PRIVATE original',
        'language': 'en',
      };
      final request = supportRepository(
        writes,
      ).write(AssistanceWrite.open, body, learner: 3);
      body['text'] = 'Replacement';
      await request;
      expect(writes.requests.single.data['text'], 'PRIVATE original');
      expect(writes.requests.single.queryParameters, isEmpty);
    },
  );
  test(
    'invalid references make no request and substituted document body is rejected',
    () async {
      final api = FakeApi(routes: {}), repo = supportRepository(api);
      await expectLater(
        repo.thread('not-a-uuid', office: false),
        throwsArgumentError,
      );
      await expectLater(repo.documents(0), throwsArgumentError);
      expect(api.requests, isEmpty);
      const id = 'a8705678-17aa-48aa-88bb-123456789abc';
      final changed = FakeApi(
        routes: {
          'GET v2/school-assistance/family/learners/3/documents/$id': {
            'error': false,
            'data': {
              'school_id': 1,
              'reader_id': 12,
              'id': 'b8705678-17aa-48aa-88bb-123456789abc',
              'document_key': id,
              'revision': 1,
              'title': 'Changed',
              'markdown': 'PRIVATE wrong body',
              'source_reference': 'Handbook',
              'expires_on': null,
              'state': 'published',
            },
          },
        },
      );
      await expectLater(
        supportRepository(changed).document(id, learner: 3),
        throwsFormatException,
      );
    },
  );
}
