//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_academic_competition_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerAcademicCompetitionResult {
  /// Returns a new [LearnerAcademicCompetitionResult] instance.
  LearnerAcademicCompetitionResult({
    required this.state,

    required this.ourScore,

    required this.theirScore,

    required this.outcome,
  });

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        LearnerAcademicCompetitionResultStateEnum.unknownDefaultOpenApi,
  )
  final LearnerAcademicCompetitionResultStateEnum state;

  @JsonKey(name: r'our_score', required: true, includeIfNull: true)
  final int? ourScore;

  @JsonKey(name: r'their_score', required: true, includeIfNull: true)
  final int? theirScore;

  @JsonKey(
    name: r'outcome',
    required: true,
    includeIfNull: true,
    unknownEnumValue:
        LearnerAcademicCompetitionResultOutcomeEnum.unknownDefaultOpenApi,
  )
  final LearnerAcademicCompetitionResultOutcomeEnum? outcome;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerAcademicCompetitionResult &&
          other.state == state &&
          other.ourScore == ourScore &&
          other.theirScore == theirScore &&
          other.outcome == outcome;

  @override
  int get hashCode =>
      state.hashCode +
      (ourScore == null ? 0 : ourScore.hashCode) +
      (theirScore == null ? 0 : theirScore.hashCode) +
      (outcome == null ? 0 : outcome.hashCode);

  factory LearnerAcademicCompetitionResult.fromJson(
    Map<String, dynamic> json,
  ) => _$LearnerAcademicCompetitionResultFromJson(json);

  Map<String, dynamic> toJson() =>
      _$LearnerAcademicCompetitionResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearnerAcademicCompetitionResultStateEnum {
  @JsonValue(r'confirmed')
  confirmed(r'confirmed'),
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearnerAcademicCompetitionResultStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum LearnerAcademicCompetitionResultOutcomeEnum {
  @JsonValue(r'won')
  won(r'won'),
  @JsonValue(r'lost')
  lost(r'lost'),
  @JsonValue(r'draw')
  draw(r'draw'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearnerAcademicCompetitionResultOutcomeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
