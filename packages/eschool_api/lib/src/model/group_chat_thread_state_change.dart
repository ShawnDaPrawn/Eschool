//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/group_chat_persona.dart';
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_thread_state_change.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatThreadStateChange {
  /// Returns a new [GroupChatThreadStateChange] instance.
  GroupChatThreadStateChange({
    required this.operationId,

    required this.persona,

    required this.revision,

    required this.closed,
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

  @JsonKey(name: r'closed', required: true, includeIfNull: false)
  final bool closed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatThreadStateChange &&
          other.operationId == operationId &&
          other.persona == persona &&
          other.revision == revision &&
          other.closed == closed;

  @override
  int get hashCode =>
      operationId.hashCode +
      persona.hashCode +
      revision.hashCode +
      closed.hashCode;

  factory GroupChatThreadStateChange.fromJson(Map<String, dynamic> json) =>
      _$GroupChatThreadStateChangeFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatThreadStateChangeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
