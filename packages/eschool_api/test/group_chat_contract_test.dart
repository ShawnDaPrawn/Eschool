import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

const thread = '8aef50e7-2429-4d2c-a523-668ae436c4d4';
Map<String, dynamic> threadJson() => {
  'id': thread,
  'activity_group_id': 9,
  'title': 'Synthetic question',
  'audience': 'guardians',
  'private': true,
  'closed': false,
  'revision': 2,
  'last_message_at': '2026-02-10T12:00:00.000Z',
};

void main() {
  test('private conversation and readonly capabilities round trip', () {
    final json = <String, dynamic>{
      'thread': threadJson(),
      'policy': {
        'revision': 0,
        'staff_learners': true,
        'staff_guardians': true,
        'staff_staff': true,
        'learner_shared_replies': false,
        'guardian_shared_replies': false,
      },
      'items': [
        {
          'id': 24,
          'sender_id': 17,
          'sender_name': 'Synthetic participant',
          'sender_persona': 'parent',
          'body': null,
          'language': 'en',
          'revision': 2,
          'reply_to': null,
          'private': true,
          'removed': true,
          'can_remove': false,
          'sent_at': '2026-02-10T12:00:00.000Z',
        },
      ],
      'next_before': null,
      'can_close': true,
      'can_post': false,
      'can_reopen': false,
      'max_characters': 500,
    };
    final model = GroupChatConversation.fromJson(json);
    expect(model.toJson(), json);
    expect(model.items.single.body, isNull);
    expect(model.canPost, isFalse);
  });

  test(
    'outcome receipt contains metadata only and optional fields stay absent',
    () {
      final json = <String, dynamic>{
        'recorded': true,
        'operation_id': thread,
        'receipt': {
          'operation_type': 'message_sent',
          'persona': 'parent',
          'thread_id': thread,
          'activity_group_id': 9,
          'revision': 1,
          'message_id': 24,
        },
      };
      expect(GroupChatReceipt.fromJson(json).toJson(), json);
      final receipt = GroupChatReceipt.fromJson(json).receipt.toJson();
      expect(receipt.containsKey('body'), isFalse);
      expect(receipt.containsKey('title'), isFalse);
      expect(receipt.containsKey('closed'), isFalse);
      expect(receipt.containsKey('removed'), isFalse);
    },
  );

  test('cursor page exposes the explicit persona and bounded policy', () {
    final json = <String, dynamic>{
      'items': [
        {...threadJson(), 'unread': 1},
      ],
      'next_before': null,
      'policy': {
        'revision': 0,
        'staff_learners': true,
        'staff_guardians': true,
        'staff_staff': true,
        'learner_shared_replies': false,
        'guardian_shared_replies': false,
      },
      'persona': 'parent',
      'writable': true,
      'max_characters': 500,
    };
    final page = GroupChatThreadPage.fromJson(json);
    expect(page.toJson(), json);
    expect(page.policy.guardianSharedReplies, isFalse);
  });

  test(
    'exact foreground draft keeps UUID and explicit private recipient scope',
    () {
      final json = <String, dynamic>{
        'operation_id': thread,
        'persona': 'parent',
        'body': 'Synthetic private reply',
        'language': 'af',
        'visibility': 'staff',
        'reply_to': 24,
      };
      expect(GroupChatMessageCreate.fromJson(json).toJson(), json);
    },
  );
}
