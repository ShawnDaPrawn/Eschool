//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_read_ack.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatReadAck {
  /// Returns a new [GroupChatReadAck] instance.
  GroupChatReadAck({required this.threadId, required this.lastMessageId});

  @JsonKey(name: r'thread_id', required: true, includeIfNull: false)
  final String threadId;

  // minimum: 0
  @JsonKey(name: r'last_message_id', required: true, includeIfNull: false)
  final int lastMessageId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatReadAck &&
          other.threadId == threadId &&
          other.lastMessageId == lastMessageId;

  @override
  int get hashCode => threadId.hashCode + lastMessageId.hashCode;

  factory GroupChatReadAck.fromJson(Map<String, dynamic> json) =>
      _$GroupChatReadAckFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatReadAckToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
