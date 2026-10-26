//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/group_chat_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'group_chat_receipt_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GroupChatReceiptResponse {
  /// Returns a new [GroupChatReceiptResponse] instance.
  GroupChatReceiptResponse({
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
  final GroupChatReceipt data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GroupChatReceiptResponseCodeEnum.unknownDefaultOpenApi,
  )
  final GroupChatReceiptResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GroupChatReceiptResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory GroupChatReceiptResponse.fromJson(Map<String, dynamic> json) =>
      _$GroupChatReceiptResponseFromJson(json);

  Map<String, dynamic> toJson() => _$GroupChatReceiptResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum GroupChatReceiptResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const GroupChatReceiptResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
