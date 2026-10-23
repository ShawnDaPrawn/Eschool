//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_grade_level.dart';
import 'package:eschool_api/src/model/academic_rating_level.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_reference.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReference {
  /// Returns a new [AcademicReference] instance.
  AcademicReference({required this.grades, required this.ratingScale});

  @JsonKey(name: r'grades', required: true, includeIfNull: false)
  final List<AcademicGradeLevel> grades;

  @JsonKey(name: r'rating_scale', required: true, includeIfNull: false)
  final List<AcademicRatingLevel> ratingScale;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReference &&
          other.grades == grades &&
          other.ratingScale == ratingScale;

  @override
  int get hashCode => grades.hashCode + ratingScale.hashCode;

  factory AcademicReference.fromJson(Map<String, dynamic> json) =>
      _$AcademicReferenceFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReferenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
