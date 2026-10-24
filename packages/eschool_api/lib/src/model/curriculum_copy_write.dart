//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'curriculum_copy_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CurriculumCopyWrite {
  /// Returns a new [CurriculumCopyWrite] instance.
  CurriculumCopyWrite({required this.sessionYearId});

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CurriculumCopyWrite && other.sessionYearId == sessionYearId;

  @override
  int get hashCode => sessionYearId.hashCode;

  factory CurriculumCopyWrite.fromJson(Map<String, dynamic> json) =>
      _$CurriculumCopyWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CurriculumCopyWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
