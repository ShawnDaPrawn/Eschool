//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/giving_donors.dart';
import 'package:json_annotation/json_annotation.dart';

part 'giving_donors_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GivingDonorsResponse {
  /// Returns a new [GivingDonorsResponse] instance.
  GivingDonorsResponse({
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
  final GivingDonors data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GivingDonorsResponseCodeEnum.unknownDefaultOpenApi,
  )
  final GivingDonorsResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GivingDonorsResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory GivingDonorsResponse.fromJson(Map<String, dynamic> json) =>
      _$GivingDonorsResponseFromJson(json);

  Map<String, dynamic> toJson() => _$GivingDonorsResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum GivingDonorsResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const GivingDonorsResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
