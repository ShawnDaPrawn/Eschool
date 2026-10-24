//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_comment_bank_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportCommentBankWrite {
  /// Returns a new [AcademicReportCommentBankWrite] instance.
  AcademicReportCommentBankWrite({
    this.id,

    this.revision,

    required this.subjectId,

    required this.scope,

    required this.label,

    required this.text,

    required this.active,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: false, includeIfNull: false)
  final int? id;

  // minimum: 1
  @JsonKey(name: r'revision', required: false, includeIfNull: false)
  final int? revision;

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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportCommentBankWrite &&
          other.id == id &&
          other.revision == revision &&
          other.subjectId == subjectId &&
          other.scope == scope &&
          other.label == label &&
          other.text == text &&
          other.active == active;

  @override
  int get hashCode =>
      id.hashCode +
      revision.hashCode +
      (subjectId == null ? 0 : subjectId.hashCode) +
      scope.hashCode +
      label.hashCode +
      text.hashCode +
      active.hashCode;

  factory AcademicReportCommentBankWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportCommentBankWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportCommentBankWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
