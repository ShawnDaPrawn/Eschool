import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

const version = '8aef50e7-2429-4d2c-a523-668ae436c4d4';

Map<String, dynamic> paper({bool staff = false}) => {
  'id': 9,
  'version_id': version,
  'number': 1,
  'title': 'Synthetic licensed fractions paper',
  'framework': 'school',
  'paper_year': 2025,
  'paper_code': 'P1',
  'state': 'published',
  'licence_kind': 'school_owned',
  'licence_reference': 'Synthetic original school material',
  'questions': [
    {
      'number': '1',
      'prompt': 'Explain one half.',
      if (staff) 'memo': 'Private reviewed teacher memo.',
      'max_units': 1000,
      'topic_ids': [3],
    },
  ],
  if (staff) ...{
    'licence': {
      'kind': 'school_owned',
      'reference': 'Synthetic original school material',
      'statement': 'Synthetic rights attestation for protocol testing only.',
      'confirmed': true,
      'expires_on': '2026-12-31',
    },
    'revision': 2,
    'group_ids': [4],
    'can_edit': true,
  },
};

void main() {
  test('learner paper keeps absent memo and staff licence fields absent', () {
    final input = paper();
    final value = ExamCoachPaperVersion.fromJson(input);
    expect(value.questions.single.memo, isNull);
    expect(value.licence, isNull);
    expect(jsonDecode(jsonEncode(value)), input);
    final teacher = ExamCoachPaperVersion.fromJson(paper(staff: true));
    expect(teacher.questions.single.memo, 'Private reviewed teacher memo.');
    expect(teacher.licence!.expiresOn, '2026-12-31');
    expect(jsonDecode(jsonEncode(teacher)), paper(staff: true));
  });

  test(
    'study dates are calendar strings and own plans preserve pinned versions',
    () {
      final input = {
        'id': version,
        'learner_id': 12,
        'group_id': 4,
        'subject_id': 5,
        'exam_on': '2026-03-02',
        'papers': [
          {
            'id': 9,
            'version_id': version,
            'title': 'Synthetic paper',
            'paper_year': 2025,
            'paper_code': 'P1',
          },
        ],
        'days': [
          {
            'date': '2026-02-27',
            'topic_id': 3,
            'minutes': 10,
            'topic_name': 'Fractions',
          },
        ],
      };
      final value = ExamCoachStudyPlan.fromJson(input);
      expect(value.examOn, '2026-03-02');
      expect(value.days.single.date, '2026-02-27');
      expect(jsonDecode(jsonEncode(value)), input);
      final config = ExamCoachConfiguration.fromJson({
        'reader_id': 8,
        'learner_id': null,
        'staff_groups': <dynamic>[],
        'learner_groups': <dynamic>[],
        'writable': true,
      });
      expect(config.learnerId, isNull);
      expect(config.toJson().containsKey('learner_id'), isTrue);
    },
  );

  test(
    'future exam entry preserves omission on older facilitator profiles',
    () {
      final input = {'groups': true, 'messages': true, 'counselling': false};
      final old = FacilitatorFeatures.fromJson(input);
      expect(old.examCoach, isNull);
      expect(old.toJson(), input);
      expect(
        FacilitatorFeatures.fromJson({...input, 'exam_coach': true}).examCoach,
        isTrue,
      );
    },
  );

  test(
    'typed paper write binds exact school/account and immutable body intent',
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
                  'data': {
                    'id': 9,
                    'version_id': version,
                    'revision': 1,
                    'state': 'draft',
                  },
                  'code': 200,
                },
              ),
            );
          },
        ),
      );
      final data = paper(staff: true);
      final input = {
        'operation_id': version,
        'expected_revision': 0,
        'title': data['title'],
        'framework': 'school',
        'paper_year': 2025,
        'paper_code': 'P1',
        'group_ids': [4],
        'licence': data['licence'],
        'questions': data['questions'],
      };
      await ExamCoachApi(dio).createExamCoachPaper(
        group: 4,
        xSchoolId: 1,
        xAccountId: 8,
        examCoachPaperWrite: ExamCoachPaperWrite.fromJson(input),
      );
      expect(sent!.headers['X-School-Id'], 1);
      expect(sent!.headers['X-Account-Id'], 8);
      expect(sent!.queryParameters, isEmpty);
      expect(sent!.path, '/exam-coach/staff/groups/4/papers');
      expect(jsonDecode(sent!.data as String), input);
    },
  );
}
