//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/subject_standard.dart';
import 'package:json_annotation/json_annotation.dart';

part 'school_subject_standard.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolSubjectStandard {
  /// Returns a new [SchoolSubjectStandard] instance.
  SchoolSubjectStandard({
    required this.subjectId,

    required this.name,

    required this.code,

    required this.type,

    required this.mediumId,

    required this.standardId,

    required this.standard,
  });

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'code', required: true, includeIfNull: true)
  final String? code;

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  // minimum: 1
  @JsonKey(name: r'medium_id', required: true, includeIfNull: false)
  final int mediumId;

  // minimum: 1
  @JsonKey(name: r'standard_id', required: true, includeIfNull: true)
  final int? standardId;

  @JsonKey(name: r'standard', required: true, includeIfNull: true)
  final SubjectStandard? standard;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolSubjectStandard &&
          other.subjectId == subjectId &&
          other.name == name &&
          other.code == code &&
          other.type == type &&
          other.mediumId == mediumId &&
          other.standardId == standardId &&
          other.standard == standard;

  @override
  int get hashCode =>
      subjectId.hashCode +
      name.hashCode +
      (code == null ? 0 : code.hashCode) +
      type.hashCode +
      mediumId.hashCode +
      (standardId == null ? 0 : standardId.hashCode) +
      (standard == null ? 0 : standard.hashCode);

  factory SchoolSubjectStandard.fromJson(Map<String, dynamic> json) =>
      _$SchoolSubjectStandardFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolSubjectStandardToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
