//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/group_chat_persona.dart';
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_read_change.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatReadChange {
  /// Returns a new [GroupChatReadChange] instance.
  GroupChatReadChange({required this.persona, required this.lastMessageId});

  @JsonKey(
    name: r'persona',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GroupChatPersona.unknownDefaultOpenApi,
  )
  final GroupChatPersona persona;

  // minimum: 1
  @JsonKey(name: r'last_message_id', required: true, includeIfNull: false)
  final int lastMessageId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatReadChange &&
          other.persona == persona &&
          other.lastMessageId == lastMessageId;

  @override
  int get hashCode => persona.hashCode + lastMessageId.hashCode;

  factory GroupChatReadChange.fromJson(Map<String, dynamic> json) =>
      _$GroupChatReadChangeFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatReadChangeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
