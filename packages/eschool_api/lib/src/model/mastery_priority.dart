//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'mastery_priority.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MasteryPriority {
  /// Returns a new [MasteryPriority] instance.
  MasteryPriority({
    required this.topicId,

    required this.name,

    required this.level,

    required this.confidenceBp,

    required this.reason,

    required this.path,
  });

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 0
  // maximum: 4
  @JsonKey(name: r'level', required: true, includeIfNull: true)
  final int? level;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'confidence_bp', required: true, includeIfNull: false)
  final int confidenceBp;

  @JsonKey(
    name: r'reason',
    required: true,
    includeIfNull: false,
    unknownEnumValue: MasteryPriorityReasonEnum.unknownDefaultOpenApi,
  )
  final MasteryPriorityReasonEnum reason;

  @JsonKey(name: r'path', required: true, includeIfNull: false)
  final List<int> path;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MasteryPriority &&
          other.topicId == topicId &&
          other.name == name &&
          other.level == level &&
          other.confidenceBp == confidenceBp &&
          other.reason == reason &&
          other.path == path;

  @override
  int get hashCode =>
      topicId.hashCode +
      name.hashCode +
      (level == null ? 0 : level.hashCode) +
      confidenceBp.hashCode +
      reason.hashCode +
      path.hashCode;

  factory MasteryPriority.fromJson(Map<String, dynamic> json) =>
      _$MasteryPriorityFromJson(json);

  Map<String, dynamic> toJson() => _$MasteryPriorityToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum MasteryPriorityReasonEnum {
  @JsonValue(r'not_yet_assessed')
  notYetAssessed(r'not_yet_assessed'),
  @JsonValue(r'prerequisite_gap')
  prerequisiteGap(r'prerequisite_gap'),
  @JsonValue(r'topic_gap')
  topicGap(r'topic_gap'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const MasteryPriorityReasonEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
