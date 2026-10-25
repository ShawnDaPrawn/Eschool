Map<String, dynamic> transferAccessFixture({bool writable = true}) => {
  'staff': false,
  'guardian': true,
  'can_write': writable,
  'consent_version': 'school-transfer-v1',
  'today': '2026-02-10',
};

Map<String, dynamic> transferDetailFixture({
  int id = 7,
  String state = 'awaiting_consent',
  int revision = 1,
  bool writable = true,
}) => {
  'id': id,
  'public_id': 'd3b4c78f-8827-4671-8a52-1f57b813fbf1',
  'state': state,
  'revision': revision,
  'fingerprint': 'a' * 64,
  'learner_name': 'Synthetic transfer learner',
  'student_number': 'Synthetic-260012',
  'guardian_name': 'Synthetic named guardian',
  'from_school_name': 'Sending demo school',
  'to_school_name': 'Receiving demo school',
  'last_attended_on': '2026-02-28',
  'starts_on': '2026-03-01',
  'expires_at': '2026-02-24T08:00:00Z',
  'export_only': false,
  'report_count': 1,
  'document_count': 1,
  'mark_count': 1,
  'attendance_count': 1,
  'consent_version': 'school-transfer-v1',
  'reports': [
    {
      'id': 3,
      'sha256': 'b' * 64,
      'year_name': '2026',
      'term_name': 'Term 1',
      'released_at': '2026-02-10T08:00:00Z',
    },
  ],
  'documents': [
    {
      'id': 'd3b4c78f-8827-4671-8a52-1f57b813fbf1',
      'definition_id': 1,
      'name': 'Synthetic shared document.pdf',
      'mime': 'application/pdf',
      'extension': 'pdf',
      'bytes': 20,
    },
  ],
  'released_marks': [
    {
      'release_id': 2,
      'release_version': 1,
      'subject_name': 'Synthetic mathematics',
      'grade_level_id': 8,
      'session_year_id': 1,
      'term_id': 1,
      'mode': 'running',
      'percentage_bp': null,
      'rating_level': null,
      'coverage_bp': 0,
      'fingerprint': 'c' * 64,
      'released_at': '2026-02-10T08:00:00Z',
    },
  ],
  'attendance': [
    {'on_date': '2026-02-05', 'kind': 'homeroom', 'state': 'present'},
  ],
  'can_consent': writable && state == 'awaiting_consent',
  'can_withdraw':
      writable && ['awaiting_consent', 'consented', 'released'].contains(state),
  'can_release': false,
  'can_cancel': false,
  'can_accept': false,
  'can_download': ['awaiting_consent', 'consented', 'released'].contains(state),
};
Map<String, dynamic> transferPageFixture({
  String state = 'awaiting_consent',
  int revision = 1,
  bool writable = true,
}) => {
  'items': [
    transferDetailFixture(state: state, revision: revision, writable: writable),
  ],
  'page': 1,
  'has_more': false,
};
