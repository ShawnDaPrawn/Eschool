//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/mastery_priority.dart';
import 'package:eschool_api/src/model/mastery_topic_view.dart';
import 'package:json_annotation/json_annotation.dart';

part 'mastery_view.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MasteryView {
  /// Returns a new [MasteryView] instance.
  MasteryView({
    required this.learnerId,

    required this.subjectId,

    required this.audience,

    required this.marksVisible,

    required this.policyVersion,

    required this.topics,

    required this.targetTopicIds,

    required this.priorities,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  @JsonKey(
    name: r'audience',
    required: true,
    includeIfNull: false,
    unknownEnumValue: MasteryViewAudienceEnum.unknownDefaultOpenApi,
  )
  final MasteryViewAudienceEnum audience;

  @JsonKey(name: r'marks_visible', required: true, includeIfNull: false)
  final bool marksVisible;

  @JsonKey(name: r'policy_version', required: true, includeIfNull: false)
  final String policyVersion;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<MasteryTopicView> topics;

  @JsonKey(name: r'target_topic_ids', required: true, includeIfNull: false)
  final List<int> targetTopicIds;

  @JsonKey(name: r'priorities', required: true, includeIfNull: false)
  final List<MasteryPriority> priorities;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MasteryView &&
          other.learnerId == learnerId &&
          other.subjectId == subjectId &&
          other.audience == audience &&
          other.marksVisible == marksVisible &&
          other.policyVersion == policyVersion &&
          other.topics == topics &&
          other.targetTopicIds == targetTopicIds &&
          other.priorities == priorities;

  @override
  int get hashCode =>
      learnerId.hashCode +
      subjectId.hashCode +
      audience.hashCode +
      marksVisible.hashCode +
      policyVersion.hashCode +
      topics.hashCode +
      targetTopicIds.hashCode +
      priorities.hashCode;

  factory MasteryView.fromJson(Map<String, dynamic> json) =>
      _$MasteryViewFromJson(json);

  Map<String, dynamic> toJson() => _$MasteryViewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum MasteryViewAudienceEnum {
  @JsonValue(r'staff')
  staff(r'staff'),
  @JsonValue(r'family')
  family(r'family'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const MasteryViewAudienceEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
