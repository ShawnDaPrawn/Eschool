import 'dart:async';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_academics/src/activities/group_chat_repository.dart';
import 'group_chat_repository_test.dart' as fixture;

class ChatFixture implements GroupChatRepository {
  bool active = true, writable = true, sharing = false, closed = false;
  @override
  int schoolId = 1, accountId = 12, groupId = 7;
  @override
  String persona = 'parent';
  @override
  bool get isActive => active;
  final calls = <Map<String, dynamic>>[];
  final receipts = <String, GroupChatMutation>{};
  final failures = <Object>[];
  Completer<GroupChatConversation>? delayedRead;
  final messages = <Map<String, dynamic>>[
    {
      'id': 23,
      'sender_id': 17,
      'sender_name': 'Synthetic staff',
      'sender_persona': 'staff',
      'body': 'Synthetic group announcement',
      'language': 'en',
      'revision': 1,
      'reply_to': null,
      'private': false,
      'removed': false,
      'can_remove': false,
      'sent_at': '2026-02-10T12:00:00Z',
    },
    {
      'id': 24,
      'sender_id': 12,
      'sender_name': 'Synthetic participant',
      'sender_persona': 'parent',
      'body': 'Synthetic private family question',
      'language': 'en',
      'revision': 1,
      'reply_to': null,
      'private': true,
      'removed': false,
      'can_remove': true,
      'sent_at': '2026-02-10T12:00:01Z',
    },
  ];
  Map<String, dynamic> get policy => {
    'revision': 0,
    'staff_learners': true,
    'staff_guardians': true,
    'staff_staff': true,
    'learner_shared_replies': sharing,
    'guardian_shared_replies': sharing,
  };
  void guard() {
    if (!active) throw ApiException('Denied', statusCode: 403);
  }

  @override
  Future<GroupChatThreadPage> threads({String? before}) async {
    guard();
    return GroupChatThreadPage.fromJson({
      ...fixture.pageJson(),
      'persona': persona,
      'writable': writable,
      'policy': policy,
    });
  }

  GroupChatConversation conversation() => GroupChatConversation.fromJson({
    'thread': {...fixture.threadJson(), 'private': false, 'closed': closed},
    'items': messages,
    'policy': policy,
    'next_before': null,
    'can_close': persona == 'staff',
    'can_post': writable && !closed,
    'can_reopen': persona == 'staff' && writable && closed,
    'max_characters': 500,
  });
  @override
  Future<GroupChatConversation> read(String thread, {int? before}) async {
    guard();
    return delayedRead?.future ?? conversation();
  }

  Future<GroupChatMutation> write(ChatWriteIntent intent, String kind) async {
    guard();
    final fields = intent.fields;
    calls.add({...fields, '_action': kind});
    if (failures.isNotEmpty) throw failures.removeAt(0);
    return receipts.putIfAbsent(intent.operationId, () {
      if (kind == 'send' || kind == 'create')
        messages.add({
          ...messages.last,
          'id': 25 + receipts.length,
          'body': fields['body'],
          'sender_id': accountId,
          'sender_persona': persona,
        });
      if (kind == 'state') closed = fields['closed'] as bool;
      if (kind == 'remove')
        messages.last = {
          ...messages.last,
          'body': null,
          'removed': true,
          'can_remove': false,
        };
      return GroupChatMutation.fromJson({
        ...fixture.mutation(),
        'persona': persona,
        'operation_type': kind == 'send'
            ? 'message_sent'
            : kind == 'create'
            ? 'thread_created'
            : kind == 'state'
            ? 'thread_state'
            : 'message_removed',
        if (kind == 'state') 'closed': closed,
      });
    });
  }

  @override
  Future<GroupChatMutation> create(ChatWriteIntent intent) =>
      write(intent, 'create');
  @override
  Future<GroupChatMutation> send(String thread, ChatWriteIntent intent) =>
      write(intent, 'send');
  @override
  Future<GroupChatMutation> state(String thread, ChatWriteIntent intent) =>
      write(intent, 'state');
  @override
  Future<GroupChatMutation> remove(
    String thread,
    int message,
    ChatWriteIntent intent,
  ) => write(intent, 'remove');
  @override
  Future<GroupChatReceipt> receipt(String operation) async {
    guard();
    final result = receipts[operation];
    if (result == null) throw ApiException('Unconfirmed', statusCode: 404);
    return GroupChatReceipt(
      recorded: true,
      operationId: operation,
      receipt: result,
    );
  }

  @override
  Future<void> markRead(String thread, int message) async {
    guard();
  }
}
