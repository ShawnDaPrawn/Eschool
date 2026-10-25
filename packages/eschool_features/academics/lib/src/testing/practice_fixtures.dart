import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

/// Synthetic protocol fixture; no model provider or real learner records.
class PracticeProtocolFixture {
  PracticeProtocolFixture({this.staff = true, this.reader = 12});
  final bool staff;
  final int reader;
  bool granted = true, reviewed = false;
  Map<String, dynamic>? lastApproval;
  Map<String, dynamic> question = {
    'kind': 'number',
    'prompt': 'One half as a decimal?',
    'explanation': 'Divide one by two to get 0.5.',
    'options': <Object>[],
    'answers': ['0.5'],
  };
  Map<String, dynamic> ok(Object? data) => {'error': false, 'data': data};
  Map<String, dynamic> daily() {
    final now = DateTime.now().toUtc();
    return {
      'id': 10,
      'school_id': 1,
      'reader_id': reader,
      'learner_id': 11,
      'group_id': 7,
      'practice_on': now.toIso8601String().substring(0, 10),
      'server_time': now.toIso8601String(),
      'offline_until': now.add(const Duration(days: 1)).toIso8601String(),
      'estimated_seconds': 120,
      'completed': reviewed,
      'streak_days': reviewed ? 1 : 0,
      'items': [
        {
          'id': 20,
          'item_id': 21,
          'topic_id': 22,
          'source_version_id': 23,
          'estimated_seconds': 120,
          'definition': question,
          'review': reviewed ? review() : null,
        },
      ],
    };
  }

  Map<String, dynamic> review() => {
    'id': 1,
    'entry_id': 20,
    'correct': true,
    'reviewed_on': DateTime.now().toUtc().toIso8601String().substring(0, 10),
    'schedule': {
      'version': 'synthetic-scheduler',
      'rating': 3,
      'difficulty': 4,
      'stability_days': 2,
      'retrievability_before': null,
      'interval_days': 2,
    },
  };
  Map<String, Object> routes() => {
    'GET v2/learning/practice/access': (RequestOptions _) => granted
        ? ok({
            'reader_id': reader,
            'learner_id': staff ? null : 11,
            'practice_on': DateTime.now().toUtc().toIso8601String().substring(
              0,
              10,
            ),
            'writable': true,
            'staff_groups': staff
                ? [
                    {'id': 7, 'name': 'Grade 7', 'subject_name': 'Mathematics'},
                  ]
                : <Object>[],
            'learner_groups': staff
                ? <Object>[]
                : [
                    {
                      'id': 7,
                      'name': 'Grade 7',
                      'subject_name': 'Mathematics',
                      'set_id': 10,
                    },
                  ],
          })
        : const FakeResponse({
            'message': 'Practice access withdrawn',
          }, status: 403),
    'GET v2/learning/practice/groups/7/choices': ok({
      'group_id': 7,
      'topics': [
        {'id': 22, 'name': 'Fractions'},
      ],
      'sources': [
        {'id': 23, 'name': 'Approved fractions'},
      ],
      'writable': true,
    }),
    'GET v2/learning/practice/groups/7/bank': (RequestOptions _) => ok({
      'page': 1,
      'last_page': 1,
      'items': [
        {
          'id': 21,
          'topic_id': 22,
          'revision': lastApproval == null ? 1 : 2,
          'active': true,
          'content_version_id': 23,
          'estimated_seconds': 120,
          'definition': question,
        },
      ],
    }),
    'POST v2/learning/practice/groups/7/bank/21': (RequestOptions request) {
      lastApproval = Map<String, dynamic>.from(request.data as Map);
      question = Map<String, dynamic>.from(lastApproval!['definition'] as Map);
      return ok({'id': 21, 'version_id': 30, 'revision': 2, 'active': true});
    },
    'GET v2/learning/practice/learners/11/daily-sets/10': (RequestOptions _) =>
        granted
        ? ok(daily())
        : const FakeResponse({
            'message': 'Practice access withdrawn',
          }, status: 403),
    'POST v2/learning/practice/learners/11/daily-sets/10/entries/20/review':
        (RequestOptions _) {
          reviewed = true;
          return ok(review());
        },
  };
}
