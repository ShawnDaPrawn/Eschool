import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';
import 'activity_file_fixtures.dart';

/// Synthetic records for actual app entry, privacy and exact-retry journeys.
class GroupChatClientFixture {
  GroupChatClientFixture({required this.persona, required this.account});
  final String persona;
  final int account;
  static const thread = '8aef50e7-2429-4d2c-a523-668ae436c4d4';
  final stamp = DateTime.now().toUtc().toIso8601String();
  bool allowed = true, interrupted = false, closed = false;
  int revision = 1, effects = 0;
  String title = 'Fixture change';
  final writes = <Map<String, dynamic>>[];
  final receipts = <String, Map<String, dynamic>>{};
  String? sentBody;
  bool get staff => persona == 'staff';
  Object ok(Object data) => {
    'error': false,
    'data': data,
    'code': 200,
    'message': 'OK',
  };
  Object denied() => const FakeResponse({
    'error': true,
    'message': 'Group access withdrawn',
    'data': null,
    'code': 403,
  }, status: 403);
  bool scoped(RequestOptions request, {bool detail = false}) =>
      allowed &&
      (detail
          ? request.queryParameters['chat_persona'] == persona
          : request.method == 'GET'
          ? request.queryParameters['persona'] == persona
          : (request.data as Map)['persona'] == persona);
  Map<String, dynamic> get policy => {
    'revision': 0,
    'staff_learners': true,
    'staff_guardians': true,
    'staff_staff': true,
    'learner_shared_replies': false,
    'guardian_shared_replies': false,
  };
  Map<String, dynamic> get metadata => {
    'id': thread,
    'activity_group_id': 7,
    'title': title,
    'audience': persona == 'student' ? 'learners' : 'guardians',
    'private': false,
    'closed': closed,
    'revision': revision,
    'last_message_at': stamp,
  };
  Map<String, dynamic> message(int id, String text, {bool personal = false}) =>
      {
        'id': id,
        'sender_id': personal ? (staff ? 99 : account) : 17,
        'sender_name': personal ? 'Synthetic participant' : 'Synthetic coach',
        'sender_persona': personal
            ? (persona == 'student' ? 'student' : 'parent')
            : 'staff',
        'body': text,
        'language': 'en',
        'revision': 1,
        'reply_to': null,
        'private': personal,
        'removed': false,
        'can_remove': staff || personal,
        'sent_at': stamp,
      };
  Object write(RequestOptions request, String kind) {
    if (!scoped(request)) return denied();
    final fields = Map<String, dynamic>.from(request.data as Map);
    writes.add(fields);
    final operation = fields['operation_id'] as String;
    final old = receipts[operation];
    if (old != null) return ok(old);
    effects++;
    if (kind == 'create') title = fields['title'] as String;
    if (kind == 'state') {
      closed = fields['closed'] as bool;
      revision++;
    }
    if (kind == 'send' || kind == 'create') sentBody = fields['body'] as String;
    final result = {
      'operation_type': kind == 'create'
          ? 'thread_created'
          : kind == 'state'
          ? 'thread_state'
          : 'message_sent',
      'persona': persona,
      'thread_id': thread,
      'activity_group_id': 7,
      'revision': revision,
      if (kind != 'state') 'message_id': 25,
      if (kind == 'state') 'closed': closed,
    };
    receipts[operation] = result;
    if (!interrupted) {
      interrupted = true;
      return const FakeResponse({
        'error': true,
        'message': 'Synthetic response interrupted',
        'data': null,
        'code': 503,
      }, status: 503);
    }
    return ok(result);
  }

  Map<String, Object> routes() => {
    'GET v2/activities/groups': (_) => allowed
        ? ok({
            'items': [materialGroupFixture()],
            'page': 1,
            'last_page': 1,
            'total': 1,
          })
        : denied(),
    'GET v2/activities/groups/7': (RequestOptions request) =>
        scoped(request, detail: true)
        ? ok({...materialDetailFixture(staff: staff), 'chat_available': true})
        : denied(),
    'GET v2/activities/groups/7/chat/threads': (RequestOptions request) =>
        scoped(request)
        ? ok({
            'items': [
              {...metadata, 'unread': 1},
            ],
            'next_before': null,
            'policy': policy,
            'persona': persona,
            'writable': true,
            'max_characters': 500,
          })
        : denied(),
    'POST v2/activities/groups/7/chat/threads': (RequestOptions request) =>
        write(request, 'create'),
    'GET v2/activities/groups/7/chat/threads/$thread':
        (RequestOptions request) => scoped(request)
        ? ok({
            'thread': metadata,
            'policy': policy,
            'items': [
              message(23, 'Bring water to practice.'),
              message(24, 'Synthetic private coach question', personal: true),
              if (sentBody != null) message(25, sentBody!, personal: !staff),
            ],
            'next_before': null,
            'can_close': staff,
            'can_post': !closed,
            'can_reopen': staff && closed,
            'max_characters': 500,
          })
        : denied(),
    'POST v2/activities/groups/7/chat/threads/$thread/messages':
        (RequestOptions request) => write(request, 'send'),
    'POST v2/activities/groups/7/chat/threads/$thread/state':
        (RequestOptions request) => write(request, 'state'),
    'POST v2/activities/groups/7/chat/threads/$thread/read':
        (RequestOptions request) => scoped(request)
        ? ok({
            'thread_id': thread,
            'last_message_id': (request.data as Map)['last_message_id'],
          })
        : denied(),
  };
}
