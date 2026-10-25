import 'package:dio/dio.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/testing.dart';

/// Synthetic shape of the contract-tested grade-head HTTP responses.
class MyGradeSmokeFixture {
  bool granted = true, saved = false;
  Map<String, Object?> ok(Object? data) => {'error': false, 'data': data};
  Object private(Object? data) => granted
      ? ok(data)
      : const FakeResponse({
          'error': true,
          'message': 'Access withdrawn',
        }, status: 403);
  Map<String, dynamic> access() => {
    'school_id': 1,
    'reader_id': 12,
    'session_year_id': 7,
    'today': '2026-10-25',
    'writable': true,
    'grades': [
      {'id': 5, 'name': 'Grade 5'},
    ],
  };
  Map<String, dynamic> dashboard() => {
    'school_id': 1,
    'reader_id': 12,
    'session_year_id': 7,
    'grade_level_id': 5,
    'on_date': '2026-10-25',
    'writable': true,
    'page': 1,
    'last_page': 1,
    'total': 1,
    'risk_available': false,
    'learners': [
      {
        'learner_id': 11,
        'name': 'Synthetic grade learner',
        'class_section_id': 3,
        'register_id': 4,
        'attendance_state': saved ? 'present' : 'absent',
        'signals': [],
      },
    ],
    'discipline': {
      'available': true,
      'items': [
        {
          'id': 9,
          'learner_id': 11,
          'learner_name': 'Synthetic discipline queue',
          'revision': 1,
          'occurred_on': '2026-10-25',
          'status': 'pending_review',
        },
      ],
      'page': 1,
      'last_page': 1,
      'total': 1,
    },
    'awards': {'available': false, 'items': []},
    'actions': {'registers': true, 'messages': false, 'interventions': false},
  };
  Map<String, dynamic> matrix() => {
    ...registerMatrixFixture(
      count: 1,
      revision: saved ? 1 : 0,
      state: saved ? 'present' : 'unmarked',
      entryRevision: saved ? 1 : 0,
    ),
    'session_year_id': 7,
    'on_date': '2026-10-25',
  };
  Map<String, Object> routes() => {
    'GET v2/my-grade/access': (RequestOptions _) => private(access()),
    'GET v2/my-grade/grades/5': (RequestOptions _) => private(dashboard()),
    'GET v2/academics/registers/4': (RequestOptions _) => private(matrix()),
    'POST v2/academics/registers/4/sync': (RequestOptions _) {
      if (!granted) return private(null);
      saved = true;
      return ok({
        'register_id': 4,
        'revision': 1,
        'entries': [
          {'learner_id': 11, 'revision': 1},
        ],
      });
    },
  };
}
