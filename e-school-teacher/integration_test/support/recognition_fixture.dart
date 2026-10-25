import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

import 'facilitator_fixture.dart';

class RecognitionStaffFixture {
  bool granted = true;
  Map<String, dynamic> ok(Object data) => {'error': false, 'data': data};
  Map<String, dynamic> profile() => {
    ...staffProfileFixture(),
    'features': {
      'groups': true,
      'messages': true,
      'house_points': granted,
      'awards': granted,
    },
  };
  Map<String, dynamic> access(String module) => {
    'module': module,
    'school_id': 1,
    'account_id': 12,
    'session_year_id': 7,
    'today': '2026-10-25',
    'family': false,
    'can_write': false,
    'manager': false,
    'grade_ids': [],
  };
  late final FakeApi api;
  RecognitionStaffFixture() {
    final reply = staffReplyFixture();
    (reply['data'] as Map)['profile'] = profile();
    api = staffApi(signIn: reply, session: (RequestOptions _) => ok(profile()));
    for (final module in ['house_points', 'awards']) {
      api.routes['GET v2/recognition/$module/access'] = (RequestOptions _) =>
          ok(access(module));
      api.routes['GET v2/recognition/$module/configuration'] =
          (RequestOptions _) => ok({
            'access': access(module),
            'settings': {
              'revision': 1,
              'daily_staff_cap': 50,
              'public_points_allowed': false,
              'principal_approval_required': false,
            },
            'years': [
              {
                'id': 7,
                'name': '2026',
                'starts_on': '2026-01-01',
                'ends_on': '2026-12-31',
              },
            ],
            'grades': [],
            'point_categories': [],
          });
      api.routes['GET v2/recognition/$module/learners'] = ok({
        'items': [
          {
            'id': 2,
            'name': 'Synthetic learner',
            'grade_level_id': 6,
            'session_year_id': 7,
          },
        ],
        'next_after': null,
      });
    }
    api.routes['GET v2/recognition/house_points/counters'] = ok({
      'session_year_id': 7,
      'revision': 1,
      'items': [
        {
          'id': 3,
          'name': 'Mountain',
          'emblem': 'mountain',
          'primary_colour': '#315b8c',
          'secondary_colour': '#ffffff',
          'active': true,
          'revision': 1,
          'points': 5,
        },
      ],
    });
    api.routes['GET v2/recognition/house_points/learners/2/points'] = ok({
      'items': [
        {
          'id': 1,
          'house_id': 3,
          'house_name': 'Mountain',
          'category_name': 'Helping',
          'points': 5,
          'reason': 'Private synthetic point evidence',
          'on_date': '2026-10-25',
          'public': false,
          'reverses_id': null,
        },
      ],
      'next_before': null,
    });
    api.routes['GET v2/recognition/awards/learners/2/awards'] = ok({
      'items': [
        {
          'id': 9,
          'session_year_id': 7,
          'title': 'Synthetic service award',
          'criteria': 'Service achievement',
          'presented_on': '2026-10-25',
          'event': 'Assembly',
          'outside_organisation': null,
          'source_kind': 'catalogue',
        },
      ],
      'next_before': null,
    });
  }
}
