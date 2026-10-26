import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

const answerId = '8aef50e7-2429-4d2c-a523-668ae436c4d4';
Map<String, dynamic> answer({String state = 'completed'}) => {
  'id': answerId,
  'operation_id': answerId,
  'state': state,
  'failure_code': state == 'quarantined' ? 'inference_quarantined' : null,
  'response': state == 'completed'
      ? {
          'question_token': 'a' * 64,
          'mark_units': 500,
          'confidence_basis_points': 7300,
          'feedback': 'Explain the equal parts.',
        }
      : null,
  'review': null,
  if (state == 'completed') ...{
    'group_id': 4,
    'paper_version_id': answerId,
    'question_number': '1',
    'revision': 2,
    'working': '',
    'provisional': true,
    'expires_at': '2026-03-12T09:00:00Z',
  },
};

Dio protocol(Object body, void Function(RequestOptions) capture) {
  final dio = Dio(BaseOptions(baseUrl: 'https://school.example/api/v2'));
  dio.interceptors.add(
    InterceptorsWrapper(
      onRequest: (request, handler) {
        capture(request);
        handler.resolve(
          Response(
            requestOptions: request,
            statusCode: 200,
            data: {
              'error': false,
              'message': 'Saved',
              'data': body,
              'code': 200,
            },
          ),
        );
      },
    ),
  );
  return dio;
}

void main() {
  test(
    'provisional units/confidence remain typed and quarantine omits human input',
    () {
      final value = ExamCoachAnswerDetail.fromJson(answer());
      expect(value.response!.markUnits, 500);
      expect(value.response!.confidenceBasisPoints, 7300);
      expect(value.provisional, isTrue);
      expect(value.review, isNull);
      final blocked = ExamCoachAnswerDetail.fromJson(
        answer(state: 'quarantined'),
      );
      expect(blocked.working, isNull);
      expect(blocked.response, isNull);
      expect(blocked.review, isNull);
      expect(jsonDecode(jsonEncode(blocked)), answer(state: 'quarantined'));
    },
  );

  test(
    'teacher confirmation binds group/account and sends only immutable body fields',
    () async {
      late RequestOptions sent;
      final dio = protocol({
        'id': answerId,
        'answer_id': answerId,
        'revision': 3,
        'mark_units': 600,
      }, (r) => sent = r);
      final input = {
        'operation_id': answerId,
        'expected_revision': 2,
        'mark_units': 600,
        'reason': 'Teacher verified the synthetic working.',
      };
      final result = await ExamCoachApi(dio).reviewExamCoachAnswer(
        group: 4,
        answer: answerId,
        xSchoolId: 1,
        xAccountId: 8,
        examCoachAnswerReviewWrite: ExamCoachAnswerReviewWrite.fromJson(input),
      );
      expect(result.data!.data.markUnits, 600);
      expect(sent.path, '/exam-coach/staff/groups/4/answers/$answerId/review');
      expect(sent.headers['X-School-Id'], 1);
      expect(sent.headers['X-Account-Id'], 8);
      expect(sent.queryParameters, isEmpty);
      expect(jsonDecode(sent.data as String), input);
    },
  );

  test(
    'crop upload keeps sharing approval and exact operation in multipart body',
    () async {
      late RequestOptions sent;
      final dio = protocol({
        'id': answerId,
        'operation_id': answerId,
        'state': 'prepared',
      }, (r) => sent = r);
      await ExamCoachApi(dio).uploadExamCoachAnswer(
        group: 4,
        version: answerId,
        operationId: answerId,
        questionNumber: '1',
        photo: MultipartFile.fromBytes([1, 2, 3], filename: 'working.png'),
        approved: true,
        shareWithTeacher: true,
        cropX: 0,
        cropY: 20,
        cropWidth: 100,
        cropHeight: 40,
        xSchoolId: 1,
        xAccountId: 12,
      );
      final form = sent.data as FormData;
      final fields = Map.fromEntries(form.fields);
      expect(fields['operation_id'], answerId);
      expect(fields['approved'], 'true');
      expect(fields['share_with_teacher'], 'true');
      expect(fields['crop_y'], '20');
      expect(sent.queryParameters, isEmpty);
      expect(sent.headers['X-Account-Id'], 12);
    },
  );

  test(
    'older configurations omit optional activation while explicit false survives',
    () {
      final input = {
        'reader_id': 12,
        'learner_id': 9,
        'staff_groups': <Object>[],
        'learner_groups': <Object>[],
        'writable': true,
      };
      final old = ExamCoachConfiguration.fromJson(input);
      expect(old.photoFeedbackEnabled, isNull);
      expect(old.toJson().containsKey('photo_feedback_enabled'), isFalse);
      final disabled = ExamCoachConfiguration.fromJson({
        ...input,
        'photo_feedback_enabled': false,
      });
      expect(disabled.photoFeedbackEnabled, isFalse);
      expect(disabled.toJson()['photo_feedback_enabled'], isFalse);
    },
  );
}
