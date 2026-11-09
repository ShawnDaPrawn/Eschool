//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reenrolment_reminded.dart';
import 'package:json_annotation/json_annotation.dart';

part 'reenrolment_reminded_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReenrolmentRemindedResponse {
  /// Returns a new [ReenrolmentRemindedResponse] instance.
  ReenrolmentRemindedResponse({
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
  final ReenrolmentReminded data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ReenrolmentRemindedResponseCodeEnum.unknownDefaultOpenApi,
  )
  final ReenrolmentRemindedResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReenrolmentRemindedResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory ReenrolmentRemindedResponse.fromJson(Map<String, dynamic> json) =>
      _$ReenrolmentRemindedResponseFromJson(json);

  Map<String, dynamic> toJson() => _$ReenrolmentRemindedResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ReenrolmentRemindedResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const ReenrolmentRemindedResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
