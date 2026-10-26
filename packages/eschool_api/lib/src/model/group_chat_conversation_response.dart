//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/group_chat_conversation.dart';
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_conversation_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatConversationResponse {
  /// Returns a new [GroupChatConversationResponse] instance.
  GroupChatConversationResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final GroupChatConversation data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        GroupChatConversationResponseCodeEnum.unknownDefaultOpenApi,
  )
  final GroupChatConversationResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatConversationResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory GroupChatConversationResponse.fromJson(Map<String, dynamic> json) =>
      _$GroupChatConversationResponseFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatConversationResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum GroupChatConversationResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const GroupChatConversationResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
