//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/group_chat_audience.dart';
import 'package:eschool_api/src/model/group_chat_thread_kind.dart';
import 'package:eschool_api/src/model/group_chat_persona.dart';
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_thread_create.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatThreadCreate {
  /// Returns a new [GroupChatThreadCreate] instance.
  GroupChatThreadCreate({
    required this.operationId,

    required this.persona,

    required this.body,

    required this.language,

    required this.kind,

    this.audience,

    required this.title,
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
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GroupChatThreadKind.unknownDefaultOpenApi,
  )
  final GroupChatThreadKind kind;

  @JsonKey(
    name: r'audience',
    required: false,
    includeIfNull: false,
    unknownEnumValue: GroupChatAudience.unknownDefaultOpenApi,
  )
  final GroupChatAudience? audience;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatThreadCreate &&
          other.operationId == operationId &&
          other.persona == persona &&
          other.body == body &&
          other.language == language &&
          other.kind == kind &&
          other.audience == audience &&
          other.title == title;

  @override
  int get hashCode =>
      operationId.hashCode +
      persona.hashCode +
      body.hashCode +
      language.hashCode +
      kind.hashCode +
      audience.hashCode +
      title.hashCode;

  factory GroupChatThreadCreate.fromJson(Map<String, dynamic> json) =>
      _$GroupChatThreadCreateFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatThreadCreateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
