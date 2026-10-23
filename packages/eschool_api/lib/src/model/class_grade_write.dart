//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'class_grade_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ClassGradeWrite {
  /// Returns a new [ClassGradeWrite] instance.
  ClassGradeWrite({required this.gradeLevelId});

  /// Reference ID, or explicit null to clear the mapping.
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: true)
  final int? gradeLevelId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ClassGradeWrite && other.gradeLevelId == gradeLevelId;

  @override
  int get hashCode => (gradeLevelId == null ? 0 : gradeLevelId.hashCode);

  factory ClassGradeWrite.fromJson(Map<String, dynamic> json) =>
      _$ClassGradeWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ClassGradeWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
