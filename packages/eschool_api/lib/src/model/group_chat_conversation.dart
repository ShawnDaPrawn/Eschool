//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/group_chat_message.dart';
import 'package:eschool_api/src/model/group_chat_policy.dart';
import 'package:eschool_api/src/model/group_chat_thread.dart';
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_conversation.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatConversation {
  /// Returns a new [GroupChatConversation] instance.
  GroupChatConversation({
    required this.thread,

    required this.items,

    required this.nextBefore,

    required this.canClose,

    required this.canPost,

    required this.canReopen,

    required this.maxCharacters,

    required this.policy,
  });

  @JsonKey(name: r'thread', required: true, includeIfNull: false)
  final GroupChatThread thread;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<GroupChatMessage> items;

  // minimum: 1
  @JsonKey(name: r'next_before', required: true, includeIfNull: true)
  final int? nextBefore;

  @JsonKey(name: r'can_close', required: true, includeIfNull: false)
  final bool canClose;

  @JsonKey(name: r'can_post', required: true, includeIfNull: false)
  final bool canPost;

  @JsonKey(name: r'can_reopen', required: true, includeIfNull: false)
  final bool canReopen;

  // minimum: 1
  // maximum: 10000
  @JsonKey(name: r'max_characters', required: true, includeIfNull: false)
  final int maxCharacters;

  @JsonKey(name: r'policy', required: true, includeIfNull: false)
  final GroupChatPolicy policy;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatConversation &&
          other.thread == thread &&
          other.items == items &&
          other.nextBefore == nextBefore &&
          other.canClose == canClose &&
          other.canPost == canPost &&
          other.canReopen == canReopen &&
          other.maxCharacters == maxCharacters &&
          other.policy == policy;

  @override
  int get hashCode =>
      thread.hashCode +
      items.hashCode +
      (nextBefore == null ? 0 : nextBefore.hashCode) +
      canClose.hashCode +
      canPost.hashCode +
      canReopen.hashCode +
      maxCharacters.hashCode +
      policy.hashCode;

  factory GroupChatConversation.fromJson(Map<String, dynamic> json) =>
      _$GroupChatConversationFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatConversationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
