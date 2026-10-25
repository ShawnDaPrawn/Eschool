import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

import 'facilitator_fixture.dart';

class SupportStaffFixture {
  bool granted = true, hasPlan = false;
  int revision = 1;
  late final FakeApi api;
  Map<String, dynamic> profile() {
    final p = staffProfileFixture();
    p['name'] = 'Synthetic Counsellor';
    p['roles'] = ['Counsellor'];
    p['features'] = {
      'groups': false,
      'messages': false,
      'counselling': false,
      'support_plans': true,
      'learner_notes': false,
    };
    return p;
  }

  Map<String, dynamic> record = {
    'id': 10,
    'learner_id': 3,
    'learner_name': 'Synthetic Learner',
    'revision': 1,
    'state': 'active',
    'review_due_on': null,
    'started_on': '2026-02-10',
    'sias_stage': 'SNA1',
    'needs': 'Private support needs',
    'accommodations': 'Private full plan',
    'reviews_has_more': false,
    'disclosures_has_more': false,
    'reviews': [],
    'disclosures': [],
  };
  SupportStaffFixture() {
    final signIn = staffReplyFixture();
    (signIn['data'] as Map)['profile'] = profile();
    api = staffApi(
      signIn: signIn,
      session: (RequestOptions _) => {'error': false, 'data': profile()},
    );
    api.routes.addAll({
      'GET v2/pastoral/support/access': {
        'error': false,
        'data': {
          'audiences': ['professional'],
          'can_write': true,
          'today': '2026-02-10',
        },
      },
      'GET v2/pastoral/support/configuration/professional':
          (RequestOptions _) => {
            'error': false,
            'data': {
              'audience': 'professional',
              'page': 1,
              'last_page': 1,
              'learners': [
                if (granted) {'id': 3, 'name': 'Synthetic Learner'},
              ],
              'staff': [
                {'id': 4, 'name': 'Named Teacher'},
              ],
              'staff_last_page': 1,
            },
          },
      'GET v2/pastoral/support/cases': (RequestOptions _) => {
        'error': false,
        'data': {
          'page': 1,
          'last_page': 1,
          'items': [
            if (granted && hasPlan)
              {
                'id': 10,
                'learner_id': 3,
                'learner_name': 'Synthetic Learner',
                'revision': revision,
                'state': 'active',
                'review_due_on': null,
              },
          ],
        },
      },
      'POST v2/pastoral/support/plans': (RequestOptions r) {
        if (!granted)
          return const FakeResponse({
            'error': true,
            'message': 'Case assignment withdrawn.',
          }, status: 403);
        final body = Map<String, dynamic>.from(r.data as Map);
        hasPlan = true;
        for (final key in [
          'learner_id',
          'started_on',
          'review_due_on',
          'sias_stage',
          'needs',
          'accommodations',
        ]) {
          if (body.containsKey(key)) record[key] = body[key];
        }
        return {
          'error': false,
          'data': {'id': 10, 'revision': revision, 'kind': 'plan'},
        };
      },
      'GET v2/pastoral/support/plans/10': (RequestOptions _) => granted
          ? {'error': false, 'data': record}
          : const FakeResponse({
              'error': true,
              'message': 'Case assignment withdrawn.',
            }, status: 403),
      'GET v2/pastoral/support/plans/10/reviews': {
        'error': false,
        'data': {'plan_id': 10, 'page': 1, 'last_page': 1, 'items': []},
      },
    });
  }
}
