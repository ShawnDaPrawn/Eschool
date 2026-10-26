import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

const tutorOperation = 'f1605678-17aa-48aa-88bb-123456789abc';
const tutorSession = 'd1605678-17aa-48aa-88bb-123456789abc';
Map<String, dynamic> turnFixture({String state = 'completed'}) => {
  'school_id': 1,
  'reader_id': 12,
  'id': tutorOperation,
  'operation_id': tutorOperation,
  'state': state,
  'question': state == 'completed' ? 'How do I start?' : null,
  'working': state == 'completed' ? '' : null,
  'response': state == 'completed'
      ? {
          'subject_token': 'a' * 64,
          'kind': 'hint',
          'text': 'Think about equal parts.',
          'question': 'What will you try first?',
        }
      : null,
  'failure_code': state == 'failed' ? 'school_inference_paused' : null,
  'support_message': null,
  'provisional': true,
};

void main() {
  test('working hints and withheld replies preserve scope and null bodies', () {
    final input = turnFixture();
    final result = TutorTurnResult.fromJson(input);
    expect(result.schoolId, 1);
    expect(result.readerId, 12);
    expect(result.provisional, isTrue);
    expect(result.response!.kind.value, 'hint');
    expect(jsonDecode(jsonEncode(result)), input);
    final failed = TutorTurnResult.fromJson(turnFixture(state: 'failed'));
    expect(failed.failureCode, 'school_inference_paused');
    expect(failed.response, isNull);
    expect(failed.question, isNull);
  });

  test('parent summary remains deterministic metadata without dialogue', () {
    final value = TutorParentSummary.fromJson({
      'school_id': 1,
      'reader_id': 13,
      'learner_id': 3,
      'subject_id': 4,
      'subject_name': 'Mathematics',
      'learning_sessions': 1,
      'learning_steps': 2,
      'suggestion_key': 'Ask your child to explain one worked example.',
      'contains_raw_chat': false,
    });
    expect(value.containsRawChat, isFalse);
    expect(value.toJson().containsKey('messages'), isFalse);
    expect(value.toJson().containsKey('flags'), isFalse);
  });

  test(
    'typed exact turn sends only body intent and expected school/account',
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
                  'data': turnFixture(),
                  'code': 200,
                },
              ),
            );
          },
        ),
      );
      final response = await TutorApi(dio).sendTutorMessage(
        session: tutorSession,
        xSchoolId: 1,
        xAccountId: 12,
        tutorTurnWrite: TutorTurnWrite(
          operationId: tutorOperation,
          question: 'How do I start?',
          working: '',
          photoIds: <String>{},
        ),
      );
      expect(sent!.headers['X-School-Id'], 1);
      expect(sent!.headers['X-Account-Id'], 12);
      expect(sent!.queryParameters, isEmpty);
      expect(sent!.path, '/tutor/sessions/$tutorSession/messages');
      final body = sent!.data is String
          ? jsonDecode(sent!.data as String) as Map<String, dynamic>
          : sent!.data as Map<String, dynamic>;
      expect(body['operation_id'], tutorOperation);
      expect(body.containsKey('learner_id'), isFalse);
      expect(response.data!.data.provisional, isTrue);
    },
  );

  test(
    'multipart crop approval and operation remain explicit typed fields',
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
                  'data': {
                    'school_id': 1,
                    'reader_id': 12,
                    'id': tutorOperation,
                    'operation_id': tutorOperation,
                    'sha256': 'b' * 64,
                    'mime': 'image/png',
                  },
                },
              ),
            );
          },
        ),
      );
      await TutorApi(dio).uploadTutorWorkingPhoto(
        session: tutorSession,
        operationId: tutorOperation,
        photo: MultipartFile.fromBytes([1, 2, 3], filename: 'working.png'),
        approved: true,
        cropX: 0,
        cropY: 60,
        cropWidth: 240,
        cropHeight: 60,
        xSchoolId: 1,
        xAccountId: 12,
      );
      final form = sent!.data as FormData;
      expect(Map.fromEntries(form.fields)['operation_id'], tutorOperation);
      expect(Map.fromEntries(form.fields)['approved'], 'true');
      expect(Map.fromEntries(form.fields)['crop_y'], '60');
      expect(form.files.single.value.filename, 'working.png');
      expect(sent!.queryParameters, isEmpty);
    },
  );
}
