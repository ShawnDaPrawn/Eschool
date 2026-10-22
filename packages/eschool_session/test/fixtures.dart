/// A teacher's `/me/context` from a Phase 0 server (the contract in
/// admin-panel/openapi.yaml, `Context`).
const Map<String, dynamic> contextJson = {
  'user': {
    'id': 12,
    'first_name': 'Tara',
    'last_name': 'Naidoo',
    'name': 'Tara Naidoo',
    'email': 'tara@school.test',
    'image': 'https://school.test/tara.jpg',
    'roles': ['Teacher'],
  },
  'schools': [
    {
      'school': {
        'id': 1,
        'name': 'Demo School',
        'short_name': 'DS',
        'crest_url': 'https://school.test/logo.png',
      },
      'roles': ['Teacher'],
    },
  ],
  'current_school': {
    'id': 1,
    'name': 'Demo School',
    'short_name': 'DS',
    'crest_url': 'https://school.test/logo.png',
    'tagline': null,
    'branding': {'theme_color': null, 'secondary_color': null},
    'timezone': 'Africa/Johannesburg',
    'session_year': null,
  },
  'modules': <Object>[],
  'notification_rules': <Object>[],
  'feature_flags': <String, Object>{},
  'sign_in_methods': ['password'],
  'children': <Object>[],
};

/// A parent's answer from a Phase 0 server, as
/// packages/eschool_api/test/fixtures/me_context_parent.json has it.
const Map<String, dynamic> phase0ParentJson = {
  'user': {
    'id': 34,
    'first_name': 'Farid',
    'last_name': 'Parent',
    'name': 'Farid Parent',
    'email': 'farid@example.com',
    'image': null,
    'roles': ['Parent'],
  },
  'schools': [
    {
      'school': {
        'id': 1,
        'name': 'Northcliff High',
        'short_name': 'NHS',
        'crest_url': 'https://admin.navigateed.co.za/storage/logos/crest.png',
      },
      'roles': ['Parent'],
    },
  ],
  'current_school': {
    'id': 1,
    'name': 'Northcliff High',
    'short_name': 'NHS',
    'crest_url': 'https://admin.navigateed.co.za/storage/logos/crest.png',
    'tagline': 'Learn and grow',
    'branding': {'theme_color': '#22577A', 'secondary_color': null},
    'timezone': 'Africa/Johannesburg',
    'session_year': {
      'id': 3,
      'name': '2026',
      'start_date': '2026-01-14',
      'end_date': '2026-12-09',
      'terms': [
        {
          'id': 10,
          'name': 'Term 4',
          'start_date': '2026-10-06',
          'end_date': '2026-12-09',
          'current': true,
        },
      ],
    },
  },
  'modules': <Object>[],
  'notification_rules': <Object>[],
  'feature_flags': <String, Object>{},
  'sign_in_methods': ['password'],
  'children': [
    {
      'id': 21,
      'user_id': 40,
      'first_name': 'Asha',
      'last_name': 'Demo',
      'name': 'Asha Demo',
      'image': 'https://admin.navigateed.co.za/storage/users/asha.jpg',
      'school': {
        'id': 1,
        'name': 'Northcliff High',
        'short_name': 'NHS',
        'crest_url': 'https://admin.navigateed.co.za/storage/logos/crest.png',
      },
    },
  ],
};

/// A guardian with a child at each of two schools, as a Phase 1 server
/// answers: the Phase 0 contract plus the child's `student_number`,
/// `approved` and `homeroom` (engineering/phases/phase-1.md, "Response
/// shapes").
const Map<String, dynamic> phase1GuardianJson = {
  'user': {
    'id': 34,
    'first_name': 'Farid',
    'last_name': 'Parent',
    'name': 'Farid Parent',
    'email': 'farid@example.com',
    'image': null,
    'roles': ['Parent'],
  },
  'schools': [
    {
      'school': {
        'id': 1,
        'name': 'Northcliff High',
        'short_name': 'NHS',
        'crest_url': 'https://school.test/nhs.png',
      },
      'roles': ['Parent'],
    },
    {
      'school': {
        'id': 2,
        'name': 'Riverside Primary',
        'short_name': 'RP',
        'crest_url': null,
      },
      'roles': ['Parent'],
    },
  ],
  'current_school': {
    'id': 2,
    'name': 'Riverside Primary',
    'short_name': 'RP',
    'crest_url': null,
    'tagline': null,
    'branding': {'theme_color': null, 'secondary_color': null},
    'timezone': 'Africa/Johannesburg',
    'session_year': null,
  },
  'modules': [
    {'key': 'chat', 'read_only': false},
    {'key': 'fees', 'read_only': true},
  ],
  'notification_rules': [
    {'type': 'comms.notice', 'channel': 'email', 'mode': 'default_on'},
    {'type': 'approval.requested', 'channel': 'push', 'mode': 'mandatory'},
  ],
  'feature_flags': {'passkeys': true},
  'sign_in_methods': ['password', 'passkey'],
  'children': [
    {
      'id': 21,
      'user_id': 40,
      'first_name': 'Asha',
      'last_name': 'Demo',
      'name': 'Asha Demo',
      'image': 'https://school.test/asha.png',
      'school': {
        'id': 1,
        'name': 'Northcliff High',
        'short_name': 'NHS',
        'crest_url': 'https://school.test/nhs.png',
      },
      'student_number': 'NE-2026-00021',
      'approved': true,
      'homeroom': 'Grade 7 A',
    },
    {
      'id': 23,
      'user_id': 42,
      'first_name': 'Cara',
      'last_name': 'Demo',
      'name': 'Cara Demo',
      'image': null,
      'school': {
        'id': 2,
        'name': 'Riverside Primary',
        'short_name': 'RP',
        'crest_url': null,
      },
      'student_number': null,
      'approved': false,
      'homeroom': null,
    },
  ],
  'approval': null,
  'a_key_from_a_later_phase': {'anything': true},
};
