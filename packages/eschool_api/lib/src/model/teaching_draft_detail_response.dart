//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/teaching_draft_detail.dart';
import 'package:json_annotation/json_annotation.dart';

part 'teaching_draft_detail_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingDraftDetailResponse {
  /// Returns a new [TeachingDraftDetailResponse] instance.
  TeachingDraftDetailResponse({
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
  final TeachingDraftDetail data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TeachingDraftDetailResponseCodeEnum.unknownDefaultOpenApi,
  )
  final TeachingDraftDetailResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingDraftDetailResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory TeachingDraftDetailResponse.fromJson(Map<String, dynamic> json) =>
      _$TeachingDraftDetailResponseFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingDraftDetailResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TeachingDraftDetailResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const TeachingDraftDetailResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
