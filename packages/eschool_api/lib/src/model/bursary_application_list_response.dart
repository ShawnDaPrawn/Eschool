//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/bursary_application_list.dart';
import 'package:json_annotation/json_annotation.dart';

part 'bursary_application_list_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BursaryApplicationListResponse {
  /// Returns a new [BursaryApplicationListResponse] instance.
  BursaryApplicationListResponse({
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
  final BursaryApplicationList data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        BursaryApplicationListResponseCodeEnum.unknownDefaultOpenApi,
  )
  final BursaryApplicationListResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BursaryApplicationListResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory BursaryApplicationListResponse.fromJson(Map<String, dynamic> json) =>
      _$BursaryApplicationListResponseFromJson(json);

  Map<String, dynamic> toJson() => _$BursaryApplicationListResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum BursaryApplicationListResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const BursaryApplicationListResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
