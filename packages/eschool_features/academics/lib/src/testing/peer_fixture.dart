import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';
import '../wellbeing/school_wall_time.dart';

/// In-memory synthetic transport; cannot create real service or awards.
class PeerSmokeFixture {
  PeerSmokeFixture({this.persona = 'staff', this.reader = 12});
  final String persona;
  final int reader;
  static const existing = 'd3d950bd-8713-435c-899b-efc72d96a22f';
  static const created = 'e3d950bd-8713-435c-899b-efc72d96a22f';
  bool allowed = true, loseNext = true;
  int effects = 0, minutes = 0, revision = 1;
  String status = 'scheduled';
  Map<String, dynamic>? scheduled;
  final writes = <Map<String, dynamic>>[];
  final _intents = <String, String>{};
  Object result(Object data) => allowed
      ? {'error': false, 'message': 'OK', 'code': 200, 'data': data}
      : const FakeResponse({
          'error': true,
          'message': 'Your peer tutoring access changed. Reopen this screen.',
          'data': null,
        }, status: 403);
  Map<String, dynamic> configuration() => {
    'school_id': 1,
    'reader_id': reader,
    'persona': persona,
    'timezone': 'Africa/Johannesburg',
    'writable': persona == 'staff',
    'groups': [
      if (persona == 'staff') {'id': 4, 'name': 'Synthetic maths group'},
    ],
    'children': [
      if (persona != 'staff') {'id': 11, 'name': 'Synthetic own learner'},
    ],
    'can_award_points': false,
    'point_categories': [],
  };
  Map<String, dynamic> row({bool newSession = false}) => {
    'id': newSession ? created : existing,
    'revision': newSession ? 1 : revision,
    'teaching_group_id': 4,
    'topic_id': 8,
    'topic_name': 'Synthetic fractions',
    'tutor_learner_id': 11,
    'tutor_name': 'Synthetic own learner',
    'tutee_learner_id': 22,
    'tutee_name': 'Synthetic peer learner',
    'supervisor_user_id': 12,
    'supervisor_name': 'Synthetic supervisor',
    'starts_at': newSession
        ? SchoolWallTime.utc(
            scheduled!['starts_at'] as String,
            'Africa/Johannesburg',
          ).toIso8601String()
        : '2026-01-10T12:00:00Z',
    'ends_at': newSession
        ? SchoolWallTime.utc(
            scheduled!['ends_at'] as String,
            'Africa/Johannesburg',
          ).toIso8601String()
        : '2026-01-10T13:00:00Z',
    'venue': newSession ? scheduled!['venue'] : 'Synthetic supervised room',
    'status': newSession ? 'scheduled' : status,
    'verified_minutes': !newSession && minutes > 0 ? minutes : null,
    'service_minutes': newSession
        ? 0
        : status == 'reversed'
        ? 0
        : minutes,
    'service_for_reader': true,
    'house_point_id': null,
    'can_complete': persona == 'staff' && !newSession && status == 'scheduled',
    'can_cancel': persona == 'staff' && (newSession || status == 'scheduled'),
    'can_reverse': persona == 'staff' && !newSession && status == 'completed',
  };
  Object _write(RequestOptions request, String action) {
    if (!allowed) return result({});
    final body = Map<String, dynamic>.from(request.data as Map),
        operation = body['operation_id'] as String;
    final fingerprint = jsonEncode([action, body]);
    writes.add({'action': action, 'body': body});
    if (_intents.containsKey(operation) && _intents[operation] != fingerprint)
      return const FakeResponse({
        'error': true,
        'message': 'Changed exact intent',
      }, status: 409);
    if (!_intents.containsKey(operation)) {
      _intents[operation] = fingerprint;
      effects++;
      if (action == 'create') {
        scheduled = body;
      } else {
        revision++;
        status = action == 'complete'
            ? 'completed'
            : action == 'cancel'
            ? 'cancelled'
            : 'reversed';
        if (action == 'complete') minutes = body['minutes'] as int;
      }
      if (loseNext) {
        loseNext = false;
        return const FakeResponse({
          'error': true,
          'message': 'Synthetic lost acknowledgement',
        }, status: 503);
      }
    }
    return result({
      'school_id': 1,
      'reader_id': reader,
      'session': row(newSession: action == 'create'),
    });
  }

  Map<String, Object> get routes => {
    'GET v2/peer-tutoring/configuration': (_) => result(configuration()),
    'GET v2/peer-tutoring/sessions': (_) => result({
      'school_id': 1,
      'reader_id': reader,
      'items': [row(), if (scheduled != null) row(newSession: true)],
      'next_page': null,
    }),
    'GET v2/peer-tutoring/groups/4/learners': (_) => result({
      'school_id': 1,
      'reader_id': reader,
      'teaching_group_id': 4,
      'items': [
        {'id': 11, 'name': 'Synthetic own learner'},
        {'id': 22, 'name': 'Synthetic peer learner'},
      ],
    }),
    for (final learner in [11, 22])
      'GET v2/peer-tutoring/groups/4/learners/$learner/matches': (_) => result({
        'school_id': 1,
        'reader_id': reader,
        'teaching_group_id': 4,
        'learner_id': learner,
        'items': [
          if (learner == 22)
            {
              'tutor_learner_id': 11,
              'tutor_name': 'Synthetic own learner',
              'tutee_learner_id': 22,
              'topic_id': 8,
              'topic_name': 'Synthetic fractions',
              'reason': 'topic_gap',
            },
        ],
      }),
    'POST v2/peer-tutoring/sessions': (RequestOptions r) => _write(r, 'create'),
    for (final action in ['complete', 'cancel', 'reverse'])
      'POST v2/peer-tutoring/sessions/$existing/$action': (RequestOptions r) =>
          _write(r, action),
  };
}
