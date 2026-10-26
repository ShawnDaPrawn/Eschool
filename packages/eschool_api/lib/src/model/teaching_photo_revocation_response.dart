//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/teaching_photo_revocation.dart';
import 'package:json_annotation/json_annotation.dart';

part 'teaching_photo_revocation_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingPhotoRevocationResponse {
  /// Returns a new [TeachingPhotoRevocationResponse] instance.
  TeachingPhotoRevocationResponse({
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
  final TeachingPhotoRevocation data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        TeachingPhotoRevocationResponseCodeEnum.unknownDefaultOpenApi,
  )
  final TeachingPhotoRevocationResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingPhotoRevocationResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory TeachingPhotoRevocationResponse.fromJson(Map<String, dynamic> json) =>
      _$TeachingPhotoRevocationResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$TeachingPhotoRevocationResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TeachingPhotoRevocationResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const TeachingPhotoRevocationResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
