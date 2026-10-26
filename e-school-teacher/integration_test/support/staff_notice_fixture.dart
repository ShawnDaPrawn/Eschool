import 'package:dio/dio.dart';

import 'notes_fixture.dart';
import 'discipline_fixture.dart';

class ProgressInboxFixture extends NotesStaffFixture {
  bool inboxGranted = true, marked = false;
  bool teacherRole;
  @override
  Map<String, dynamic> profile() {
    final row = super.profile();
    row['name'] = 'Synthetic allocated Teacher';
    row['roles'] = teacherRole ? ['Teacher'] : ['Counsellor'];
    row['features'] = {'private_notices': inboxGranted};
    return row;
  }

  ProgressInboxFixture({this.teacherRole = true}) {
    metadata['visibility'] = 'staff';
    metadata['author_name'] = 'Synthetic allocated Teacher';
    api.routes.addAll({
      'GET v2/staff/private-notices': (RequestOptions _) => {
        'error': false,
        'data': {
          'school_id': 1,
          'reader_id': 12,
          'writable': true,
          'page': 1,
          'last_page': 1,
          'unread_count': marked ? 0 : 1,
          'items': [
            {
              'id': 70,
              'feature': 'progress',
              'title': 'Learning progress updated',
              'message':
                  'Open Learning progress to review an available update.',
              'writable': true,
              'read_at': marked ? '2026-02-10T09:00:00Z' : null,
            },
          ],
        },
      },
      'POST v2/staff/private-notices/70/read': (RequestOptions _) {
        marked = true;
        return {
          'error': false,
          'data': {
            'id': 70,
            'school_id': 1,
            'reader_id': 12,
            'read_at': '2026-02-10T09:00:00Z',
          },
        };
      },
      'GET v2/growth/progress/configuration': {
        'error': false,
        'data': {
          'session_year_id': 7,
          'can_manage_bands': false,
          'can_assign_counselling': false,
          'can_read_caseload': false,
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
          'terms': [
            {'id': 8, 'name': 'Term 1'},
          ],
          'groups': [
            {
              'id': 9,
              'name': 'Mathematics A',
              'subject_id': 4,
              'subject_name': 'Mathematics',
              'grade_name': 'Grade 7',
              'session_year_id': 7,
              'term_ids': [8],
            },
          ],
        },
      },
      'GET v2/growth/progress/groups/9/learners': {
        'error': false,
        'data': {
          'group_id': 9,
          'current_page': 1,
          'last_page': 1,
          'total': 1,
          'learners': [
            {'learner_id': 11, 'display_name': 'Asha Synthetic'},
          ],
        },
      },
      'GET v2/growth/progress/groups/9/learners/11': {
        'error': false,
        'data': {
          'audience': 'staff',
          'learner_id': 11,
          'subject_id': 4,
          'session_year_id': 7,
          'term_id': 8,
          'snapshot_id': 30,
          'provisional': true,
          'band': 'secure',
          'percentage_bp': 7000,
          'coverage_bp': 5000,
          'alerts_eligible': true,
          'settings_revision': 0,
        },
      },
      'GET v2/growth/progress/notes/9': {
        'error': false,
        'data': {...metadata, 'body': 'Private allocated academic observation'},
      },
    });
  }
}

class DisciplineInboxFixture extends DisciplineStaffFixture {
  bool marked = false;

  @override
  Map<String, dynamic> profile() {
    final row = super.profile();
    (row['features'] as Map<String, dynamic>)['private_notices'] = true;
    return row;
  }

  DisciplineInboxFixture() {
    api.routes.addAll({
      'GET v2/staff/private-notices': (RequestOptions _) => ok({
        'school_id': 1,
        'reader_id': 12,
        'writable': true,
        'page': 1,
        'last_page': 1,
        'unread_count': marked ? 0 : 1,
        'items': [
          {
            'id': 71,
            'feature': 'discipline',
            'title': 'Discipline follow-up updated',
            'message':
                'Open School discipline to review an available follow-up.',
            'writable': true,
            'read_at': marked ? '2026-10-25T09:00:00Z' : null,
          },
        ],
      }),
      'POST v2/staff/private-notices/71/read': (RequestOptions _) {
        marked = true;
        return ok({
          'id': 71,
          'school_id': 1,
          'reader_id': 12,
          'read_at': '2026-10-25T09:00:00Z',
        });
      },
    });
  }
}
