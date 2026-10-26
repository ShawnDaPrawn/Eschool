import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

/// Synthetic protocol responses; no provider, licence or camera qualification.
class ExamCoachSmokeFixture {
  ExamCoachSmokeFixture({required this.reader, this.staff = true})
    : hasPaper = !staff,
      state = staff ? 'draft' : 'published';
  static const version = '138314b5-9394-408c-9cb8-405c4f2b23ac';
  static const answerId = 'bd1d21bc-4a71-4125-88e6-a32596c79916';
  static const operation = 'de100bf4-c237-4d13-ae2c-21da6c1bda5a';
  static const planId = 'fd2047ce-1d76-43de-9d37-d7c0286e782b';
  static const pixels =
      'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAHklEQVR4nGNQSFjwn5qYYdTAUQNHDRw1cNTAkWogAOkyUJ4SO0XgAAAAAElFTkSuQmCC';
  final int reader;
  final bool staff;
  bool permitted = true,
      hasPaper,
      loseDraft = true,
      loseReview = true,
      uploaded = false,
      reviewed = false,
      planCreated = false;
  String state, answerState = 'prepared';
  String? uploadOperation;
  int revision = 1, paperEffects = 0, reviewEffects = 0;
  final writes = <Map<String, dynamic>>[], reviews = <Map<String, dynamic>>[];
  final receipts = <String, Map<String, dynamic>>{};
  Map<String, dynamic>? definition;
  Map<String, dynamic> group() => {
    'id': 3,
    'name': 'Synthetic maths group',
    'subject_name': 'Mathematics',
  };
  Object result(Object? body) => permitted
      ? {'error': false, 'message': 'Saved', 'data': body, 'code': 200}
      : const FakeResponse({
          'error': true,
          'message': 'Access withdrawn',
        }, status: 403);
  Map<String, dynamic> body(RequestOptions r) => Map<String, dynamic>.from(
    r.data is String ? jsonDecode(r.data as String) as Map : r.data as Map,
  );
  Map<String, dynamic> paper() => {
    'id': 2,
    'version_id': version,
    'number': 1,
    'title': definition?['title'] ?? 'Synthetic licensed paper',
    'framework': 'school',
    'paper_year': 2025,
    'paper_code': 'MATH-P1',
    'state': state,
    'licence_kind': 'permission',
    'licence_reference': 'Synthetic permission',
    'questions': [
      for (final q
          in (definition?['questions'] as List? ??
              [
                {
                  'number': '1',
                  'prompt': 'Explain one half.',
                  'memo': 'PRIVATE-STAFF-MEMO-CANARY',
                  'max_units': 1000,
                  'topic_ids': [6],
                },
              ]))
        {
          for (final k in [
            'number',
            'prompt',
            'max_units',
            'topic_ids',
            if (staff) 'memo',
          ])
            k: q[k],
        },
    ],
    if (staff) ...{
      'revision': revision,
      'group_ids': [3],
      'can_edit': true,
      'licence':
          definition?['licence'] ??
          {
            'kind': 'permission',
            'reference': 'Synthetic permission',
            'statement': 'The school obtained synthetic test permission.',
            'confirmed': true,
          },
    },
  };
  Map<String, dynamic> receipt() => {
    'id': 2,
    'version_id': version,
    'revision': revision,
    'state': state,
  };
  Map<String, dynamic> answer() => {
    'id': answerId,
    'operation_id': uploadOperation ?? operation,
    'state': staff ? 'completed' : answerState,
    'failure_code': null,
    'group_id': 3,
    'paper_version_id': version,
    'question_number': '1',
    'revision': reviewed ? 3 : 2,
    'expires_at': DateTime.now()
        .toUtc()
        .add(const Duration(days: 1))
        .toIso8601String(),
    'working': 'Synthetic private working',
    'response': (staff || answerState == 'completed')
        ? {
            'question_token': 'a' * 64,
            'mark_units': 500,
            'confidence_basis_points': 7300,
            'feedback': 'Explain the two equal parts.',
          }
        : null,
    'review': reviewed
        ? {
            'id': operation,
            'mark_units': 750,
            'reason': 'Teacher verified the equal parts.',
            'answer_revision': 3,
          }
        : null,
    'provisional': !reviewed,
  };
  Map<String, Object> routes(FakeApi Function() api) {
    final root = 'v2/exam-coach';
    final base = staff ? '$root/staff/groups/3' : '$root/groups/3';
    return {
      'GET $root/configuration': (RequestOptions _) => result({
        'reader_id': reader,
        'learner_id': staff ? null : 11,
        'staff_groups': staff ? [group()] : [],
        'learner_groups': staff ? [] : [group()],
        'writable': true,
        'photo_feedback_enabled': true,
      }),
      'GET $root/staff/groups/3/choices': (RequestOptions _) => result({
        'group_id': 3,
        'frameworks': ['school'],
        'topics': [
          {'id': 6, 'name': 'Fractions'},
        ],
        'audience_groups': [group()],
        'writable': true,
      }),
      'GET $base/papers': (RequestOptions _) => result({
        'group_id': 3,
        'page': 1,
        'has_more': false,
        'papers': hasPaper
            ? [
                {
                  for (final key in [
                    'id',
                    'version_id',
                    'number',
                    'title',
                    'paper_year',
                    'paper_code',
                    'state',
                    'licence_kind',
                  ])
                    key: paper()[key],
                  'revision': revision,
                  'can_edit': staff,
                },
              ]
            : [],
      }),
      'GET $base/versions/$version': (RequestOptions _) => result(paper()),
      'POST $root/staff/groups/3/papers': (RequestOptions r) {
        if (!permitted) return result(null);
        final input = body(r);
        writes.add(input);
        if (!receipts.containsKey(input['operation_id'])) {
          paperEffects++;
          hasPaper = true;
          definition = input;
          state = 'draft';
          receipts[input['operation_id']] = receipt();
        }
        api().routes['GET $root/staff/groups/3/operations/${input['operation_id']}'] =
            (RequestOptions _) => result({
              'kind': 'paper',
              'operation_id': input['operation_id'],
              'paper': receipts[input['operation_id']],
              'review': null,
            });
        if (loseDraft) {
          loseDraft = false;
          return const FakeResponse({
            'error': true,
            'message': 'Synthetic response lost',
          }, status: 503);
        }
        return result(receipts[input['operation_id']]);
      },
      'POST $root/staff/groups/3/papers/2/versions/$version/publish':
          (RequestOptions r) {
            if (!permitted) return result(null);
            state = 'published';
            revision = 2;
            return result(receipt());
          },
      'GET $root/groups/3/exams': (RequestOptions _) => result({
        'group_id': 3,
        'exams': [
          {
            'id': 4,
            'name': 'Published exam',
            'exam_on': '2026-12-02',
            'schedule_signature': 'b' * 64,
          },
        ],
      }),
      'GET $root/plans': (RequestOptions _) => result({
        'plans': planCreated
            ? [
                {
                  'id': planId,
                  'group_id': 3,
                  'subject_id': 4,
                  'exam_on': '2026-12-02',
                  'papers': [
                    {
                      'id': 2,
                      'version_id': version,
                      'title': 'Synthetic licensed paper',
                      'paper_year': 2025,
                      'paper_code': 'MATH-P1',
                    },
                  ],
                },
              ]
            : [],
        'page': 1,
        'has_more': false,
      }),
      'POST $root/groups/3/plans': (RequestOptions r) {
        planCreated = true;
        return result({'id': planId, 'kind': 'study_plan', 'state': 'created'});
      },
      'GET $root/plans/$planId': (RequestOptions _) => result({
        'id': planId,
        'learner_id': 11,
        'group_id': 3,
        'subject_id': 4,
        'exam_on': '2026-12-02',
        'papers': [],
        'days': [
          {
            'date': '2026-12-01',
            'topic_id': 6,
            'minutes': 20,
            'topic_name': 'Fractions',
          },
        ],
      }),
      'GET ${staff ? base : root}/answers': (RequestOptions _) => result({
        'answers': (staff && state == 'published') || uploaded
            ? [
                {
                  for (final key in [
                    'id',
                    'operation_id',
                    'state',
                    'failure_code',
                    'group_id',
                    'paper_version_id',
                    'question_number',
                    'revision',
                    'expires_at',
                  ])
                    key: answer()[key],
                  if (staff) ...{
                    'learner_id': 11,
                    'learner_name': 'Synthetic learner',
                  },
                },
              ]
            : [],
        'page': 1,
        'has_more': false,
      }),
      'GET ${staff ? base : root}/answers/$answerId': (RequestOptions _) =>
          result(answer()),
      'GET ${staff ? base : root}/answers/$answerId/photo':
          (RequestOptions _) => permitted
          ? FakeBinaryResponse(base64Decode(pixels), contentType: 'image/png')
          : result(null),
      'POST $root/staff/groups/3/answers/$answerId/review': (RequestOptions r) {
        if (!permitted) return result(null);
        final input = body(r);
        reviews.add(input);
        if (!receipts.containsKey(input['operation_id'])) {
          reviewEffects++;
          reviewed = true;
          receipts[input['operation_id']] = {
            'id': input['operation_id'],
            'answer_id': answerId,
            'revision': 3,
            'mark_units': input['mark_units'],
          };
        }
        if (loseReview) {
          loseReview = false;
          return const FakeResponse({
            'error': true,
            'message': 'Synthetic response lost',
          }, status: 503);
        }
        return result(receipts[input['operation_id']]);
      },
      'POST $root/groups/3/versions/$version/answers': (RequestOptions r) {
        final form = r.data as FormData;
        uploadOperation = form.fields
            .firstWhere((f) => f.key == 'operation_id')
            .value;
        uploaded = true;
        api().routes['GET $root/answer-operations/$uploadOperation'] =
            (RequestOptions _) => result(answer());
        return result({
          'id': answerId,
          'operation_id': uploadOperation,
          'state': 'prepared',
        });
      },
      'GET $root/answer-operations/$operation': (RequestOptions _) =>
          result(answer()),
      'POST $root/answers/$answerId/feedback': (RequestOptions _) {
        answerState = 'completed';
        return result(answer());
      },
    };
  }
}
