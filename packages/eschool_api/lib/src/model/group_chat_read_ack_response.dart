//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/group_chat_read_ack.dart';
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_read_ack_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatReadAckResponse {
  /// Returns a new [GroupChatReadAckResponse] instance.
  GroupChatReadAckResponse({
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
  final GroupChatReadAck data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GroupChatReadAckResponseCodeEnum.unknownDefaultOpenApi,
  )
  final GroupChatReadAckResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatReadAckResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory GroupChatReadAckResponse.fromJson(Map<String, dynamic> json) =>
      _$GroupChatReadAckResponseFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatReadAckResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum GroupChatReadAckResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const GroupChatReadAckResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
