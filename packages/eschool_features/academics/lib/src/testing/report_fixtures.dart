/// Synthetic contracts shared by widget, browser and native report tests.
Map<String, dynamic> reportDesignFixture() => {
  'heading': 'School report',
  'introduction': null,
  'footer': null,
  'sections': [
    'subjects',
    'attendance',
    'subject_comments',
    'homeroom_comment',
  ],
  'show_crest': false,
};
Map<String, dynamic> reportConfigurationFixture({
  bool leader = true,
  bool canDesign = true,
}) => {
  'session_year_id': 1,
  'school_years': [
    {'id': 1, 'name': '2026'},
  ],
  'homerooms': leader
      ? [
          {'id': 3, 'grade_level_id': 8, 'grade_order': 7, 'name': 'Grade 7 A'},
        ]
      : [],
  'terms': [
    {'id': 2, 'name': 'Term 1', 'grade_from': 0, 'grade_to': 12},
  ],
  'subjects': [
    {'id': 2, 'name': 'Mathematics'},
  ],
  'templates': [
    {
      'id': 4,
      'name': 'Term report',
      'revision': 1,
      'design': reportDesignFixture(),
    },
  ],
  'can_design': canDesign,
  'comment_banks': [
    {
      'id': 6,
      'subject_id': 2,
      'scope': 'subject',
      'label': 'Steady progress',
      'text': 'Makes steady progress.',
      'active': true,
      'revision': 1,
    },
  ],
};
Map<String, dynamic> reportSelectionFixture() => {
  'template_id': 4,
  'session_year_id': 1,
  'term_id': null,
  'class_section_id': 3,
  'learner_ids': [11],
  'mode': 'official',
};
Map<String, dynamic> reportPayloadFixture() => {
  'learner_id': 11,
  'learner_name': 'Thandi Mokoena',
  'school_name': 'Smoke Test School',
  'year_name': '2026',
  'term_name': null,
  'homeroom_name': 'Grade 7 A',
  'grade_name': 'Grade 7',
  'session_year_id': 1,
  'term_id': null,
  'class_section_id': 3,
  'grade_level_id': 8,
  'starts_on': '2026-01-01',
  'ends_on': '2026-12-31',
  'mode': 'official',
  'provisional': false,
  'ready': true,
  'subjects': [
    {
      'subject_id': 2,
      'subject_name': 'Mathematics',
      'snapshot_id': 7,
      'fingerprint': 'a' * 64,
      'percentage_bp': 7500,
      'rating_level': 6,
      'coverage_bp': 10000,
      'complete': true,
      'ready': true,
      'result_status': 'complete',
    },
  ],
  'attendance': {
    'days_recorded': 1,
    'present_half_days': 2,
    'absent_half_days': 0,
    'late_days': 0,
    'excused_days': 0,
    'evidence': [
      {'on_date': '2026-02-10', 'state': 'present', 'revision': 1},
    ],
  },
  'enrolments': [
    {'id': 1, 'starts_on': '2026-01-01', 'ends_on': '2026-12-31'},
  ],
};
Map<String, dynamic> reportCommentFixture({
  String text = '',
  int revision = 0,
  int batchRevision = 1,
}) => {
  'card_id': 9,
  'batch_id': 8,
  'batch_revision': batchRevision,
  'learner_id': 11,
  'learner_name': 'Thandi Mokoena',
  'period_name': '2026 Annual report',
  'subject_id': 2,
  'scope_label': 'Mathematics',
  'revision': revision,
  'text': text,
};
Map<String, dynamic> reportBatchFixture({
  String state = 'draft',
  int revision = 1,
  bool detail = true,
}) => {
  'id': 8,
  'state': state,
  'revision': revision,
  'fingerprint': 'b' * 64,
  'source_fingerprint': 'a' * 64,
  'selection': reportSelectionFixture(),
  'design': {
    'template_id': 4,
    'template_revision': 1,
    'name': 'Term report',
    ...reportDesignFixture(),
    'crest': null,
  },
  'signatures': <String, dynamic>{},
  'released_at': state == 'released' ? '2026-02-10T10:00:00Z' : null,
  'cards': [
    {
      'id': 9,
      'learner_id': 11,
      'payload': reportPayloadFixture(),
      'pdf_ready': ['ready', 'released'].contains(state),
      'generated_at': null,
      if (detail) 'comments': <Object>[],
      if (detail) 'download_url': null,
    },
  ],
  if (detail) 'can_grade_sign': true,
  if (detail) 'can_principal_sign': true,
};
String reportDownloadFixture(String base, {int card = 9, bool staff = false}) =>
    '${base}academics/reports/cards/$card/download?school=1&reader=21&staff=${staff ? 1 : 0}&expires=1999999999&signature=${'a' * 64}';
Map<String, dynamic> reportFamilyFixture(
  String base, {
  int page = 1,
  int id = 9,
  int learner = 11,
}) => {
  'learner_id': learner,
  'page': page,
  'last_page': 2,
  'reports': [
    {
      'id': id,
      'batch_id': 8,
      'session_year_id': 1,
      'term_id': null,
      'year_name': '2026',
      'term_name': null,
      'learner_name': 'Thandi Mokoena',
      'provisional': false,
      'released_at': '2026-02-10T10:00:00Z',
      'read_at': null,
      'download_url': reportDownloadFixture(base, card: id),
    },
  ],
};

const reportSmokePdfBase64 =
    'JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCAzMDAgMjAwXSAvUmVzb3VyY2VzIDw8IC9Gb250IDw8IC9GMSA0IDAgUiA+PiA+PiAvQ29udGVudHMgNSAwIFIgPj4KZW5kb2JqCjQgMCBvYmoKPDwgL1R5cGUgL0ZvbnQgL1N1YnR5cGUgL1R5cGUxIC9CYXNlRm9udCAvSGVsdmV0aWNhID4+CmVuZG9iago1IDAgb2JqCjw8IC9MZW5ndGggNzAgPj4Kc3RyZWFtCkJUIC9GMSAxNCBUZiAyMCAxNjAgVGQgKFN5bnRoZXRpYyBzY2hvb2wgcmVwb3J0IC0gNzUuMDAgcGVyY2VudCkgVGogRVQKZW5kc3RyZWFtCmVuZG9iagp4cmVmCjAgNgowMDAwMDAwMDAwIDY1NTM1IGYgCjAwMDAwMDAwMDkgMDAwMDAgbiAKMDAwMDAwMDA1OCAwMDAwMCBuIAowMDAwMDAwMTE1IDAwMDAwIG4gCjAwMDAwMDAyNDEgMDAwMDAgbiAKMDAwMDAwMDMxMSAwMDAwMCBuIAp0cmFpbGVyCjw8IC9TaXplIDYgL1Jvb3QgMSAwIFIgPj4Kc3RhcnR4cmVmCjQzMQolJUVPRgo=';
