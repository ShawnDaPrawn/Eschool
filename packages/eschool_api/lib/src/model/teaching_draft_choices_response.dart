//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/teaching_draft_choices.dart';
import 'package:json_annotation/json_annotation.dart';

part 'teaching_draft_choices_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingDraftChoicesResponse {
  /// Returns a new [TeachingDraftChoicesResponse] instance.
  TeachingDraftChoicesResponse({
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
  final TeachingDraftChoices data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        TeachingDraftChoicesResponseCodeEnum.unknownDefaultOpenApi,
  )
  final TeachingDraftChoicesResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingDraftChoicesResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory TeachingDraftChoicesResponse.fromJson(Map<String, dynamic> json) =>
      _$TeachingDraftChoicesResponseFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingDraftChoicesResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TeachingDraftChoicesResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const TeachingDraftChoicesResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
