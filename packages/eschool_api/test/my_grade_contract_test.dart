import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

Map<String, dynamic> gradeFixture() => {
  'school_id': 1,
  'reader_id': 12,
  'session_year_id': 1,
  'grade_level_id': 7,
  'on_date': '2026-02-10',
  'writable': true,
  'page': 1,
  'last_page': 1,
  'total': 1,
  'risk_available': true,
  'learners': [
    {
      'learner_id': 3,
      'name': 'Synthetic Learner',
      'class_section_id': 4,
      'register_id': null,
      'attendance_state': 'unrecorded',
      'signals': [
        {
          'source': 'academic',
          'score': null,
          'reason_code': 'insufficient_coverage',
          'as_of': '2026-02-10',
        },
      ],
    },
  ],
  'discipline': {
    'available': false,
    'items': <Object>[],
    'page': 1,
    'last_page': 1,
    'total': null,
  },
  'awards': {'available': false, 'items': <Object>[]},
  'actions': {'registers': true, 'messages': true, 'interventions': true},
};

void main() {
  test(
    'calendar dates and unknown attendance/evidence survive typed roundtrip',
    () {
      final input = gradeFixture();
      final value = MyGradeDashboard.fromJson(input);
      expect(value.onDate, '2026-02-10');
      expect(value.learners.single.registerId, isNull);
      expect(value.learners.single.signals.single.score, isNull);
      expect(value.discipline.total, isNull);
      expect(jsonDecode(jsonEncode(value.toJson())), input);
    },
  );

  test('optional head capability retains omission on older profiles', () {
    final old = FacilitatorFeatures.fromJson({
      'groups': true,
      'messages': true,
      'counselling': false,
    });
    expect(old.myGrade, isNull);
    expect(old.toJson().containsKey('my_grade'), isFalse);
    expect(
      FacilitatorFeatures.fromJson({...old.toJson(), 'my_grade': true}).myGrade,
      isTrue,
    );
  });

  test(
    'typed dashboard binds school/account and sends calendar/page fields as query',
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
                  'message': 'Fetched',
                  'code': 200,
                  'data': gradeFixture(),
                },
              ),
            );
          },
        ),
      );
      final reply = await MyGradeApi(dio).getMyGradeDashboard(
        grade: 7,
        xSchoolId: 1,
        xAccountId: 12,
        onDate: '2026-02-10',
        page: 2,
        disciplinePage: 3,
      );
      expect(sent!.path, '/my-grade/grades/7');
      expect(sent!.headers['X-School-Id'], 1);
      expect(sent!.headers['X-Account-Id'], 12);
      expect(sent!.queryParameters, {
        'on_date': '2026-02-10',
        'page': 2,
        'discipline_page': 3,
      });
      expect(sent!.data, isNull);
      expect(reply.data!.data.onDate, '2026-02-10');
    },
  );
}
