//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_exit_issue.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicExitIssue {
  /// Returns a new [AcademicExitIssue] instance.
  AcademicExitIssue({required this.type, required this.id});

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicExitIssue && other.type == type && other.id == id;

  @override
  int get hashCode => type.hashCode + id.hashCode;

  factory AcademicExitIssue.fromJson(Map<String, dynamic> json) =>
      _$AcademicExitIssueFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicExitIssueToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
