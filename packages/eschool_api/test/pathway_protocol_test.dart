import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

const reference = 'b63cbd29-266d-470f-b428-4b1fcfa5d46f';
Map<String, dynamic> projection() => {
  'school_id': 1,
  'reader_id': 12,
  'learner_id': 3,
  'reference_id': reference,
  'revision': 2,
  'kind': 'programme',
  'source': 'scenario',
  'selection': null,
  'released_evidence': [],
  'score': {
    'projected_score': null,
    'known_subtotal': 0,
    'minimum_score': 4,
    'status': 'unknown',
    'missing_subject_ids': [4],
    'below_minimum_subject_ids': [],
    'counted_subject_ids': [],
    'subjects': [
      {
        'subject_id': 4,
        'percentage_bp': null,
        'points': null,
        'minimum_bp': 5000,
        'counts_for_score': true,
      },
    ],
  },
};

void main() {
  test(
    'source maintenance preserves calendar dates without adding timestamps',
    () {
      final source = PathwayReferenceWrite.fromJson({
        'operation_id': reference,
        'revision': 0,
        'kind': 'programme',
        'status': 'published',
        'title': 'Synthetic programme',
        'institution': 'Synthetic institution',
        'source_title': 'Synthetic source',
        'source_url': 'https://institution.example.test/source',
        'source_year': 2026,
        'reviewed_on': '2026-02-10',
        'expires_on': '2026-12-31',
        'guidance': 'Source-specific example only.',
        'rules': {
          'subjects': [
            {'subject_id': 4, 'minimum_bp': 5000, 'counts_for_score': true},
          ],
          'bands': [
            {'minimum_bp': 5000, 'points': 4},
            {'minimum_bp': 0, 'points': 0},
          ],
          'best_n': 1,
          'minimum_score': 4,
        },
      });
      expect(source.toJson()['reviewed_on'], '2026-02-10');
      expect(source.toJson()['expires_on'], '2026-12-31');
    },
  );

  test(
    'unknown source evidence stays nullable rather than becoming a zero score',
    () {
      final value = PathwayProjection.fromJson(projection());
      expect(value.score!.projectedScore, isNull);
      expect(value.score!.subjects.single.percentageBp, isNull);
      expect(value.score!.subjects.single.points, isNull);
      expect(value.selection, isNull);
      expect((value.toJson()['score'] as Map)['projected_score'], isNull);
      expect(value.toJson()['selection'], isNull);
    },
  );

  test('guide checks preserve false and cannot manufacture a score', () {
    final value = PathwayProjection.fromJson({
      ...projection(),
      'kind': 'subject_choice_guide',
      'source': 'selection',
      'score': null,
      'selection': {
        'valid': false,
        'selected_subject_ids': [],
        'missing_compulsory_subject_ids': [4],
        'groups': [],
      },
    });
    expect(value.score, isNull);
    expect(value.selection!.valid, isFalse);
    expect((value.toJson()['selection'] as Map)['valid'], isFalse);
    expect(value.toJson()['score'], isNull);
  });

  test(
    'typed scenario uses a private request body with explicit school and account',
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
                  'data': projection(),
                },
              ),
            );
          },
        ),
      );
      final result = await EschoolApi(dio: dio)
          .getPathwaysApi()
          .calculatePathwayProjection(
            reference: reference,
            xSchoolId: 1,
            xAccountId: 12,
            pathwayProjectionRequest: PathwayProjectionRequest.fromJson({
              'learner_id': 3,
              'revision': 2,
              'source': 'scenario',
              'marks': [
                {'subject_id': 4, 'percentage_bp': null},
              ],
            }),
          );
      expect(sent!.method, 'POST');
      expect(sent!.path, '/pathways/references/$reference/projection');
      expect(sent!.queryParameters, isEmpty);
      expect(sent!.headers['X-School-Id'], 1);
      expect(sent!.headers['X-Account-Id'], 12);
      final body = jsonDecode(sent!.data as String) as Map;
      expect(body['learner_id'], 3);
      expect(body['revision'], 2);
      expect((body['marks'] as List).single['percentage_bp'], isNull);
      expect(body.containsKey('operation_id'), isFalse);
      expect(result.data!.data.score!.projectedScore, isNull);
      dio.close();
    },
  );
}
