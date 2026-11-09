//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_test_topic_share.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_blueprint.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestBlueprint {
  /// Returns a new [PracticeTestBlueprint] instance.
  PracticeTestBlueprint({
    required this.id,

    required this.kind,

    required this.groupId,

    required this.title,

    required this.questionCount,

    required this.timeLimitMinutes,

    required this.opensAt,

    required this.closesAt,

    required this.weighting,

    required this.topicShares,

    required this.active,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeTestBlueprintKindEnum.unknownDefaultOpenApi,
  )
  final PracticeTestBlueprintKindEnum kind;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  // minimum: 1
  @JsonKey(name: r'question_count', required: true, includeIfNull: false)
  final int questionCount;

  // minimum: 0
  @JsonKey(name: r'time_limit_minutes', required: true, includeIfNull: false)
  final int timeLimitMinutes;

  @JsonKey(name: r'opens_at', required: true, includeIfNull: true)
  final DateTime? opensAt;

  @JsonKey(name: r'closes_at', required: true, includeIfNull: true)
  final DateTime? closesAt;

  @JsonKey(
    name: r'weighting',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeTestBlueprintWeightingEnum.unknownDefaultOpenApi,
  )
  final PracticeTestBlueprintWeightingEnum weighting;

  @JsonKey(name: r'topic_shares', required: true, includeIfNull: false)
  final List<PracticeTestTopicShare> topicShares;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestBlueprint &&
          other.id == id &&
          other.kind == kind &&
          other.groupId == groupId &&
          other.title == title &&
          other.questionCount == questionCount &&
          other.timeLimitMinutes == timeLimitMinutes &&
          other.opensAt == opensAt &&
          other.closesAt == closesAt &&
          other.weighting == weighting &&
          other.topicShares == topicShares &&
          other.active == active &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      kind.hashCode +
      groupId.hashCode +
      title.hashCode +
      questionCount.hashCode +
      timeLimitMinutes.hashCode +
      (opensAt == null ? 0 : opensAt.hashCode) +
      (closesAt == null ? 0 : closesAt.hashCode) +
      weighting.hashCode +
      topicShares.hashCode +
      active.hashCode +
      revision.hashCode;

  factory PracticeTestBlueprint.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestBlueprintFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestBlueprintToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeTestBlueprintKindEnum {
  @JsonValue(r'quick_quiz')
  quickQuiz(r'quick_quiz'),
  @JsonValue(r'cycle_test')
  cycleTest(r'cycle_test'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestBlueprintKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PracticeTestBlueprintWeightingEnum {
  @JsonValue(r'even')
  even(r'even'),
  @JsonValue(r'past_papers')
  pastPapers(r'past_papers'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestBlueprintWeightingEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
