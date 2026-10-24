//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'curriculum_topic_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CurriculumTopicWrite {
  /// Returns a new [CurriculumTopicWrite] instance.
  CurriculumTopicWrite({
    this.key,

    required this.termId,

    required this.name,

    this.sortOrder,

    this.weekFrom,

    this.weekTo,
  });

  @JsonKey(name: r'key', required: false, includeIfNull: false)
  final String? key;

  // minimum: 1
  @JsonKey(name: r'term_id', required: true, includeIfNull: false)
  final int termId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'sort_order', required: false, includeIfNull: false)
  final int? sortOrder;

  // minimum: 1
  // maximum: 53
  @JsonKey(name: r'week_from', required: false, includeIfNull: false)
  final int? weekFrom;

  // minimum: 1
  // maximum: 53
  @JsonKey(name: r'week_to', required: false, includeIfNull: false)
  final int? weekTo;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CurriculumTopicWrite &&
          other.key == key &&
          other.termId == termId &&
          other.name == name &&
          other.sortOrder == sortOrder &&
          other.weekFrom == weekFrom &&
          other.weekTo == weekTo;

  @override
  int get hashCode =>
      key.hashCode +
      termId.hashCode +
      name.hashCode +
      sortOrder.hashCode +
      (weekFrom == null ? 0 : weekFrom.hashCode) +
      (weekTo == null ? 0 : weekTo.hashCode);

  factory CurriculumTopicWrite.fromJson(Map<String, dynamic> json) =>
      _$CurriculumTopicWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CurriculumTopicWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
