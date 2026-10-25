Map<String, dynamic> masteryFixture({
  int learner = 11,
  int subject = 4,
  bool staff = false,
  bool visible = true,
}) => {
  'learner_id': learner,
  'subject_id': subject,
  'audience': staff ? 'staff' : 'family',
  'marks_visible': visible,
  'policy_version': 'recent20-v1',
  'topics': [
    for (final id in [1, 2])
      {
        'id': id,
        'topic_key': '25791550-3b61-4ad0-ab4c-b453c2fdb01$id',
        'name': id == 1 ? 'Fractions foundation' : 'Decimals current',
        'grade_level_id': 7,
        'grade_name': 'Grade 7',
        'level': visible && id == 2 ? 0 : null,
        'confidence_bp': visible && id == 2 ? 2000 : 0,
        'evidence_count': visible && id == 2 ? 1 : 0,
        'computed_at': null,
      },
  ],
  'target_topic_ids': [2],
  'priorities': visible
      ? [
          {
            'topic_id': 1,
            'name': 'Fractions foundation',
            'level': null,
            'confidence_bp': 0,
            'reason': 'not_yet_assessed',
            'path': [2, 1],
          },
        ]
      : [],
};
