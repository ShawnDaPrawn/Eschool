//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_comment_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportCommentWrite {
  /// Returns a new [AcademicReportCommentWrite] instance.
  AcademicReportCommentWrite({
    required this.subjectId,

    required this.text,

    this.bankId,

    required this.revision,

    required this.batchRevision,

    this.reason,
  });

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: true)
  final int? subjectId;

  @JsonKey(name: r'text', required: true, includeIfNull: true)
  final String? text;

  // minimum: 1
  @JsonKey(name: r'bank_id', required: false, includeIfNull: false)
  final int? bankId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'batch_revision', required: true, includeIfNull: false)
  final int batchRevision;

  @JsonKey(name: r'reason', required: false, includeIfNull: false)
  final String? reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportCommentWrite &&
          other.subjectId == subjectId &&
          other.text == text &&
          other.bankId == bankId &&
          other.revision == revision &&
          other.batchRevision == batchRevision &&
          other.reason == reason;

  @override
  int get hashCode =>
      (subjectId == null ? 0 : subjectId.hashCode) +
      (text == null ? 0 : text.hashCode) +
      (bankId == null ? 0 : bankId.hashCode) +
      revision.hashCode +
      batchRevision.hashCode +
      (reason == null ? 0 : reason.hashCode);

  factory AcademicReportCommentWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportCommentWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportCommentWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
