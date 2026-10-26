import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'smoke.dart' show pumpUntilFound, tapWhenReady;

/// Synthetic responses matching the separately contract-tested staff API.
class TeachingSmokeFixture {
  TeachingSmokeFixture({this.reader = 31});
  final int reader;
  bool granted = true, loseGeneration = true, loseApproval = true;
  int generations = 0, receipts = 0, effects = 0;
  static const draftId = 'bf22c6a8-a013-4629-a5ee-c5c63688681f';
  Map<String, dynamic>? current;
  final reviews = <Map<String, dynamic>>[];
  final _receipts = <String, Map<String, dynamic>>{};
  Map<String, dynamic> scope() => {'school_id': 1, 'reader_id': reader};
  Map<String, dynamic> summary() => {
    ...scope(),
    'id': draftId,
    'operation_id': current!['operation_id'],
    'kind': 'worksheet',
    'state': current!['state'],
    'revision': current!['revision'],
    'group_id': 3,
    'content_version_id': 4,
    'learner_id': null,
    'task_id': null,
  };
  Object answer(Object? data) => granted
      ? {'error': false, 'data': data}
      : const FakeResponse({
          'error': true,
          'message': 'Access withdrawn',
        }, status: 403);
  Map<String, dynamic> body(RequestOptions r) => Map<String, dynamic>.from(
    r.data is String ? jsonDecode(r.data as String) as Map : r.data as Map,
  );

  Map<String, Object> routes(FakeApi Function() api) => {
    'GET v2/teaching-assistant/configuration': (RequestOptions _) => answer({
      ...scope(),
      'today': '2026-10-26',
      'writable': true,
      'groups': [
        {
          'id': 3,
          'name': 'Synthetic maths group',
          'subject_name': 'Mathematics',
        },
      ],
    }),
    'GET v2/teaching-assistant/groups/3': (RequestOptions _) => answer({
      ...scope(),
      'group_id': 3,
      'sources': [
        {'id': 4, 'name': 'Published synthetic fractions'},
      ],
      'topics': [
        {'id': 6, 'name': 'Fractions'},
      ],
      'tasks': [
        {'id': 5, 'name': 'Fractions assessment', 'term_id': 2},
      ],
    }),
    'GET v2/teaching-assistant/groups/3/learners': (RequestOptions _) =>
        answer({
          ...scope(),
          'group_id': 3,
          'items': [
            {'id': 9, 'name': 'Synthetic own learner', 'approved': true},
          ],
          'page': 1,
          'last_page': 1,
        }),
    'GET v2/teaching-assistant/drafts': (RequestOptions _) => answer({
      ...scope(),
      'group_id': 3,
      'items': current == null ? [] : [summary()],
      'page': 1,
      'last_page': 1,
    }),
    'POST v2/teaching-assistant/groups/3/drafts': (RequestOptions r) {
      if (!granted) return answer(null);
      final input = body(r);
      generations++;
      current = {
        ...scope(),
        'id': draftId,
        'operation_id': input['operation_id'],
        'kind': input['kind'],
        'state': 'ready',
        'revision': 1,
        'group_id': 3,
        'content_version_id': input['content_version_id'],
        'learner_id': null,
        'task_id': null,
        'request': {
          for (final key in [
            'kind',
            'language',
            'brief',
            'learner_id',
            'task_id',
            'photo_ids',
            'topic_ids',
          ])
            key: input[key],
        },
        'definition': {
          'title': 'Private browser worksheet',
          'sections': [
            for (final level in ['support', 'core', 'extension'])
              {'level': level, 'body': 'Private $level fractions working'},
          ],
        },
        'question_labels': <dynamic>[],
      };
      api().routes['GET v2/teaching-assistant/operations/${input['operation_id']}'] =
          (RequestOptions _) {
            receipts++;
            return answer(current);
          };
      if (loseGeneration) {
        loseGeneration = false;
        return const FakeResponse({
          'error': true,
          'message': 'Synthetic uncertain result',
        }, status: 503);
      }
      return answer(current);
    },
    'GET v2/teaching-assistant/drafts/$draftId': (RequestOptions _) =>
        answer(current),
    'POST v2/teaching-assistant/drafts/$draftId/review': (RequestOptions r) {
      if (!granted) return answer(null);
      final input = body(r);
      reviews.add(input);
      final operation = input['operation_id'] as String;
      if (_receipts.containsKey(operation)) return answer(_receipts[operation]);
      if (input['expected_revision'] != current!['revision']) {
        return const FakeResponse({
          'error': true,
          'message': 'Stale draft',
        }, status: 409);
      }
      effects++;
      if (input['action'] == 'edit')
        current!['definition'] = input['definition'];
      current!['revision'] = (current!['revision'] as int) + 1;
      current!['state'] = input['action'] == 'approve' ? 'approved' : 'ready';
      final result = {...summary(), 'write_operation_id': operation};
      _receipts[operation] = result;
      if (input['action'] == 'approve' && loseApproval) {
        loseApproval = false;
        return const FakeResponse({
          'error': true,
          'message': 'Synthetic uncertain review',
        }, status: 503);
      }
      return answer(result);
    },
  };
}

Future<void> runTeachingApproval(
  WidgetTester tester,
  TeachingSmokeFixture fixture,
) async {
  await tapWhenReady(tester, find.text('New draft'));
  await pumpUntilFound(tester, find.byKey(const ValueKey('teaching-brief')));
  await tester.enterText(
    find.byKey(const ValueKey('teaching-brief')),
    'Prepare three levels from the published lesson.',
  );
  await tester.pump();
  await tapWhenReady(tester, find.byKey(const ValueKey('teaching-generate')));
  await tapWhenReady(tester, find.text('Check saved request'));
  await pumpUntilFound(
    tester,
    find.byKey(const ValueKey('teaching-edit-title')),
  );
  expect(fixture.generations, 1);
  expect(fixture.receipts, 1);
  await tester.enterText(
    find.byKey(const ValueKey('teaching-edit-title')),
    'Teacher reviewed browser worksheet',
  );
  await tapWhenReady(tester, find.byKey(const ValueKey('teaching-save-edit')));
  await pumpUntilFound(tester, find.text('Teacher reviewed browser worksheet'));
  await tapWhenReady(tester, find.byKey(const ValueKey('teaching-approve')));
  await tapWhenReady(tester, find.text('Recover original review'));
  await pumpUntilFound(tester, find.textContaining('Approved'));
  expect(fixture.effects, 2);
  expect(fixture.current!['state'], 'approved');
  expect(fixture.reviews.where((r) => r['action'] == 'approve'), hasLength(2));
  final approvals = fixture.reviews
      .where((r) => r['action'] == 'approve')
      .toList();
  expect(approvals[0], approvals[1]);
  expect(
    fixture.reviews.any(
      (r) => ['confirm_marks', 'publish_practice'].contains(r['action']),
    ),
    isFalse,
  );
  expect(tester.takeException(), isNull);
}
