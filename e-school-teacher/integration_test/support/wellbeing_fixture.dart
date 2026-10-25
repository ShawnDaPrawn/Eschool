import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

import 'facilitator_fixture.dart';

class ClinicalStaffFixture {
  bool granted = true;
  String body = 'Private clinical browser baseline';
  int revision = 1;
  late final FakeApi api;
  Map<String, dynamic> profile() {
    final p = staffProfileFixture();
    p['name'] = 'Synthetic Counsellor';
    p['roles'] = ['Counsellor'];
    p['features'] = {
      'groups': false,
      'messages': false,
      'counselling': true,
      'support_plans': false,
      'learner_notes': false,
    };
    return p;
  }

  Map<String, dynamic> result(Object? data) => {
    'error': false,
    'message': 'Private result',
    'code': 200,
    'data': data,
  };
  Map<String, dynamic> record() => {
    'id': 10,
    'learner_id': 3,
    'learner_name': 'Synthetic Case Learner',
    'incident_report_id': null,
    'status': 'open',
    'opened_at': '2026-02-10T08:00:00Z',
    'revision': revision,
    'has_older': false,
    'next_before': null,
    'entries': [
      {
        'id': 1,
        'author_id': 12,
        'author_name': 'Synthetic Counsellor',
        'supersedes_id': null,
        'kind': 'note',
        'body': body,
        'happened_at': '2026-02-10T08:00:00Z',
        'created_at': '2026-02-10T08:00:00Z',
      },
    ],
  };
  ClinicalStaffFixture() {
    api = FakeApi(
      routes: {
        'POST v2/facilitator/sign-in': (RequestOptions _) => {
          'error': false,
          'code': null,
          'data': {
            'authenticated': true,
            'token': '12|synthetic-clinical-token',
            'profile': profile(),
            'password_change_required': false,
            'documents': [],
          },
        },
        'GET v2/facilitator/session': (RequestOptions _) => result(profile()),
        'POST v2/facilitator/sign-out': result(null),
        'GET v2/wellbeing/access': (RequestOptions _) => result({
          'can_configure': false,
          'professional': true,
          'can_triage': false,
          'can_casework': granted,
          'can_write': true,
          'today': '2026-02-10',
          'school_slug': 'jacaranda',
          'emergency_guidance': 'Speak to a trusted adult if unsafe.',
          'timezone': 'Africa/Johannesburg',
        }),
        'GET v2/wellbeing/cases': (RequestOptions _) => result({
          'items': [
            if (granted)
              {
                'id': 10,
                'learner_id': 3,
                'learner_name': 'Synthetic Case Learner',
                'status': 'open',
                'opened_at': '2026-02-10T08:00:00Z',
                'revision': revision,
              },
          ],
          'page': 1,
          'last_page': 1,
        }),
        'GET v2/wellbeing/cases/10': (RequestOptions _) => granted
            ? result(record())
            : FakeResponse({
                'error': true,
                'message': 'Not found',
                'code': 404,
              }, status: 404),
        'GET v2/wellbeing/cases/10/professionals': result({
          'items': [
            {'id': 12, 'name': 'Synthetic Counsellor'},
          ],
          'page': 1,
          'last_page': 1,
          'selected_available': true,
        }),
        'GET v2/wellbeing/cases/10/incident-choices': result({
          'items': [],
          'page': 1,
          'last_page': 1,
        }),
        'GET v2/wellbeing/cases/10/appointments': result([]),
        'POST v2/wellbeing/cases/10/entries': (RequestOptions request) {
          final input = Map<String, dynamic>.from(
            request.data is String
                ? jsonDecode(request.data)
                : request.data as Map,
          );
          body = input['body'];
          revision++;
          return result({
            'case_revision': revision,
            'entry': record()['entries'][0],
          });
        },
      },
    );
  }
}
