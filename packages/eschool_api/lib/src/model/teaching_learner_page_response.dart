//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/teaching_learner_page.dart';
import 'package:json_annotation/json_annotation.dart';

part 'teaching_learner_page_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingLearnerPageResponse {
  /// Returns a new [TeachingLearnerPageResponse] instance.
  TeachingLearnerPageResponse({
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
  final TeachingLearnerPage data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TeachingLearnerPageResponseCodeEnum.unknownDefaultOpenApi,
  )
  final TeachingLearnerPageResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingLearnerPageResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory TeachingLearnerPageResponse.fromJson(Map<String, dynamic> json) =>
      _$TeachingLearnerPageResponseFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingLearnerPageResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TeachingLearnerPageResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const TeachingLearnerPageResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
