//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_point_category.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionPointCategory {
  /// Returns a new [RecognitionPointCategory] instance.
  RecognitionPointCategory({
    required this.id,

    required this.name,

    required this.defaultPoints,

    required this.minimumPoints,

    required this.maximumPoints,

    required this.active,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'default_points', required: true, includeIfNull: false)
  final int defaultPoints;

  @JsonKey(name: r'minimum_points', required: true, includeIfNull: false)
  final int minimumPoints;

  @JsonKey(name: r'maximum_points', required: true, includeIfNull: false)
  final int maximumPoints;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionPointCategory &&
          other.id == id &&
          other.name == name &&
          other.defaultPoints == defaultPoints &&
          other.minimumPoints == minimumPoints &&
          other.maximumPoints == maximumPoints &&
          other.active == active &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      defaultPoints.hashCode +
      minimumPoints.hashCode +
      maximumPoints.hashCode +
      active.hashCode +
      revision.hashCode;

  factory RecognitionPointCategory.fromJson(Map<String, dynamic> json) =>
      _$RecognitionPointCategoryFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionPointCategoryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
