//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_fixture_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerFixtureResult {
  /// Returns a new [LearnerFixtureResult] instance.
  LearnerFixtureResult({
    required this.state,

    required this.ourScore,

    required this.theirScore,
  });

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LearnerFixtureResultStateEnum.unknownDefaultOpenApi,
  )
  final LearnerFixtureResultStateEnum state;

  @JsonKey(name: r'our_score', required: true, includeIfNull: true)
  final int? ourScore;

  @JsonKey(name: r'their_score', required: true, includeIfNull: true)
  final int? theirScore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerFixtureResult &&
          other.state == state &&
          other.ourScore == ourScore &&
          other.theirScore == theirScore;

  @override
  int get hashCode =>
      state.hashCode +
      (ourScore == null ? 0 : ourScore.hashCode) +
      (theirScore == null ? 0 : theirScore.hashCode);

  factory LearnerFixtureResult.fromJson(Map<String, dynamic> json) =>
      _$LearnerFixtureResultFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerFixtureResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearnerFixtureResultStateEnum {
  @JsonValue(r'confirmed')
  confirmed(r'confirmed'),
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearnerFixtureResultStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
