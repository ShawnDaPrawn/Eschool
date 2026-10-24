import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

AcademicLessonWrite placement() => AcademicLessonWrite.fromJson({
  'session_year_id': 2,
  'teaching_group_id': 9,
  'class_section_id': null,
  'teacher_id': 3,
  'room_id': 4,
  'bell_period_id': 5,
  'starts_on': '2027-02-01',
  'ends_on': '2027-03-31',
  'occurs_on': null,
  'weekday': 2,
  'week_pattern': 'odd',
  'active': true,
});

void main() {
  test(
    'placements keep dates as exact calendar strings and optional nulls safe',
    () {
      final json = placement().toJson();
      expect(json['starts_on'], '2027-02-01');
      expect(json['ends_on'], '2027-03-31');
      expect(json['week_pattern'], 'odd');
      expect(AcademicLessonWrite.fromJson(json).teachingGroupId, 9);
      expect(AcademicLessonWrite.fromJson(json).classSectionId, isNull);
    },
  );
  test(
    'empty next-period results retain an explicit null and clash codes stay readable',
    () {
      final next = AcademicTimetableNextData.fromJson({'lesson': null});
      expect(next.lesson, isNull);
      expect(next.toJson().containsKey('lesson'), isTrue);
      final clash = AcademicTimetableClash.fromJson({
        'row': 0,
        'lesson_id': null,
        'date': '2027-02-02',
        'reasons': ['teacher', 'room', 'learner'],
      });
      expect(clash.reasons, ['teacher', 'room', 'learner']);
      expect(clash.toJson()['date'], '2027-02-02');
    },
  );
  test('import preserves explicit export-column and school-value mappings', () {
    final input = AcademicTimetableImportWrite.fromJson({
      'csv': 'Students Sets,Room\n7A,R1\n',
      'session_year_id': 2,
      'defaults': {
        'starts_on': '2027-02-01',
        'ends_on': '2027-03-31',
        'term_id': null,
      },
      'columns': {'teaching_group_id': 'Students Sets', 'room_id': 'Room'},
      'values': {
        'teaching_group_id': {'7A': 9},
        'room_id': {'R1': 4},
      },
    });
    expect(input.toJson()['columns']['teaching_group_id'], 'Students Sets');
    expect(input.toJson()['values']['room_id']['R1'], 4);
    expect(input.toJson()['defaults']['starts_on'], '2027-02-01');
  });
  test(
    'generated preview pins school, idempotency key and scoped path',
    () async {
      final dio = Dio(BaseOptions(baseUrl: 'https://school.test/api/v2'));
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
                  'message': 'Preview',
                  'code': 200,
                  'data': {
                    'rows': [placement().toJson()],
                    'conflicts': <Object>[],
                    'can_save': true,
                    'fingerprint': 'a' * 64,
                  },
                },
              ),
            );
          },
        ),
      );
      final response = await AcademicsApi(dio).previewAcademicTimetable(
        academicTimetablePreviewWrite: AcademicTimetablePreviewWrite(
          rows: [placement()],
        ),
        xSchoolId: 7,
        idempotencyKey: 'preview-week',
      );
      expect(sent!.path, '/academics/timetable/preview');
      expect(sent!.headers['X-School-Id'], 7);
      expect(sent!.headers['Idempotency-Key'], 'preview-week');
      expect(response.data!.data.canSave, isTrue);
    },
  );
}
