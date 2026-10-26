import 'dart:async';

import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';

import 'exam_fixture.dart';

ApiExamRepository repo(FakeApi api, {bool Function()? active}) =>
    ApiExamRepository(
      ApiClient(
        authToken: () => '',
        sendsBearerToken: false,
        onUnauthorized: () {},
        createDio: () => Dio()..httpClientAdapter = api,
      ),
      schoolId: 1,
      readerId: 7,
      contextActive: active ?? () => true,
    );
void main() {
  test(
    'cookie requests pin school/account and reject a replaced reader',
    () async {
      for (final wrong in [false, true]) {
        final api = FakeApi(
          routes: {
            'GET v2/exam-coach/configuration': {
              'error': false,
              'data': {...examConfiguration(), if (wrong) 'reader_id': 99},
            },
          },
        );
        if (wrong) {
          await expectLater(
            repo(api).configuration(),
            throwsA(isA<ApiException>()),
          );
        } else {
          expect((await repo(api).configuration()).staffGroups.single.id, 3);
        }
        expect(api.requests.single.headers['X-Account-Id'], '7');
        expect(api.requests.single.headers['X-School-Id'], '1');
        expect(api.requests.single.headers.containsKey('Authorization'), false);
      }
    },
  );
  test(
    'an immutable paper/review body cannot change nested memo or operation',
    () {
      final body = {
        'operation_id': examOperation,
        'questions': [
          {'memo': 'private original'},
        ],
      };
      final intent = ExamWriteIntent('staff/groups/3/papers', body);
      ((intent.body['questions'] as List).first as Map)['memo'] = 'mutated';
      ((body['questions'] as List).first as Map)['memo'] = 'also mutated';
      expect((intent.body['questions'] as List).first, {
        'memo': 'private original',
      });
      expect(intent.operation, examOperation);
    },
  );
  test(
    'learner library refuses any accidental staff memo projection',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/exam-coach/groups/3/versions/$examVersion': {
            'error': false,
            'data': examPaper(staff: true),
          },
        },
      );
      await expectLater(
        repo(api).paper(3, examVersion, staff: false),
        throwsA(isA<ApiException>()),
      );
    },
  );
  test(
    'group choices and answer operations must echo the requested source',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/exam-coach/answer-operations/$examOperation': {
            'error': false,
            'data': {...examDetail(), 'operation_id': examVersion},
          },
        },
      );
      await expectLater(
        repo(api).answerOperation(examOperation),
        throwsA(isA<ApiException>()),
      );
    },
  );
  test(
    'a late private response is rejected after the context generation ends',
    () async {
      final done = Completer<Map<String, dynamic>>();
      var active = true;
      final client = _Delayed(done);
      final service = ApiExamRepository(
        client,
        schoolId: 1,
        readerId: 7,
        contextActive: () => active,
      );
      final future = service.configuration();
      active = false;
      done.complete({'error': false, 'data': examConfiguration()});
      await expectLater(future, throwsA(isA<ApiException>()));
    },
  );
}

class _Delayed extends ApiClient {
  _Delayed(this.done)
    : super(
        authToken: () => '',
        sendsBearerToken: false,
        onUnauthorized: () {},
      );
  final Completer<Map<String, dynamic>> done;
  @override
  Future<Map<String, dynamic>?> send({
    required String method,
    required String url,
    Object? data,
    Map<String, dynamic>? queryParameters,
    int? schoolId,
    int? accountId,
    bool useAuthToken = true,
    bool notFoundAsNull = false,
    String? idempotencyKey,
    bool privateRequest = false,
    Duration? requestTimeout,
  }) => done.future;
}
