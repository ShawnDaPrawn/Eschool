//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/bursary_change.dart';
import 'package:json_annotation/json_annotation.dart';

part 'bursary_change_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BursaryChangeResponse {
  /// Returns a new [BursaryChangeResponse] instance.
  BursaryChangeResponse({
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
  final BursaryChange data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: BursaryChangeResponseCodeEnum.unknownDefaultOpenApi,
  )
  final BursaryChangeResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BursaryChangeResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory BursaryChangeResponse.fromJson(Map<String, dynamic> json) =>
      _$BursaryChangeResponseFromJson(json);

  Map<String, dynamic> toJson() => _$BursaryChangeResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum BursaryChangeResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const BursaryChangeResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
