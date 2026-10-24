//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_subject_standard_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolSubjectStandardWrite {
  /// Returns a new [SchoolSubjectStandardWrite] instance.
  SchoolSubjectStandardWrite({required this.standardId});

  // minimum: 1
  @JsonKey(name: r'standard_id', required: true, includeIfNull: true)
  final int? standardId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolSubjectStandardWrite && other.standardId == standardId;

  @override
  int get hashCode => (standardId == null ? 0 : standardId.hashCode);

  factory SchoolSubjectStandardWrite.fromJson(Map<String, dynamic> json) =>
      _$SchoolSubjectStandardWriteFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolSubjectStandardWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
