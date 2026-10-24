import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test(
    'join keeps an explicit empty allocation, no fee posting and dated retry',
    () {
      final input = AcademicTransitionCommit(
        kind: 'join',
        learnerId: 7,
        sessionYearId: 2,
        effectiveOn: '2026-02-10',
        reason: 'Reviewed join',
        classSectionId: 3,
        groupIds: {},
        feeProrataRequested: false,
        operationId: 'd2160bf5-7a51-4e32-8910-f978744cc17d',
        fingerprint: 'a' * 64,
      );
      final json = input.toJson();
      expect(json['effective_on'], '2026-02-10');
      expect(json['group_ids'], isEmpty);
      expect(json['fee_prorata_requested'], isFalse);
      expect(json.containsKey('target_group_id'), isFalse);
      expect(
        AcademicTransitionCommit.fromJson(json).operationId,
        input.operationId,
      );
    },
  );

  test(
    'leave receipt retains cancelled future entries and null legacy grade',
    () {
      final row = AcademicTransitionRecord.fromJson({
        'id': 1,
        'learner_id': 7,
        'session_year_id': 2,
        'grade_level_id': null,
        'kind': 'leave',
        'effective_on': '2026-02-10',
        'reason': 'Reviewed departure',
        'operation_id': 'd2160bf5-7a51-4e32-8910-f978744cc17d',
        'created_at': '2026-02-10T08:00:00Z',
        'result': {
          'last_attended_on': '2026-02-10',
          'ended_enrolment_ids': [3],
          'cancelled_enrolment_ids': [4],
          'ended_membership_ids': [],
          'cancelled_membership_ids': [5],
          'records_deleted': false,
        },
      });
      expect(row.gradeLevelId, isNull);
      expect(row.result.cancelledEnrolmentIds, [4]);
      expect(row.result.recordsDeleted, isFalse);
      expect(row.toJson()['grade_level_id'], isNull);
    },
  );

  test(
    'queued export transport pins explicit selection and own operation UUID',
    () async {
      final dio = Dio(BaseOptions(baseUrl: 'https://school.example/api/v2'));
      RequestOptions? sent;
      dio.interceptors.add(
        InterceptorsWrapper(
          onRequest: (options, handler) {
            sent = options;
            handler.resolve(
              Response(
                requestOptions: options,
                statusCode: 200,
                data: {
                  'error': false,
                  'message': 'Queued',
                  'code': 200,
                  'data': {
                    'id': 9,
                    'transition_id': 1,
                    'learner_id': 7,
                    'state': 'queued',
                    'failure_code': null,
                    'expires_at': '2026-02-17T08:00:00Z',
                    'generated_at': null,
                    'artifact_sha256': null,
                    'artifact_bytes': null,
                    'signing_public_key': null,
                    'signing_key_id': null,
                    'download_url': null,
                  },
                },
              ),
            );
          },
        ),
      );
      final input = AcademicExitPrepare(
        transitionId: 1,
        reportIds: {8},
        documentIds: {},
        operationId: 'd2160bf5-7a51-4e32-8910-f978744cc17d',
        fingerprint: 'b' * 64,
      );
      final result = await AcademicsApi(
        dio,
      ).prepareAcademicExitPackage(academicExitPrepare: input, xSchoolId: 3);
      expect(sent!.path, '/academics/transitions/exports/prepare');
      expect(sent!.headers['X-School-Id'], 3);
      expect(
        (jsonDecode(sent!.data as String)
            as Map<String, dynamic>)['operation_id'],
        input.operationId,
      );
      expect(
        (jsonDecode(sent!.data as String)
            as Map<String, dynamic>)['document_ids'],
        isEmpty,
      );
      expect(result.data!.data.downloadUrl, isNull);
      expect(result.data!.data.state, 'queued');
    },
  );
}
