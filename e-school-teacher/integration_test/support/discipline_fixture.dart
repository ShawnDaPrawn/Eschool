import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

import 'facilitator_fixture.dart';

Map<String, dynamic> disciplineDefinitionFixture() => {
  'counter_scope': 'same_infraction',
  'reset': 'school_year',
  'rolling_days': null,
  'levels': [
    {
      'code': 'notice',
      'name': 'School notice',
      'threshold': 1,
      'kind': 'sanction',
      'sanctions': ['notice'],
    },
    {
      'code': 'hearing',
      'name': 'Principal hearing',
      'threshold': 2,
      'kind': 'hearing',
      'sanctions': <String>[],
    },
  ],
  'sanctions': [
    {'code': 'notice', 'name': 'School notice', 'kind': 'notice'},
  ],
  'infractions': [
    {'code': 'late', 'name': 'Observed school fact', 'base_level': 'notice'},
  ],
};
Map<String, dynamic> disciplineRecordFixture() => {
  'id': 9,
  'learner_id': 11,
  'learner_name': 'Synthetic learner',
  'session_year_id': 7,
  'grade_level_id': 5,
  'recorded_by': 12,
  'occurred_on': '2026-10-25',
  'infraction_code': 'late',
  'level_code': 'notice',
  'sanction_code': 'notice',
  'status': 'confirmed',
  'hearing_decision': null,
  'revision': 1,
  'published': true,
};

class DisciplineStaffFixture {
  bool granted = true, available = true;
  late final FakeApi api;
  Map<String, dynamic> ok(Object? data) => {'error': false, 'data': data};
  Map<String, dynamic> profile() {
    final row = staffProfileFixture();
    row['roles'] = ['Teacher'];
    row['features'] = {
      'discipline': available,
      'groups': false,
      'messages': false,
      'counselling': false,
      'support_plans': false,
      'learner_notes': false,
      'risk_interventions': false,
    };
    return row;
  }

  Map<String, Object> routes() => {
    'GET v2/discipline/configuration': (RequestOptions _) => granted
        ? ok({
            'session_year_id': 7,
            'today': '2026-10-25',
            'writable': true,
            'can_manage': false,
            'can_hear': false,
            'grade_ids': <int>[],
            'grades': <Object>[],
            'years': [
              {'id': 7, 'name': '2026'},
            ],
            'framework': {
              'id': 1,
              'session_year_id': 7,
              'title': 'Synthetic school framework',
              'revision': 1,
              'definition': disciplineDefinitionFixture(),
            },
          })
        : const FakeResponse({'message': 'Scope withdrawn'}, status: 403),
    'GET v2/discipline/records': ok({
      'session_year_id': 7,
      'page': 1,
      'last_page': 1,
      'records': [disciplineRecordFixture()],
    }),
    'GET v2/discipline/learners': ok({
      'session_year_id': 7,
      'page': 1,
      'last_page': 1,
      'learners': <Object>[],
    }),
    'GET v2/discipline/detentions': ok({
      'session_year_id': 7,
      'page': 1,
      'last_page': 1,
      'sessions': <Object>[],
    }),
    'GET v2/discipline/records/9': (RequestOptions _) => granted
        ? ok({
            ...disciplineRecordFixture(),
            'description': 'Published synthetic school fact',
            'staff_note': 'Private synthetic discipline follow-up',
            'decision_reason': null,
            'family_reason': null,
            'basis': {
              'definition': disciplineDefinitionFixture(),
              'infraction_name': 'Observed school fact',
              'window_start': '2026-01-01',
              'occurrence_count': 1,
            },
          })
        : const FakeResponse({'message': 'Scope withdrawn'}, status: 403),
    'GET v2/discipline/records/9/history': ok({
      'record_id': 9,
      'versions': <Object>[],
    }),
  };
  DisciplineStaffFixture() {
    final reply = staffReplyFixture();
    (reply['data'] as Map)['profile'] = profile();
    api = staffApi(signIn: reply, session: (RequestOptions _) => ok(profile()));
    api.routes.addAll(routes());
  }
}
