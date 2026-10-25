import 'dart:convert';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  Map<String, dynamic> topic(int id, int? level) => {
    'id': id,
    'topic_key': '25791550-3b61-4ad0-ab4c-b453c2fdb01$id',
    'name': 'Synthetic topic $id',
    'grade_level_id': 7,
    'grade_name': 'Grade 7',
    'level': level,
    'confidence_bp': level == null ? 0 : 2000,
    'evidence_count': level == null ? 0 : 1,
    'computed_at': null,
  };

  test('unknown mastery remains distinct from assessed level zero', () {
    final input = MasteryView.fromJson({
      'learner_id': 12,
      'subject_id': 4,
      'audience': 'family',
      'marks_visible': true,
      'policy_version': 'recent20-v1',
      'topics': [topic(1, null), topic(2, 0)],
      'target_topic_ids': [2],
      'priorities': [
        {
          'topic_id': 1,
          'name': 'Synthetic topic 1',
          'level': null,
          'confidence_bp': 0,
          'reason': 'not_yet_assessed',
          'path': [2, 1],
        },
      ],
    });
    expect(input.topics[0].level, isNull);
    expect(input.topics[1].level, 0);
    final output = jsonDecode(jsonEncode(input.toJson()));
    expect(output['topics'][0]['level'], isNull);
    expect(output['topics'][1]['level'], 0);
    expect(output['priorities'][0]['path'], [2, 1]);
    expect(output['audience'], 'family');
  });

  test('empty graph is a supported exact-revision write', () {
    final input = MasteryGraphWrite.fromJson({
      'expected_revision': 3,
      'family_marks_enabled': false,
      'links': [],
    });
    expect(input.toJson(), {
      'expected_revision': 3,
      'family_marks_enabled': false,
      'links': [],
    });
  });
}
