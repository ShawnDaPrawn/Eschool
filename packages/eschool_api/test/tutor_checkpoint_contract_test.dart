import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

const checkpoint = 'c8705678-17aa-48aa-88bb-123456789abc';
const session = 'd1605678-17aa-48aa-88bb-123456789abc';
const operation = 'f1605678-17aa-48aa-88bb-123456789abc';
Map<String, dynamic> detail({bool answered = false}) => {
  'school_id': 1,
  'reader_id': 12,
  'id': checkpoint,
  'session_id': session,
  'learner_id': 3,
  'topic_id': 4,
  'operation_id': checkpoint,
  'answer_operation_id': answered ? operation : null,
  'state': answered ? 'answered' : 'ready',
  'question': {
    'kind': 'number',
    'prompt': 'One half as a decimal?',
    'options': [],
  },
  'correct': answered ? false : null,
  'explanation': answered ? 'Divide one into two equal parts.' : null,
  'answered_at': answered ? '2026-10-26T10:15:30.000Z' : null,
  'verified_by': answered ? 'approved_answer_definition' : null,
};

void main() {
  test(
    'unanswered checks withhold answers and verified outcomes preserve false',
    () {
      final ready = TutorCheckpointDetail.fromJson(detail());
      expect(ready.question.kind.value, 'number');
      expect(ready.correct, isNull);
      expect(ready.explanation, isNull);
      expect(ready.verifiedBy, isNull);
      expect(ready.toJson().containsKey('answers'), isFalse);
      final answered = TutorCheckpointDetail.fromJson(detail(answered: true));
      expect(answered.correct, isFalse);
      expect(
        answered.answeredAt!.toUtc(),
        DateTime.utc(2026, 10, 26, 10, 15, 30),
      );
      expect(answered.verifiedBy, 'approved_answer_definition');
    },
  );

  test(
    'typed first answer keeps exact UUID and school/account in a private body',
    () async {
      RequestOptions? sent;
      final dio = Dio(BaseOptions(baseUrl: 'https://school.example/api/v2'));
      dio.interceptors.add(
        InterceptorsWrapper(
          onRequest: (request, handler) {
            sent = request;
            handler.resolve(
              Response(
                requestOptions: request,
                statusCode: 200,
                data: {
                  'error': false,
                  'message': 'Saved',
                  'code': 200,
                  'data': detail(answered: true),
                },
              ),
            );
          },
        ),
      );
      final response = await TutorApi(dio).answerTutorCheckpoint(
        session: session,
        checkpoint: checkpoint,
        xSchoolId: 1,
        xAccountId: 12,
        tutorCheckpointAnswerWrite: TutorCheckpointAnswerWrite(
          operationId: operation,
          answer: '0.25',
        ),
      );
      expect(
        sent!.path,
        '/tutor/sessions/$session/checkpoints/$checkpoint/answer',
      );
      expect(sent!.headers['X-School-Id'], 1);
      expect(sent!.headers['X-Account-Id'], 12);
      expect(sent!.queryParameters, isEmpty);
      final body = sent!.data is String
          ? jsonDecode(sent!.data as String)
          : sent!.data;
      expect(body, {'operation_id': operation, 'answer': '0.25'});
      expect(response.data!.data.correct, isFalse);
    },
  );
}
