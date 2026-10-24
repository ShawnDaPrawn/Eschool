//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_comment_bank.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportCommentBank {
  /// Returns a new [AcademicReportCommentBank] instance.
  AcademicReportCommentBank({
    required this.id,

    required this.subjectId,

    required this.scope,

    required this.label,

    required this.text,

    required this.active,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: true)
  final int? subjectId;

  @JsonKey(name: r'scope', required: true, includeIfNull: false)
  final String scope;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportCommentBank &&
          other.id == id &&
          other.subjectId == subjectId &&
          other.scope == scope &&
          other.label == label &&
          other.text == text &&
          other.active == active &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      (subjectId == null ? 0 : subjectId.hashCode) +
      scope.hashCode +
      label.hashCode +
      text.hashCode +
      active.hashCode +
      revision.hashCode;

  factory AcademicReportCommentBank.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportCommentBankFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportCommentBankToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
