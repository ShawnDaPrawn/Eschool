/// The answer described in the Build Bible (3.3 and Phase 0 step 8).
const Map<String, dynamic> contextJson = {
  'user': {
    'id': 12,
    'first_name': 'Tara',
    'last_name': 'Naidoo',
    'email': 'tara@school.test',
    'image': 'https://school.test/tara.jpg',
  },
  'roles': ['Teacher'],
  'school': {
    'id': 1,
    'name': 'Demo School',
    'timezone': 'Africa/Johannesburg',
    'branding': {'logo': 'https://school.test/logo.png'},
  },
  'modules': <String>[],
};

/// The answer of a server from the first API v2 release (Phase 0), as
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
    'branding': {'theme_color': '#22577A', 'secondary_color': null},
    'timezone': 'Africa/Johannesburg',
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
      'school': {'id': 1, 'name': 'Northcliff High'},
    },
  ],
};

/// The Phase 1 contract (engineering/phases/phase-1.md, "Response shapes"):
/// a guardian with a child at each of two schools.
const Map<String, dynamic> phase1GuardianJson = {
  'user': {'id': 34, 'name': 'Farid Parent', 'email': 'farid@example.com'},
  'roles': ['Parent'],
  'schools': [
    {
      'id': 1,
      'name': 'Northcliff High',
      'short_name': 'NHS',
      'crest_url': 'https://school.test/nhs.png',
      'roles': ['Parent'],
    },
    {
      'id': 2,
      'name': 'Riverside Primary',
      'short_name': 'RP',
      'crest_url': 'https://school.test/rp.png',
      'roles': ['Parent'],
    },
  ],
  'current_school': {
    'id': 2,
    'name': 'Riverside Primary',
    'short_name': 'RP',
    'crest_url': 'https://school.test/rp.png',
  },
  'children': [
    {
      'id': 21,
      'user_id': 40,
      'name': 'Asha Demo',
      'school_id': 1,
      'school_name': 'Northcliff High',
      'student_number': 'NE-2026-00021',
      'approved': true,
      'avatar_url': 'https://school.test/asha.png',
      'homeroom': 'Grade 7 A',
    },
    {
      'id': 23,
      'user_id': 42,
      'name': 'Cara Demo',
      'school_id': 2,
      'school_name': 'Riverside Primary',
      'student_number': 'NE-2026-00023',
      'approved': false,
      'avatar_url': '',
      'homeroom': 'Grade 3 B',
    },
  ],
  'modules': ['chat', 'online_exams'],
  'branding': {
    'display_name': 'Riverside Primary School',
    'short_name': 'RP',
    'crest_url': 'https://school.test/rp.png',
  },
  'notification_rules': [
    {'type': 'comms.notice', 'channel': 'email', 'mode': 'default_on'},
    {'type': 'approval.requested', 'channel': 'push', 'mode': 'mandatory'},
  ],
  'a_key_from_a_later_phase': {'anything': true},
};
