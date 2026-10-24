Map<String, dynamic> registerMatrixFixture({
  int count = 35,
  int revision = 0,
  bool canWrite = true,
  String state = 'unmarked',
  int entryRevision = 0,
}) => {
  'register_id': 4,
  'label': 'Grade 7 A',
  'session_year_id': 1,
  'class_section_id': 3,
  'timetable_lesson_id': null,
  'on_date': '2026-02-10',
  'revision': revision,
  'roster_hash': 'a' * 64,
  'can_write': canWrite,
  'learners': [
    for (var i = 0; i < count; i++)
      {
        'learner_id': 11 + i,
        'name': i == 0 ? 'Thandi Mokoena' : 'Synthetic learner ${i + 1}',
        'state': state,
        'revision': entryRevision,
        'source': null,
        'amended_at': null,
        'qr_code': 'NEAR1.synthetic-${11 + i}.signature',
      },
  ],
};
Map<String, dynamic> registerChoicesFixture() => {
  'on_date': '2026-02-10',
  'session_year_id': 1,
  'school_years': [
    {'id': 1, 'name': '2026'},
  ],
  'homerooms': [
    {'id': 3, 'name': 'Grade 7 A'},
  ],
  'periods': [
    {'id': 8, 'name': '08:00 · Mathematics · Room 101'},
  ],
  'can_manage': false,
  'settings': {
    'absence_cutoff': '10:00',
    'teaching_days': [1, 2, 3, 4, 5],
  },
};
