//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/group_chat_persona.dart';
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_message.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatMessage {
  /// Returns a new [GroupChatMessage] instance.
  GroupChatMessage({
    required this.id,

    required this.senderId,

    required this.senderName,

    required this.senderPersona,

    required this.body,

    required this.language,

    required this.revision,

    required this.replyTo,

    required this.private,

    required this.removed,

    required this.canRemove,

    required this.sentAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'sender_id', required: true, includeIfNull: false)
  final int senderId;

  @JsonKey(name: r'sender_name', required: true, includeIfNull: false)
  final String senderName;

  @JsonKey(
    name: r'sender_persona',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GroupChatPersona.unknownDefaultOpenApi,
  )
  final GroupChatPersona senderPersona;

  @JsonKey(name: r'body', required: true, includeIfNull: true)
  final String? body;

  @JsonKey(name: r'language', required: true, includeIfNull: false)
  final String language;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'reply_to', required: true, includeIfNull: true)
  final int? replyTo;

  @JsonKey(name: r'private', required: true, includeIfNull: false)
  final bool private;

  @JsonKey(name: r'removed', required: true, includeIfNull: false)
  final bool removed;

  @JsonKey(name: r'can_remove', required: true, includeIfNull: false)
  final bool canRemove;

  @JsonKey(name: r'sent_at', required: true, includeIfNull: false)
  final DateTime sentAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatMessage &&
          other.id == id &&
          other.senderId == senderId &&
          other.senderName == senderName &&
          other.senderPersona == senderPersona &&
          other.body == body &&
          other.language == language &&
          other.revision == revision &&
          other.replyTo == replyTo &&
          other.private == private &&
          other.removed == removed &&
          other.canRemove == canRemove &&
          other.sentAt == sentAt;

  @override
  int get hashCode =>
      id.hashCode +
      senderId.hashCode +
      senderName.hashCode +
      senderPersona.hashCode +
      (body == null ? 0 : body.hashCode) +
      language.hashCode +
      revision.hashCode +
      (replyTo == null ? 0 : replyTo.hashCode) +
      private.hashCode +
      removed.hashCode +
      canRemove.hashCode +
      sentAt.hashCode;

  factory GroupChatMessage.fromJson(Map<String, dynamic> json) =>
      _$GroupChatMessageFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatMessageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
