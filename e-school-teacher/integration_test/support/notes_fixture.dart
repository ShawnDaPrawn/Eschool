import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

import 'facilitator_fixture.dart';

class NotesStaffFixture {
  bool granted = true, available = true;
  late final FakeApi api;
  Map<String, dynamic> profile() {
    final row = staffProfileFixture();
    row['name'] = 'Synthetic Counsellor';
    row['roles'] = ['Counsellor'];
    row['features'] = {
      'groups': false,
      'messages': false,
      'counselling': false,
      'support_plans': true,
      'learner_notes': available,
    };
    return row;
  }

  final metadata = <String, dynamic>{
    'id': 9,
    'learner_id': 11,
    'subject_id': null,
    'session_year_id': 7,
    'author_id': 12,
    'author_name': 'Synthetic Counsellor',
    'visibility': 'counselling',
    'revision': 1,
    'archived': false,
    'updated_at': '2026-10-25T01:00:00Z',
  };
  NotesStaffFixture() {
    final reply = staffReplyFixture();
    (reply['data'] as Map)['profile'] = profile();
    api = staffApi(
      signIn: reply,
      session: (RequestOptions _) => {'error': false, 'data': profile()},
    );
    api.routes.addAll({
      'GET v2/growth/progress/configuration': {
        'error': false,
        'data': {
          'session_year_id': 7,
          'can_manage_bands': false,
          'can_assign_counselling': false,
          'can_read_caseload': true,
          'settings': {
            'high_bp': 8000,
            'secure_bp': 6500,
            'minimum_coverage_bp': 2500,
            'revision': 0,
          },
          'restricted_staff': [],
          'years': [
            {'id': 7, 'name': '2026'},
          ],
          'terms': [],
          'groups': [],
        },
      },
      'GET v2/growth/progress/caseload': (RequestOptions _) => {
        'error': false,
        'data': {
          'current_page': 1,
          'last_page': 1,
          'total': granted ? 1 : 0,
          'learners': [
            if (granted)
              {'learner_id': 11, 'display_name': 'Synthetic learner'},
          ],
        },
      },
      'GET v2/growth/progress/learners/11/notes': (RequestOptions _) => granted
          ? {
              'error': false,
              'data': {
                'notes': [metadata],
                'next_after': null,
              },
            }
          : const FakeResponse({'message': 'Access withdrawn'}, status: 403),
      'GET v2/growth/progress/notes/9': (RequestOptions _) => granted
          ? {
              'error': false,
              'data': {
                ...metadata,
                'body': 'Private synthetic clinical observation',
              },
            }
          : const FakeResponse({'message': 'Access withdrawn'}, status: 403),
    });
  }
}
