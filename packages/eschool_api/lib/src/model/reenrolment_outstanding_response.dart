//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reenrolment_outstanding.dart';
import 'package:json_annotation/json_annotation.dart';

part 'reenrolment_outstanding_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReenrolmentOutstandingResponse {
  /// Returns a new [ReenrolmentOutstandingResponse] instance.
  ReenrolmentOutstandingResponse({
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
  final ReenrolmentOutstanding data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        ReenrolmentOutstandingResponseCodeEnum.unknownDefaultOpenApi,
  )
  final ReenrolmentOutstandingResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReenrolmentOutstandingResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory ReenrolmentOutstandingResponse.fromJson(Map<String, dynamic> json) =>
      _$ReenrolmentOutstandingResponseFromJson(json);

  Map<String, dynamic> toJson() => _$ReenrolmentOutstandingResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ReenrolmentOutstandingResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const ReenrolmentOutstandingResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
