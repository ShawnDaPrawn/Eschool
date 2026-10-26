import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

const assistanceId = 'c8705678-17aa-48aa-88bb-123456789abc';
Map<String, dynamic> assistanceTurn() => {
  'school_id': 1,
  'reader_id': 12,
  'id': assistanceId,
  'purpose': 'answer',
  'state': 'prepared',
  'learner_id': 3,
  'expires_at': '2026-10-26T10:15:00.000Z',
  'language': 'af',
  'review_text': 'Only approved minimised school source text.',
  'citations': [
    {
      'token': 's1',
      'kind': 'document',
      'title': 'School hours',
      'locator': {'id': assistanceId},
    },
  ],
  'response': null,
};
void main() {
  test(
    'review keeps nullable answer and exact source locator without inference',
    () {
      final turn = SchoolAssistanceTurn.fromJson(assistanceTurn());
      expect(turn.response, isNull);
      expect(turn.citations.single.locator.id, assistanceId);
      expect(turn.toJson()['response'], isNull);
      expect(turn.expiresAt.toUtc(), DateTime.utc(2026, 10, 26, 10, 15));
      expect(turn.schoolId, 1);
      expect(turn.readerId, 12);
    },
  );
  test(
    'date-only school publication and calendar locators stay plain dates',
    () {
      final write = SchoolAssistancePublishRequest.fromJson({
        'operation_id': assistanceId,
        'expected_revision': 0,
        'title': 'School hours',
        'markdown': 'Lessons start at 08:00.',
        'source_reference': 'School handbook',
        'identifiers_reviewed': true,
        'expires_on': '2026-12-31',
      });
      expect(write.toJson()['expires_on'], '2026-12-31');
      final locator = SchoolAssistanceSourceLocator.fromJson({
        'year': 2,
        'on_date': '2026-10-26',
      });
      expect(locator.toJson()['on_date'], '2026-10-26');
    },
  );
  test('optional office capability preserves absence and explicit false', () {
    final basic = {'groups': false, 'messages': true, 'counselling': false};
    expect(FacilitatorFeatures.fromJson(basic).schoolAssistance, isNull);
    expect(
      FacilitatorFeatures.fromJson(
        basic,
      ).toJson().containsKey('school_assistance'),
      isFalse,
    );
    final denied = FacilitatorFeatures.fromJson({
      ...basic,
      'school_assistance': false,
    });
    expect(denied.schoolAssistance, isFalse);
    expect(denied.toJson()['school_assistance'], isFalse);
  });
  test(
    'disabled inference retains bounded reason without implying an answer',
    () {
      final reply = SchoolAssistanceUnavailableResponse.fromJson({
        'error': true,
        'message': 'Unavailable',
        'data': {'reason': 'inference_disabled'},
        'code': 'unavailable',
      });
      expect(reply.data!.reason, 'inference_disabled');
      expect(reply.toJson()['data'], {'reason': 'inference_disabled'});
    },
  );
  test(
    'typed review binds school/account and keeps UUID in private body',
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
                  'message': 'OK',
                  'code': 200,
                  'data': assistanceTurn(),
                },
              ),
            );
          },
        ),
      );
      final result = await SchoolAssistanceApi(dio)
          .prepareSchoolAssistanceAnswer(
            learner: 3,
            xSchoolId: 1,
            xAccountId: 12,
            schoolAssistancePrepareAnswerRequest:
                SchoolAssistancePrepareAnswerRequest(
                  operationId: assistanceId,
                  documents: const [],
                  includeCalendar: true,
                  includeRecords: false,
                  question: 'When does school start?',
                  language: 'af',
                ),
          );
      expect(sent!.path, '/school-assistance/family/learners/3/answers');
      expect(sent!.headers['X-School-Id'], 1);
      expect(sent!.headers['X-Account-Id'], 12);
      expect(sent!.queryParameters, isEmpty);
      final body = sent!.data is String
          ? jsonDecode(sent!.data as String)
          : sent!.data;
      expect(body['operation_id'], assistanceId);
      expect(body['include_records'], isFalse);
      expect(result.data!.data.response, isNull);
    },
  );
}
