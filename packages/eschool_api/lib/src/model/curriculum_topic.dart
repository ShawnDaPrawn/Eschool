//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'curriculum_topic.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CurriculumTopic {
  /// Returns a new [CurriculumTopic] instance.
  CurriculumTopic({
    required this.id,

    required this.key,

    required this.termId,

    required this.name,

    required this.sortOrder,

    required this.weekFrom,

    required this.weekTo,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'key', required: true, includeIfNull: false)
  final String key;

  // minimum: 1
  @JsonKey(name: r'term_id', required: true, includeIfNull: false)
  final int termId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'sort_order', required: true, includeIfNull: false)
  final int sortOrder;

  // minimum: 1
  // maximum: 53
  @JsonKey(name: r'week_from', required: true, includeIfNull: true)
  final int? weekFrom;

  // minimum: 1
  // maximum: 53
  @JsonKey(name: r'week_to', required: true, includeIfNull: true)
  final int? weekTo;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CurriculumTopic &&
          other.id == id &&
          other.key == key &&
          other.termId == termId &&
          other.name == name &&
          other.sortOrder == sortOrder &&
          other.weekFrom == weekFrom &&
          other.weekTo == weekTo;

  @override
  int get hashCode =>
      id.hashCode +
      key.hashCode +
      termId.hashCode +
      name.hashCode +
      sortOrder.hashCode +
      (weekFrom == null ? 0 : weekFrom.hashCode) +
      (weekTo == null ? 0 : weekTo.hashCode);

  factory CurriculumTopic.fromJson(Map<String, dynamic> json) =>
      _$CurriculumTopicFromJson(json);

  Map<String, dynamic> toJson() => _$CurriculumTopicToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
