//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/group_chat_persona.dart';
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_message_removal.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatMessageRemoval {
  /// Returns a new [GroupChatMessageRemoval] instance.
  GroupChatMessageRemoval({
    required this.operationId,

    required this.persona,

    required this.revision,

    required this.reason,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'persona',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GroupChatPersona.unknownDefaultOpenApi,
  )
  final GroupChatPersona persona;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatMessageRemoval &&
          other.operationId == operationId &&
          other.persona == persona &&
          other.revision == revision &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode +
      persona.hashCode +
      revision.hashCode +
      reason.hashCode;

  factory GroupChatMessageRemoval.fromJson(Map<String, dynamic> json) =>
      _$GroupChatMessageRemovalFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatMessageRemovalToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
