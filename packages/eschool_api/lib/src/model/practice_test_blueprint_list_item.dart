//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_test_topic_share.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_blueprint_list_item.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestBlueprintListItem {
  /// Returns a new [PracticeTestBlueprintListItem] instance.
  PracticeTestBlueprintListItem({
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

    required this.started,

    required this.handedIn,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        PracticeTestBlueprintListItemKindEnum.unknownDefaultOpenApi,
  )
  final PracticeTestBlueprintListItemKindEnum kind;

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
    unknownEnumValue:
        PracticeTestBlueprintListItemWeightingEnum.unknownDefaultOpenApi,
  )
  final PracticeTestBlueprintListItemWeightingEnum weighting;

  @JsonKey(name: r'topic_shares', required: true, includeIfNull: false)
  final List<PracticeTestTopicShare> topicShares;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 0
  @JsonKey(name: r'started', required: true, includeIfNull: false)
  final int started;

  // minimum: 0
  @JsonKey(name: r'handed_in', required: true, includeIfNull: false)
  final int handedIn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestBlueprintListItem &&
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
          other.revision == revision &&
          other.started == started &&
          other.handedIn == handedIn;

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
      revision.hashCode +
      started.hashCode +
      handedIn.hashCode;

  factory PracticeTestBlueprintListItem.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestBlueprintListItemFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestBlueprintListItemToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeTestBlueprintListItemKindEnum {
  @JsonValue(r'quick_quiz')
  quickQuiz(r'quick_quiz'),
  @JsonValue(r'cycle_test')
  cycleTest(r'cycle_test'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestBlueprintListItemKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PracticeTestBlueprintListItemWeightingEnum {
  @JsonValue(r'even')
  even(r'even'),
  @JsonValue(r'past_papers')
  pastPapers(r'past_papers'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestBlueprintListItemWeightingEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
