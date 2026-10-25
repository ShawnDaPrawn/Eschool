//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'mastery_topic_view.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MasteryTopicView {
  /// Returns a new [MasteryTopicView] instance.
  MasteryTopicView({
    required this.id,

    required this.topicKey,

    required this.name,

    required this.gradeLevelId,

    required this.gradeName,

    required this.level,

    required this.confidenceBp,

    required this.evidenceCount,

    required this.computedAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'topic_key', required: true, includeIfNull: false)
  final String topicKey;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  @JsonKey(name: r'grade_name', required: true, includeIfNull: false)
  final String gradeName;

  // minimum: 0
  // maximum: 4
  @JsonKey(name: r'level', required: true, includeIfNull: true)
  final int? level;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'confidence_bp', required: true, includeIfNull: false)
  final int confidenceBp;

  // minimum: 0
  // maximum: 20
  @JsonKey(name: r'evidence_count', required: true, includeIfNull: false)
  final int evidenceCount;

  @JsonKey(name: r'computed_at', required: true, includeIfNull: true)
  final DateTime? computedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MasteryTopicView &&
          other.id == id &&
          other.topicKey == topicKey &&
          other.name == name &&
          other.gradeLevelId == gradeLevelId &&
          other.gradeName == gradeName &&
          other.level == level &&
          other.confidenceBp == confidenceBp &&
          other.evidenceCount == evidenceCount &&
          other.computedAt == computedAt;

  @override
  int get hashCode =>
      id.hashCode +
      topicKey.hashCode +
      name.hashCode +
      gradeLevelId.hashCode +
      gradeName.hashCode +
      (level == null ? 0 : level.hashCode) +
      confidenceBp.hashCode +
      evidenceCount.hashCode +
      (computedAt == null ? 0 : computedAt.hashCode);

  factory MasteryTopicView.fromJson(Map<String, dynamic> json) =>
      _$MasteryTopicViewFromJson(json);

  Map<String, dynamic> toJson() => _$MasteryTopicViewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
