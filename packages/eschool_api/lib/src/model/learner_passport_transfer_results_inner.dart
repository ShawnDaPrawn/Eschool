//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_passport_transfer_results_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerPassportTransferResultsInner {
  /// Returns a new [LearnerPassportTransferResultsInner] instance.
  LearnerPassportTransferResultsInner({
    required this.subjectName,

    required this.mode,

    required this.percentageBp,

    required this.ratingLevel,

    required this.releasedOn,
  });

  @JsonKey(name: r'subject_name', required: true, includeIfNull: false)
  final String subjectName;

  @JsonKey(
    name: r'mode',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        LearnerPassportTransferResultsInnerModeEnum.unknownDefaultOpenApi,
  )
  final LearnerPassportTransferResultsInnerModeEnum mode;

  /// Percentage in basis points (7250 is 72.50%).
  @JsonKey(name: r'percentage_bp', required: true, includeIfNull: true)
  final int? percentageBp;

  @JsonKey(name: r'rating_level', required: true, includeIfNull: true)
  final int? ratingLevel;

  @JsonKey(name: r'released_on', required: true, includeIfNull: true)
  final DateTime? releasedOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerPassportTransferResultsInner &&
          other.subjectName == subjectName &&
          other.mode == mode &&
          other.percentageBp == percentageBp &&
          other.ratingLevel == ratingLevel &&
          other.releasedOn == releasedOn;

  @override
  int get hashCode =>
      subjectName.hashCode +
      mode.hashCode +
      (percentageBp == null ? 0 : percentageBp.hashCode) +
      (ratingLevel == null ? 0 : ratingLevel.hashCode) +
      (releasedOn == null ? 0 : releasedOn.hashCode);

  factory LearnerPassportTransferResultsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$LearnerPassportTransferResultsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$LearnerPassportTransferResultsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearnerPassportTransferResultsInnerModeEnum {
  @JsonValue(r'official')
  official(r'official'),
  @JsonValue(r'running')
  running(r'running'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearnerPassportTransferResultsInnerModeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
