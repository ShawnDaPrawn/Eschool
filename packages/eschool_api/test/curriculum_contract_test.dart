import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test(
    'curriculum draft keeps exact marks, weights and date-only due dates',
    () {
      final task = CurriculumTaskWrite(
        name: 'Fractions',
        type: 'test',
        component: 'sba',
        weightBp: 10000,
        annualComponent: 'sba',
        annualWeightBp: 2500,
        maxMark: '20.00',
        dueOn: '2026-02-10',
        topicKeys: const [],
        questions: [
          CurriculumQuestionWrite(
            number: '1',
            maxMark: '7.50',
            topicKeys: const [],
          ),
          CurriculumQuestionWrite(
            number: '2',
            maxMark: '12.50',
            topicKeys: const [],
          ),
        ],
      );
      final json = task.toJson();
      expect(json['due_on'], '2026-02-10');
      expect(json['max_mark'], '20.00');
      expect(json['weight_bp'], 10000);
      expect(json['annual_weight_bp'], 2500);
      expect((json['questions'] as List).last['max_mark'], '12.50');
      expect(CurriculumTaskWrite.fromJson(json).maxMark, '20.00');
    },
  );

  test('an incomplete draft round-trips empty component maps', () {
    final draft = CurriculumPlanWrite.fromJson({
      'subject_id': 1,
      'grade_level_id': 8,
      'session_year_id': 1,
      'title': 'Draft',
      'rounding': 'half_up',
      'absent_policy': 'incomplete',
      'annual_components': <String, int>{},
      'topics': <Object>[],
      'assessments': [
        {'term_id': 1, 'components': <String, int>{}, 'tasks': <Object>[]},
      ],
    });
    expect(draft.toJson()['annual_components'], isEmpty);
    expect(draft.assessments.single.components, isEmpty);
    expect(draft.revision, isNull);
    expect(CurriculumPublishWrite(revision: 3).toJson(), {'revision': 3});
  });
}
