Map<String, dynamic> messageConfigurationFixture({
  bool staff = true,
  int school = 1,
  int account = 12,
}) => {
  'school_id': school,
  'reader_id': account,
  'timezone': 'Africa/Johannesburg',
  'staff_available': staff,
  'can_manage': false,
  'session_year_id': staff ? 2 : null,
  'years': staff
      ? [
          {'id': 2, 'name': '2026'},
        ]
      : [],
  'scopes': staff
      ? [
          {'kind': 'group', 'target_id': 9, 'name': 'Fractions group'},
        ]
      : [],
  'audiences': staff
      ? [
          {
            'id': 3,
            'name': 'Maths parents',
            'revision': 1,
            'active': true,
            'editable': true,
            'user_ids': [13],
          },
        ]
      : [],
  'rules': staff
      ? {
          'allow_school': false,
          'requires_approval': true,
          'grade_head_exempt': false,
          'head_grades': [],
          'versions': [
            [4, 0],
          ],
        }
      : null,
  'channels': ['in_app', 'push', 'email'],
};
Map<String, dynamic> messageSummaryFixture({
  String state = 'sent',
  int revision = 1,
  String title = 'Private weekly update',
  bool read = false,
}) => {
  'id': 11,
  'title': title,
  'state': state,
  'revision': revision,
  'published_at': state == 'sent' ? '2026-10-24T09:00:00Z' : null,
  'scheduled_at': null,
  'updated_at': '2026-10-24T09:01:00Z',
  'read_at': read ? '2026-10-24T09:02:00Z' : null,
};
Map<String, dynamic> messageListFixture({
  bool prepared = false,
  int page = 1,
}) => {
  'page': page,
  'last_page': page,
  'items': [
    {
      ...messageSummaryFixture(state: prepared ? 'draft' : 'sent'),
      'is_author': prepared,
      'can_review': false,
    },
  ],
};
Map<String, dynamic> messageReaderFixture({
  int school = 1,
  int account = 12,
  String body = '# Family information\n\nPrivate school text.',
}) => {
  ...messageSummaryFixture(),
  'school_id': school,
  'reader_id': account,
  'markdown': body,
};
Map<String, dynamic> messageEditorFixture({
  String state = 'draft',
  int revision = 1,
  bool author = true,
  bool reviewer = false,
  String title = 'Private weekly update',
  String body = '# Family information\n\nPrivate school text.',
}) => {
  ...messageSummaryFixture(state: state, revision: revision, title: title),
  'markdown': body,
  'audience': {
    'kind': 'group',
    'session_year_id': 2,
    'target_id': 9,
    'parents': true,
  },
  'channels': ['in_app'],
  'scheduled_local': null,
  'requires_approval': true,
  'submitted_at': state == 'draft' ? null : '2026-10-24T09:00:00Z',
  'approved_at': state == 'approved' || state == 'sent'
      ? '2026-10-24T09:01:00Z'
      : null,
  'decision_reason': null,
  'blocked_code': null,
  'withdrawal_reason': null,
  'is_author': author,
  'can_review': reviewer,
  'recipient_count': state == 'sent' ? 3 : 0,
  'read_count': 0,
};
Map<String, dynamic> messagePreviewFixture({int revision = 1}) => {
  'id': 11,
  'revision': revision,
  'recipient_count': 3,
  'requires_approval': true,
  'fingerprint': 'a' * 64,
  'html': '<p>Private school text.</p>',
  'channels': ['in_app'],
  'scheduled_at': null,
  'timezone': 'Africa/Johannesburg',
};
