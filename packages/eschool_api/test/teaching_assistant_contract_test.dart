import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

const generationId = '70e436ab-d8d2-4713-9de7-56caee02a990';
const draftId = 'c76f96a2-83d2-4f01-8a7e-2202cae51e1e';
const reviewId = '2d730033-01ad-40cc-9fa5-c3fc239ec71d';

Map<String, dynamic> summary() => {
  'school_id': 1,
  'reader_id': 12,
  'id': draftId,
  'operation_id': generationId,
  'kind': 'worksheet',
  'state': 'approved',
  'revision': 2,
  'group_id': 4,
  'content_version_id': 8,
  'learner_id': null,
  'task_id': null,
};

void main() {
  test(
    'generation intent retains original UUID and explicit null evidence',
    () {
      final input = {
        'operation_id': generationId,
        'content_version_id': 8,
        'kind': 'worksheet',
        'language': 'en',
        'brief': 'Draft equal-parts activities.',
        'learner_id': null,
        'task_id': null,
        'photo_ids': <String>[],
        'topic_ids': <int>[],
      };
      final request = TeachingDraftGeneration.fromJson(input);
      expect(jsonDecode(jsonEncode(request)), input);
    },
  );

  test(
    'quarantine can withhold both intent and definition without decoding',
    () {
      final input = {
        ...summary(),
        'state': 'quarantined',
        'request': null,
        'definition': null,
        'question_labels': <dynamic>[],
      };
      final draft = TeachingDraftDetail.fromJson(input);
      expect(draft.request, isNull);
      expect(draft.definition, isNull);
      expect(draft.operationId, generationId);
      expect(jsonDecode(jsonEncode(draft)), input);
    },
  );

  test('script confidence and unknown mark retain exact values', () {
    final proposal = TeachingScriptProposal.fromJson({
      'question_id': 9,
      'mark': null,
      'confidence_bp': 1234,
      'reason': 'The visible working is incomplete.',
    });
    expect(proposal.mark, isNull);
    expect(proposal.confidenceBp, 1234);
    final precise = TeachingScriptProposal.fromJson({
      ...proposal.toJson(),
      'mark': '2.50',
    });
    expect(precise.toJson()['mark'], '2.50');
  });

  test(
    'review sends explicit action and account; receipt recovers generation',
    () async {
      final requests = <RequestOptions>[];
      final dio = Dio(BaseOptions(baseUrl: 'https://school.example/api/v2'));
      dio.interceptors.add(
        InterceptorsWrapper(
          onRequest: (request, handler) {
            requests.add(request);
            handler.resolve(
              Response(
                requestOptions: request,
                statusCode: 200,
                data: {
                  'error': false,
                  'message': 'Saved',
                  'code': 200,
                  'data': request.method == 'POST'
                      ? {...summary(), 'write_operation_id': reviewId}
                      : {
                          ...summary(),
                          'request': null,
                          'definition': null,
                          'question_labels': <dynamic>[],
                        },
                },
              ),
            );
          },
        ),
      );
      final api = TeachingAssistantApi(dio);
      final intent = TeachingDraftReview.fromJson({
        'operation_id': reviewId,
        'expected_revision': 1,
        'action': 'approve',
        'definition': null,
        'confirmed': false,
      });
      final response = await api.reviewTeachingDraft(
        draft: draftId,
        teachingDraftReview: intent,
        xSchoolId: 1,
        xAccountId: 12,
      );
      expect(response.data!.data.writeOperationId, reviewId);
      expect(requests.single.queryParameters, isEmpty);
      expect(jsonDecode(requests.single.data as String), intent.toJson());
      expect(requests.single.headers['X-Account-Id'], 12);
      await api.getTeachingDraftReceipt(
        operation: generationId,
        xSchoolId: 1,
        xAccountId: 12,
      );
      expect(
        requests.last.path,
        '/teaching-assistant/operations/$generationId',
      );
      expect(requests.last.method, 'GET');
      expect(requests.last.headers['X-School-Id'], 1);
    },
  );
}
