import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

Map<String, dynamic> record() => {
  'id': 9,
  'learner_id': 11,
  'session_year_id': 7,
  'grade_level_id': 5,
  'recorded_by': 12,
  'occurred_on': '2026-02-10',
  'infraction_code': 'late',
  'level_code': 'review',
  'sanction_code': null,
  'status': 'pending_review',
  'hearing_decision': null,
  'revision': 1,
  'published': false,
};
void main() {
  test(
    'unconfigured school framework stays absent without invented sanctions',
    () {
      final value = DisciplineConfiguration.fromJson({
        'session_year_id': 7,
        'today': '2026-02-10',
        'can_manage': false,
        'can_hear': false,
        'grade_ids': [],
        'grades': [],
        'years': [
          {'id': 7, 'name': '2026'},
        ],
        'framework': null,
      });
      expect(value.framework, isNull);
      expect(value.toJson()['framework'], isNull);
      expect(value.toJson()['today'], '2026-02-10');
    },
  );
  test('pending hearing metadata cannot invent a confirmed sanction', () {
    final value = DisciplineRecordMetadata.fromJson(record());
    expect(value.toJson(), record());
    expect(value.toJson().containsKey('staff_note'), isFalse);
    final hearing = DisciplineHearingWrite.fromJson({
      'operation_id': '4abe121c-21cd-4429-a4c9-9cf1a54fbfee',
      'expected_revision': 2,
      'decision': 'no_action',
      'reason': 'Fair hearing completed',
    });
    expect(hearing.toJson()['decision'], 'no_action');
    expect(hearing.toJson().containsKey('sanction_code'), isFalse);
  });
  test('unknown detention attendance is distinct from recorded absence', () {
    final value = DisciplineDetentionEntry.fromJson({
      'id': 3,
      'record_id': 9,
      'learner_id': 11,
      'name': 'Synthetic learner',
      'attendance': null,
      'status': 'active',
      'source_valid': true,
      'reason': null,
      'revision': 1,
    });
    expect(value.attendance, isNull);
    expect(value.toJson()['attendance'], isNull);
    final request = DisciplineDetentionWrite.fromJson({
      'operation_id': '4abe121c-21cd-4429-a4c9-9cf1a54fbfee',
      'session_year_id': 7,
      'grade_level_id': 5,
      'supervisor_id': 12,
      'on_date': '2026-02-10',
      'starts_at': '15:00',
      'ends_at': '16:00',
      'location': 'Supervised room',
      'capacity': 20,
    });
    expect(request.toJson()['on_date'], '2026-02-10');
    expect(request.toJson()['starts_at'], '15:00');
  });
  test(
    'typed private create sends exact UUID in body with school and account binding',
    () async {
      final dio = Dio(BaseOptions(baseUrl: 'https://example.invalid/api/v2'));
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
                  'message': 'Saved',
                  'code': 200,
                  'data': record(),
                },
              ),
            );
          },
        ),
      );
      final request = DisciplineRecordWrite.fromJson({
        'operation_id': '4abe121c-21cd-4429-a4c9-9cf1a54fbfee',
        'session_year_id': 7,
        'occurred_on': '2026-02-10',
        'infraction_code': 'late',
        'description': 'Factual family-facing description',
        'staff_note': 'Private staff note',
      });
      final response = await DisciplineApi(dio).createDisciplineRecord(
        learner: 11,
        disciplineRecordWrite: request,
        xAccountId: 12,
        xSchoolId: 1,
      );
      expect(sent!.path, '/discipline/learners/11/records');
      expect(sent!.headers['X-Account-Id'], 12);
      expect(sent!.headers['X-School-Id'], 1);
      expect(sent!.queryParameters, isEmpty);
      expect(
        (jsonDecode(sent!.data as String) as Map)['operation_id'],
        '4abe121c-21cd-4429-a4c9-9cf1a54fbfee',
      );
      expect(response.data!.data.toJson()['status'], 'pending_review');
    },
  );
}
