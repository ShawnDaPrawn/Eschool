import 'package:eschool_api/eschool_api.dart';

Map<String, dynamic> result([int? bp]) => {
  'percentage_bp': bp,
  'running_bp': bp,
  'coverage_bp': bp == null ? 0 : 10000,
  'rating_level': bp == null ? null : 6,
  'running_rating_level': bp == null ? null : 6,
  'complete': bp != null,
  'provisional': bp == null,
  'components': <String, dynamic>{},
  'tasks': <Object>[],
  'warnings': <String>[],
  'rounding': 'half_up',
  'absent_policy': 'incomplete',
};
AcademicMatrixData markMatrix({
  int group = 1,
  bool locked = false,
  bool moderated = false,
  int learnerCount = 2,
}) => AcademicMatrixData.fromJson({
  'group_id': group,
  'subject_id': 1,
  'grade_level_id': 8,
  'session_year_id': 1,
  'term_id': 1,
  'assessment_plan_id': 1,
  'version': 1,
  'locked': locked,
  'marks_lock_on': null,
  'current_page': 1,
  'last_page': 1,
  'total': learnerCount,
  'capabilities': {
    'capture': true,
    'correct': false,
    'moderate': false,
    'release': false,
  },
  'tasks': [
    {
      'id': 10,
      'canonical_task_id': 10,
      'name': 'Fractions',
      'max_mark': '20.00',
      'component': 'sba',
      'weight_bp': 10000,
      'questions': [
        {
          'id': 7,
          'canonical_question_id': 7,
          'number': '1',
          'max_mark': '7.50',
        },
        {
          'id': 8,
          'canonical_question_id': 8,
          'number': '2',
          'max_mark': '12.50',
        },
      ],
    },
  ],
  'learners': [
    for (final id in List.generate(learnerCount, (index) => index + 11))
      {
        'learner_id': id,
        'student_number': '26000000$id',
        'display_name': 'Learner $id',
        'marks': [
          if (moderated && id == 12)
            {
              'id': 1,
              'canonical_task_id': 10,
              'learner_id': 12,
              'teaching_group_id': group,
              'mark': '15.00',
              'status': 'moderated',
              'disposition': 'recorded',
              'revision': 2,
              'note': null,
              'captured_by': 1,
              'moderated_by': 2,
              'moderated_at': null,
              'question_marks': [
                {'canonical_question_id': 7, 'mark': '5.00'},
                {'canonical_question_id': 8, 'mark': '10.00'},
              ],
            },
        ],
        'result': result(),
      },
  ],
});

Map<String, Object?> releasePreviewFixture() => {
  'mode': 'official',
  'provisional': false,
  'fingerprint': 'preview-fingerprint',
  'can_release': true,
  'learners': [
    {
      'learner_id': 11,
      'snapshot_id': 1,
      'fingerprint': 'snapshot-fingerprint',
      'percentage_bp': 7505,
      'rating_level': 6,
      'coverage_bp': 10000,
      'complete': true,
      'ready': true,
    },
  ],
};
Map<String, Object?> releaseFixture() => {
  'id': 1,
  'subject_id': 1,
  'grade_level_id': 8,
  'session_year_id': 1,
  'term_id': 1,
  'mode': 'official',
  'provisional': false,
  'version': 1,
  'fingerprint': 'preview-fingerprint',
  'released_at': '2026-02-01T10:00:00Z',
  'learner_ids': [11],
};
Map<String, Object?> releasedFixture(int learner) => {
  'learner_id': learner,
  'results': [
    {
      'release_id': 1,
      'subject_id': 1,
      'subject_name': 'Mathematics',
      'grade_level_id': 8,
      'grade_name': 'Grade 7',
      'session_year_id': 1,
      'term_id': 1,
      'mode': 'official',
      'provisional': false,
      'percentage_bp': 7505,
      'rating_level': 6,
      'coverage_bp': 10000,
      'released_at': '2026-02-01T10:00:00Z',
    },
  ],
};
Map<String, Object?> periodsFixture() => {
  'learner_id': 11,
  'periods': [
    {
      'session_year_id': 1,
      'year_name': '2026',
      'term_id': 1,
      'term_name': 'Term 1', 'released_at': '2026-02-01T10:00:00Z',
    },
  ],
};
Map<String, Object?> academicContextFixture({String role = 'Teacher'}) => {
  'current_school': {
    'id': 1,
    'name': 'Smoke Test School',
    'short_name': null,
    'crest_url': null,
    'tagline': null,
    'timezone': 'Africa/Johannesburg',
    'branding': {'theme_color': null, 'secondary_color': null},
    'session_year': null,
  },
  'schools': [
    {
      'school': {
        'id': 1,
        'name': 'Smoke Test School',
        'short_name': null,
        'crest_url': null,
      },
      'roles': [role],
    },
  ],
  'feature_flags': {'academic_marks': true},
};
