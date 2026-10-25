//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_award_definition.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionAwardDefinition {
  /// Returns a new [RecognitionAwardDefinition] instance.
  RecognitionAwardDefinition({
    required this.id,

    required this.awardCategoryId,

    required this.name,

    required this.criteria,

    required this.frequency,

    required this.active,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'award_category_id', required: true, includeIfNull: false)
  final int awardCategoryId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'criteria', required: true, includeIfNull: false)
  final String criteria;

  @JsonKey(name: r'frequency', required: true, includeIfNull: false)
  final String frequency;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionAwardDefinition &&
          other.id == id &&
          other.awardCategoryId == awardCategoryId &&
          other.name == name &&
          other.criteria == criteria &&
          other.frequency == frequency &&
          other.active == active &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      awardCategoryId.hashCode +
      name.hashCode +
      criteria.hashCode +
      frequency.hashCode +
      active.hashCode +
      revision.hashCode;

  factory RecognitionAwardDefinition.fromJson(Map<String, dynamic> json) =>
      _$RecognitionAwardDefinitionFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionAwardDefinitionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
