import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test(
    'offline operations retain zero revisions, UUID and optional QR data',
    () {
      final input = AcademicRegisterSyncWrite.fromJson({
        'operation_id': '670751e8-a3ea-44ab-bf80-efb718d7ed5a',
        'base_revision': 0,
        'roster_hash': 'a' * 64,
        'rows': [
          {
            'learner_id': 11,
            'state': 'present',
            'source': 'qr',
            'expected_revision': 0,
            'qr_token': 'NEAR1.synthetic.signature',
          },
        ],
      });
      final json = input.toJson();
      expect(json['base_revision'], 0);
      expect(json['operation_id'], '670751e8-a3ea-44ab-bf80-efb718d7ed5a');
      expect(json['rows'][0]['expected_revision'], 0);
      expect(json['rows'][0]['qr_token'], 'NEAR1.synthetic.signature');
      expect(json.containsKey('reason'), isFalse);
      final target = AcademicRegisterTargetWrite.fromJson({
        'session_year_id': 1,
        'class_section_id': 7,
        'on_date': '2026-02-10',
      });
      expect(target.toJson()['on_date'], '2026-02-10');
      expect(target.toJson().containsKey('timetable_lesson_id'), isFalse);
    },
  );

  test('unmarked rosters retain explicit nullable amendment metadata', () {
    final row = AcademicRegisterLearner.fromJson({
      'learner_id': 11,
      'name': 'Synthetic learner',
      'state': 'unmarked',
      'revision': 0,
      'source': null,
      'amended_at': null,
      'qr_code': 'NEAR1.synthetic.signature',
    });
    expect(row.toJson().containsKey('amended_at'), isTrue);
    expect(row.toJson()['source'], isNull);
    expect(row.revision, 0);
  });

  test(
    'sync pins the school and register while the operation UUID survives retries',
    () async {
      final dio = Dio(BaseOptions(baseUrl: 'https://school.test/api/v2'));
      RequestOptions? sent;
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
                    'register_id': 4,
                    'revision': 2,
                    'entries': [
                      {'learner_id': 11, 'revision': 2},
                    ],
                  },
                },
              ),
            );
          },
        ),
      );
      final input = AcademicRegisterSyncWrite(
        operationId: '670751e8-a3ea-44ab-bf80-efb718d7ed5a',
        baseRevision: 1,
        rosterHash: 'a' * 64,
        rows: [
          AcademicRegisterRowWrite(
            learnerId: 11,
            state: 'late',
            source_: 'offline',
            expectedRevision: 1,
          ),
        ],
        reason: 'Arrived after roll call',
      );
      final result = await AcademicsApi(dio).syncAcademicRegister(
        register: 4,
        xSchoolId: 7,
        academicRegisterSyncWrite: input,
      );
      expect(sent!.path, '/academics/registers/4/sync');
      expect(sent!.headers['X-School-Id'], 7);
      expect(
        (jsonDecode(sent!.data as String) as Map)['operation_id'],
        input.operationId,
      );
      expect(result.data!.data.revision, 2);
    },
  );
}
