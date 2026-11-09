//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reenrolment_learner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'reenrolment_learner_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReenrolmentLearnerResponse {
  /// Returns a new [ReenrolmentLearnerResponse] instance.
  ReenrolmentLearnerResponse({
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
  final ReenrolmentLearner data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ReenrolmentLearnerResponseCodeEnum.unknownDefaultOpenApi,
  )
  final ReenrolmentLearnerResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReenrolmentLearnerResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory ReenrolmentLearnerResponse.fromJson(Map<String, dynamic> json) =>
      _$ReenrolmentLearnerResponseFromJson(json);

  Map<String, dynamic> toJson() => _$ReenrolmentLearnerResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ReenrolmentLearnerResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const ReenrolmentLearnerResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
