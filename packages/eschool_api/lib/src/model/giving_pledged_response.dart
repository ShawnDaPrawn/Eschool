//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/giving_pledged.dart';
import 'package:json_annotation/json_annotation.dart';

part 'giving_pledged_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GivingPledgedResponse {
  /// Returns a new [GivingPledgedResponse] instance.
  GivingPledgedResponse({
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
  final GivingPledged data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GivingPledgedResponseCodeEnum.unknownDefaultOpenApi,
  )
  final GivingPledgedResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GivingPledgedResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory GivingPledgedResponse.fromJson(Map<String, dynamic> json) =>
      _$GivingPledgedResponseFromJson(json);

  Map<String, dynamic> toJson() => _$GivingPledgedResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum GivingPledgedResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const GivingPledgedResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
