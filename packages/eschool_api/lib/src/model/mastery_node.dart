//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'mastery_node.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MasteryNode {
  /// Returns a new [MasteryNode] instance.
  MasteryNode({
    required this.id,

    required this.topicKey,

    required this.name,

    required this.gradeLevelId,

    required this.gradeName,
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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MasteryNode &&
          other.id == id &&
          other.topicKey == topicKey &&
          other.name == name &&
          other.gradeLevelId == gradeLevelId &&
          other.gradeName == gradeName;

  @override
  int get hashCode =>
      id.hashCode +
      topicKey.hashCode +
      name.hashCode +
      gradeLevelId.hashCode +
      gradeName.hashCode;

  factory MasteryNode.fromJson(Map<String, dynamic> json) =>
      _$MasteryNodeFromJson(json);

  Map<String, dynamic> toJson() => _$MasteryNodeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
