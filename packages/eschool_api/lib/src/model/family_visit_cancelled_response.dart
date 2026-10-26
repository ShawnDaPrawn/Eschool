//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_visit_cancelled.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_visit_cancelled_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyVisitCancelledResponse {
  /// Returns a new [FamilyVisitCancelledResponse] instance.
  FamilyVisitCancelledResponse({
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
  final FamilyVisitCancelled data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        FamilyVisitCancelledResponseCodeEnum.unknownDefaultOpenApi,
  )
  final FamilyVisitCancelledResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyVisitCancelledResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory FamilyVisitCancelledResponse.fromJson(Map<String, dynamic> json) =>
      _$FamilyVisitCancelledResponseFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyVisitCancelledResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum FamilyVisitCancelledResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const FamilyVisitCancelledResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
