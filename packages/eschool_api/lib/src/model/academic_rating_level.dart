//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_rating_level.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRatingLevel {
  /// Returns a new [AcademicRatingLevel] instance.
  AcademicRatingLevel({
    required this.level,

    required this.name,

    required this.minBasisPoints,

    required this.maxBasisPoints,
  });

  // minimum: 1
  // maximum: 7
  @JsonKey(name: r'level', required: true, includeIfNull: false)
  final int level;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'min_basis_points', required: true, includeIfNull: false)
  final int minBasisPoints;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'max_basis_points', required: true, includeIfNull: false)
  final int maxBasisPoints;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRatingLevel &&
          other.level == level &&
          other.name == name &&
          other.minBasisPoints == minBasisPoints &&
          other.maxBasisPoints == maxBasisPoints;

  @override
  int get hashCode =>
      level.hashCode +
      name.hashCode +
      minBasisPoints.hashCode +
      maxBasisPoints.hashCode;

  factory AcademicRatingLevel.fromJson(Map<String, dynamic> json) =>
      _$AcademicRatingLevelFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicRatingLevelToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
