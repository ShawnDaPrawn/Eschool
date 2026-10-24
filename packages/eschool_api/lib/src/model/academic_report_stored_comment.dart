//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_stored_comment.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportStoredComment {
  /// Returns a new [AcademicReportStoredComment] instance.
  AcademicReportStoredComment({
    required this.scopeKey,

    required this.text,

    required this.revision,
  });

  @JsonKey(name: r'scope_key', required: true, includeIfNull: false)
  final String scopeKey;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportStoredComment &&
          other.scopeKey == scopeKey &&
          other.text == text &&
          other.revision == revision;

  @override
  int get hashCode => scopeKey.hashCode + text.hashCode + revision.hashCode;

  factory AcademicReportStoredComment.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportStoredCommentFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportStoredCommentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
