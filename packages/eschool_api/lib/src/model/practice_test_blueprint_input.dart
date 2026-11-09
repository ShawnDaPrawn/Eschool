//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_blueprint_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestBlueprintInput {
  /// Returns a new [PracticeTestBlueprintInput] instance.
  PracticeTestBlueprintInput({
    required this.operationId,

    required this.title,

    required this.topicIds,

    required this.questionCount,

    required this.timeLimitMinutes,

    required this.opensAt,

    required this.closesAt,

    required this.weighting,

    this.paperVersionIds,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'topic_ids', required: true, includeIfNull: false)
  final Set<int> topicIds;

  // minimum: 5
  // maximum: 40
  @JsonKey(name: r'question_count', required: true, includeIfNull: false)
  final int questionCount;

  // minimum: 5
  // maximum: 180
  @JsonKey(name: r'time_limit_minutes', required: true, includeIfNull: false)
  final int timeLimitMinutes;

  @JsonKey(name: r'opens_at', required: true, includeIfNull: false)
  final DateTime opensAt;

  @JsonKey(name: r'closes_at', required: true, includeIfNull: false)
  final DateTime closesAt;

  @JsonKey(
    name: r'weighting',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        PracticeTestBlueprintInputWeightingEnum.unknownDefaultOpenApi,
  )
  final PracticeTestBlueprintInputWeightingEnum weighting;

  @JsonKey(name: r'paper_version_ids', required: false, includeIfNull: false)
  final Set<String>? paperVersionIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestBlueprintInput &&
          other.operationId == operationId &&
          other.title == title &&
          other.topicIds == topicIds &&
          other.questionCount == questionCount &&
          other.timeLimitMinutes == timeLimitMinutes &&
          other.opensAt == opensAt &&
          other.closesAt == closesAt &&
          other.weighting == weighting &&
          other.paperVersionIds == paperVersionIds;

  @override
  int get hashCode =>
      operationId.hashCode +
      title.hashCode +
      topicIds.hashCode +
      questionCount.hashCode +
      timeLimitMinutes.hashCode +
      opensAt.hashCode +
      closesAt.hashCode +
      weighting.hashCode +
      paperVersionIds.hashCode;

  factory PracticeTestBlueprintInput.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestBlueprintInputFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestBlueprintInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeTestBlueprintInputWeightingEnum {
  @JsonValue(r'even')
  even(r'even'),
  @JsonValue(r'past_papers')
  pastPapers(r'past_papers'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestBlueprintInputWeightingEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
