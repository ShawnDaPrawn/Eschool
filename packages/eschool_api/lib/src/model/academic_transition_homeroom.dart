//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_homeroom.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionHomeroom {
  /// Returns a new [AcademicTransitionHomeroom] instance.
  AcademicTransitionHomeroom({
    required this.id,

    required this.name,

    required this.gradeLevelId,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: true)
  final int? gradeLevelId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionHomeroom &&
          other.id == id &&
          other.name == name &&
          other.gradeLevelId == gradeLevelId;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (gradeLevelId == null ? 0 : gradeLevelId.hashCode);

  factory AcademicTransitionHomeroom.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionHomeroomFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionHomeroomToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
