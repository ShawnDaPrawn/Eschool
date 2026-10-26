//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/group_chat_operation_type.dart';
import 'package:eschool_api/src/model/group_chat_persona.dart';
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_mutation.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatMutation {
  /// Returns a new [GroupChatMutation] instance.
  GroupChatMutation({
    required this.operationType,

    required this.persona,

    required this.threadId,

    required this.activityGroupId,

    required this.revision,

    this.messageId,

    this.closed,

    this.removed,
  });

  @JsonKey(
    name: r'operation_type',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GroupChatOperationType.unknownDefaultOpenApi,
  )
  final GroupChatOperationType operationType;

  @JsonKey(
    name: r'persona',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GroupChatPersona.unknownDefaultOpenApi,
  )
  final GroupChatPersona persona;

  @JsonKey(name: r'thread_id', required: true, includeIfNull: false)
  final String threadId;

  // minimum: 1
  @JsonKey(name: r'activity_group_id', required: true, includeIfNull: false)
  final int activityGroupId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'message_id', required: false, includeIfNull: false)
  final int? messageId;

  @JsonKey(name: r'closed', required: false, includeIfNull: false)
  final bool? closed;

  @JsonKey(name: r'removed', required: false, includeIfNull: false)
  final bool? removed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatMutation &&
          other.operationType == operationType &&
          other.persona == persona &&
          other.threadId == threadId &&
          other.activityGroupId == activityGroupId &&
          other.revision == revision &&
          other.messageId == messageId &&
          other.closed == closed &&
          other.removed == removed;

  @override
  int get hashCode =>
      operationType.hashCode +
      persona.hashCode +
      threadId.hashCode +
      activityGroupId.hashCode +
      revision.hashCode +
      messageId.hashCode +
      closed.hashCode +
      removed.hashCode;

  factory GroupChatMutation.fromJson(Map<String, dynamic> json) =>
      _$GroupChatMutationFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatMutationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
