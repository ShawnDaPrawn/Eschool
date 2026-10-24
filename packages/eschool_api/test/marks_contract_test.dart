import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  AcademicMarkWrite input() => AcademicMarkWrite(
    rows: [
      AcademicMarkRow(
        learnerId: 10,
        expectedRevision: 3,
        disposition: AcademicMarkRowDispositionEnum.recorded,
        mark: null,
        questionMarks: [
          AcademicQuestionScore(questionId: 7, mark: '7.50'),
          AcademicQuestionScore(questionId: 8, mark: null),
        ],
      ),
    ],
  );

  test(
    'capture preserves decimal text, explicit missing scores and revisions',
    () {
      final json = input().toJson();
      final row = (json['rows'] as List).single as Map<String, dynamic>;
      expect(row['mark'], isNull);
      expect(row.containsKey('mark'), isTrue);
      expect(row['expected_revision'], 3);
      expect((row['question_marks'] as List).first['mark'], '7.50');
      expect((row['question_marks'] as List).last.containsKey('mark'), isTrue);
      expect(
        AcademicMarkWrite.fromJson(json).rows.single.questionMarks.last.mark,
        isNull,
      );
    },
  );

  test(
    'provisional results keep absent official values and integer basis points',
    () {
      final result = AcademicMarkResult.fromJson({
        'percentage_bp': null,
        'running_bp': 7505,
        'coverage_bp': 5000,
        'rating_level': null,
        'running_rating_level': 6,
        'complete': false,
        'provisional': true,
        'components': <String, dynamic>{},
        'tasks': <Object>[],
        'warnings': ['annual_terms_unpublished'],
        'rounding': 'half_even',
        'absent_policy': 'incomplete',
      });
      expect(result.percentageBp, isNull);
      expect(result.runningBp, 7505);
      expect(result.toJson()['percentage_bp'], isNull);
      expect(result.components, isEmpty);
    },
  );

  test(
    'year release preview retains a null term and an explicit running mode',
    () {
      final release = AcademicReleaseWrite(
        termId: null,
        mode: AcademicReleaseWriteModeEnum.running,
        learnerIds: {10, 11},
      );
      expect(release.toJson()['term_id'], isNull);
      expect(release.toJson().containsKey('term_id'), isTrue);
      expect(release.toJson()['mode'], 'running');
      expect(release.toJson()['fingerprint'], isNull);
      expect(AcademicReleaseWrite.fromJson(release.toJson()).learnerIds, {
        10,
        11,
      });
    },
  );

  test(
    'generated capture sends school and retry headers on the scoped task URL',
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
                  'message': 'Saved',
                  'code': 200,
                  'data': {'marks': <Object>[], 'recompute_pending': true},
                },
              ),
            );
          },
        ),
      );
      final response = await AcademicsApi(dio).captureAcademicMarks(
        group: 2,
        task: 5,
        academicMarkWrite: input(),
        xSchoolId: 3,
        idempotencyKey: 'test-capture',
      );
      expect(sent!.path, '/academics/marks/groups/2/tasks/5/capture');
      expect(sent!.method, 'POST');
      expect(sent!.headers['X-School-Id'], 3);
      expect(sent!.headers['Idempotency-Key'], 'test-capture');
      expect(response.data!.data.recomputePending, isTrue);
    },
  );
}
