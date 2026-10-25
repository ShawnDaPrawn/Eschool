import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

import 'facilitator_fixture.dart';

Map<String, dynamic> riskConfigurationFixture() => {
  'session_year_id': 7,
  'today': '2026-10-25',
  'can_manage': false,
  'can_read_signals': true,
  'grade_ids': [],
  'settings': {
    'academic_threshold_bp': 6500,
    'minimum_coverage_bp': 2500,
    'absence_threshold_bp': 1000,
    'minimum_registers': 10,
    'revision': 0,
    'rule_version': 'observed-counts-v1',
  },
  'years': [
    {'id': 7, 'name': '2026'},
  ],
  'groups': [],
};
Map<String, dynamic> interventionFixture() => {
  'id': 9,
  'learner_id': 11,
  'session_year_id': 7,
  'grade_level_id': 5,
  'owner_id': 12,
  'owner_authority': 'professional',
  'started_on': '2026-10-25',
  'review_on': '2026-10-30',
  'state': 'active',
  'revision': 1,
};

class RiskStaffFixture {
  bool granted = true, available = true;
  late final FakeApi api;
  Map<String, dynamic> profile() {
    final row = staffProfileFixture();
    row['roles'] = ['Counsellor'];
    row['features'] = {
      'groups': false,
      'messages': false,
      'counselling': true,
      'support_plans': true,
      'learner_notes': true,
      'risk_interventions': available,
    };
    return row;
  }

  Map<String, dynamic> ok(dynamic data) => {'error': false, 'data': data};
  Map<String, Object> routes() => {
    'GET v2/growth/risk/configuration': (RequestOptions _) => granted
        ? ok(riskConfigurationFixture())
        : const FakeResponse({'message': 'Scope withdrawn'}, status: 403),
    'GET v2/growth/risk/interventions': (RequestOptions _) => granted
        ? ok({
            'session_year_id': 7,
            'page': 1,
            'last_page': 1,
            'interventions': [
              {
                'id': 9,
                'learner_id': 11,
                'learner_name': 'Synthetic learner',
                'owner_id': 12,
                'owner_name': 'Synthetic owner',
                'grade_level_id': 5,
                'review_on': '2026-10-30',
                'state': 'active',
                'revision': 1,
                'can_read_body': true,
              },
            ],
          })
        : const FakeResponse({'message': 'Scope withdrawn'}, status: 403),
    'GET v2/growth/risk/learners': ok({
      'session_year_id': 7,
      'page': 1,
      'last_page': 1,
      'learners': [],
    }),
    'GET v2/growth/risk/interventions/9': (RequestOptions _) => granted
        ? ok({
            ...interventionFixture(),
            'action': 'Private synthetic follow-up action',
            'outcome': null,
          })
        : const FakeResponse({'message': 'Scope withdrawn'}, status: 403),
    'GET v2/growth/risk/interventions/9/history': ok({'intervention_id': 9, 'versions': []}),
  };
  RiskStaffFixture() {
    final reply = staffReplyFixture();
    (reply['data'] as Map)['profile'] = profile();
    api = staffApi(signIn: reply, session: (RequestOptions _) => ok(profile()));
    api.routes.addAll(routes());
  }
}
