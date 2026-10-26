import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';
import 'tutor_fixture.dart';

const checkpointId = '10000000-0000-4000-8000-000000000006';
Map<String, dynamic> value({bool answered = false}) => {
  'school_id': 1,
  'reader_id': 7,
  'id': checkpointId,
  'session_id': tutorSession,
  'learner_id': 9,
  'topic_id': 6,
  'operation_id': tutorOperation,
  'answer_operation_id': answered ? tutorOperation : null,
  'state': answered ? 'answered' : 'ready',
  'question': {'kind': 'number', 'prompt': 'One half?', 'options': []},
  'correct': answered ? false : null,
  'explanation': answered ? 'Compare the two equal parts.' : null,
  'answered_at': answered ? '2026-10-26T10:15:30Z' : null,
  'verified_by': answered ? 'approved_answer_definition' : null,
};
ApiTutorRepository repository(FakeApi api, {bool Function()? active}) =>
    ApiTutorRepository(
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
    'checkpoint issue and first answer send exact body and school/account without query values',
    () async {
      final api = FakeApi(
        routes: {
          'POST v2/tutor/sessions/$tutorSession/checkpoints': {
            'error': false,
            'data': value(),
          },
          'POST v2/tutor/sessions/$tutorSession/checkpoints/$checkpointId/answer':
              {'error': false, 'data': value(answered: true)},
        },
      );
      final repo = repository(api);
      expect(
        (await repo.issueCheckpoint(tutorSession, tutorOperation)).correct,
        isNull,
      );
      final result = await repo.answerCheckpoint(
        tutorSession,
        checkpointId,
        TutorCheckpointAnswerWrite(operationId: tutorOperation, answer: '0.25'),
      );
      expect(result.correct, isFalse);
      for (final request in api.requests) {
        expect(request.headers['X-School-Id'], '1');
        expect(request.headers['X-Account-Id'], '7');
        expect(request.queryParameters, isEmpty);
      }
      final raw = api.requests.last.data;
      expect(raw is String ? jsonDecode(raw) : raw, {
        'operation_id': tutorOperation,
        'answer': '0.25',
      });
    },
  );
  test(
    'wrong account/school/session/checkpoint and premature answer disclosures are refused',
    () async {
      for (final patch in <Map<String, dynamic>>[
        {'reader_id': 99},
        {'school_id': 2},
        {'session_id': checkpointId},
        {'id': tutorOperation},
        {'correct': true},
        {'explanation': 'PRIVATE-EARLY-ANSWER'},
      ]) {
        final api = FakeApi(
          routes: {
            'GET v2/tutor/sessions/$tutorSession/checkpoints/$checkpointId': {
              'error': false,
              'data': {...value(), ...patch},
            },
          },
        );
        await expectLater(
          repository(api).checkpoint(tutorSession, checkpointId),
          throwsA(isA<ApiException>()),
        );
      }
    },
  );
  test('withdrawn client context refuses checkpoint IO before HTTP', () async {
    final api = FakeApi(routes: {});
    final repo = repository(api, active: () => false);
    await expectLater(
      repo.issueCheckpoint(tutorSession, tutorOperation),
      throwsA(isA<ApiException>()),
    );
    await expectLater(
      repo.checkpoint(tutorSession, checkpointId),
      throwsA(isA<ApiException>()),
    );
    await expectLater(
      repo.answerCheckpoint(
        tutorSession,
        checkpointId,
        TutorCheckpointAnswerWrite(operationId: tutorOperation, answer: '0.5'),
      ),
      throwsA(isA<ApiException>()),
    );
    expect(api.requests, isEmpty);
  });
}
