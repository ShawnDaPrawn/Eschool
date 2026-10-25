import 'package:dio/dio.dart';
import 'package:eschool_academics/src/discipline/discipline_repository.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';
import 'discipline_test.dart' show disciplineDetail;

void main() {
  ApiDisciplineRepository repository(
    FakeApi fake, {
    bool Function()? active,
    String Function()? token,
    bool bearer = true,
  }) => ApiDisciplineRepository(
    ApiClient(
      authToken: token ?? () => 'Synthetic token',
      sendsBearerToken: bearer,
      onUnauthorized: () {},
      createDio: () => Dio()..httpClientAdapter = fake,
    ),
    schoolId: 1,
    readerId: 12,
    contextActive: active ?? () => true,
  );
  test(
    'cookie readers bind the expected account and keep private record URLs out of logs',
    () async {
      final messages = <String>[];
      final old = debugPrint;
      debugPrint = (String? message, {int? wrapWidth}) {
        if (message != null) messages.add(message);
      };
      addTearDown(() => debugPrint = old);
      final api = FakeApi(
        routes: {
          'GET v2/discipline/records/9': (RequestOptions request) {
            expect(request.headers['X-School-Id'], '1');
            expect(request.headers['X-Account-Id'], '12');
            expect(request.headers.containsKey('Authorization'), false);
            return {'error': false, 'data': disciplineDetail()};
          },
        },
      );
      expect(
        (await repository(api, bearer: false).record(9)).staffNote,
        'Private school follow-up',
      );
      expect(
        messages.where(
          (line) =>
              line.contains('/records/9') ||
              line.contains('Private school follow-up'),
        ),
        isEmpty,
      );
    },
  );
  test('another record cannot substitute a requested private body', () async {
    final api = FakeApi(
      routes: {
        'GET v2/discipline/records/9': {
          'error': false,
          'data': disciplineDetail(id: 99),
        },
      },
    );
    await expectLater(repository(api).record(9), throwsA(isA<ApiException>()));
  });
  test('a late reply is refused after account or school replacement', () async {
    var active = true, token = 'First token';
    final api = FakeApi(
      routes: {
        'GET v2/discipline/records/9': (RequestOptions request) {
          active = false;
          token = 'Replacement token';
          return {'error': false, 'data': disciplineDetail()};
        },
      },
    );
    await expectLater(
      repository(api, active: () => active, token: () => token).record(9),
      throwsA(isA<ApiException>()),
    );
  });
  test(
    'family bootstrap omits an unknown year and refuses another learner projection',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/discipline/family/learners/11': (RequestOptions request) {
            expect(
              request.queryParameters.containsKey('session_year_id'),
              false,
            );
            return {
              'error': false,
              'data': {
                'learner_id': 99,
                'session_year_id': 7,
                'page': 1,
                'last_page': 1,
                'records': <dynamic>[],
              },
            };
          },
        },
      );
      await expectLater(
        repository(api).family(11, null),
        throwsA(isA<ApiException>()),
      );
    },
  );
  test(
    'exact mutation intent cannot be changed by editing the original or returned input',
    () {
      final original = <String, dynamic>{
        'operation_id': 'synthetic-id',
        'description': 'Original fact',
        'nested': {'id': 4},
      };
      final intent = DisciplineIntent(
        DisciplineAction.create,
        original,
        learner: 11,
      );
      original['description'] = 'Edited later';
      (original['nested'] as Map)['id'] = 99;
      final decoded = intent.body;
      decoded['description'] = 'Another edit';
      expect(intent.body['description'], 'Original fact');
      expect(intent.body['nested'], {'id': 4});
    },
  );
  test(
    'guardian acknowledgement uses its family endpoint and verifies the exact revision',
    () async {
      final api = FakeApi(
        routes: {
          'POST v2/discipline/family/records/9/acknowledge':
              (RequestOptions request) {
                expect(request.data, {
                  'operation_id': 'synthetic-uuid',
                  'expected_revision': 2,
                });
                return {
                  'error': false,
                  'data': {
                    'record_id': 9,
                    'revision': 1,
                    'acknowledged_at': '2026-02-10T12:00:00Z',
                  },
                };
              },
        },
      );
      await expectLater(
        repository(api).write(
          DisciplineIntent(DisciplineAction.acknowledge, {
            'operation_id': 'synthetic-uuid',
            'expected_revision': 2,
          }, record: 9),
        ),
        throwsA(isA<ApiException>()),
      );
    },
  );
}
