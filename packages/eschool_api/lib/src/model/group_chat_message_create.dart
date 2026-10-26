//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/group_chat_visibility.dart';
import 'package:eschool_api/src/model/group_chat_persona.dart';
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_message_create.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatMessageCreate {
  /// Returns a new [GroupChatMessageCreate] instance.
  GroupChatMessageCreate({
    required this.operationId,

    required this.persona,

    required this.body,

    required this.language,

    required this.visibility,

    this.replyTo,
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

  /// The current school max_characters response further limits this field.
  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @JsonKey(name: r'language', required: true, includeIfNull: false)
  final String language;

  @JsonKey(
    name: r'visibility',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GroupChatVisibility.unknownDefaultOpenApi,
  )
  final GroupChatVisibility visibility;

  // minimum: 1
  @JsonKey(name: r'reply_to', required: false, includeIfNull: false)
  final int? replyTo;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatMessageCreate &&
          other.operationId == operationId &&
          other.persona == persona &&
          other.body == body &&
          other.language == language &&
          other.visibility == visibility &&
          other.replyTo == replyTo;

  @override
  int get hashCode =>
      operationId.hashCode +
      persona.hashCode +
      body.hashCode +
      language.hashCode +
      visibility.hashCode +
      (replyTo == null ? 0 : replyTo.hashCode);

  factory GroupChatMessageCreate.fromJson(Map<String, dynamic> json) =>
      _$GroupChatMessageCreateFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatMessageCreateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
