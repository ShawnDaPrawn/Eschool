//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_comment_row.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportCommentRow {
  /// Returns a new [AcademicReportCommentRow] instance.
  AcademicReportCommentRow({
    required this.cardId,

    required this.batchId,

    required this.batchRevision,

    required this.learnerId,

    required this.learnerName,

    required this.periodName,

    required this.subjectId,

    required this.scopeLabel,

    required this.revision,

    required this.text,
  });

  // minimum: 1
  @JsonKey(name: r'card_id', required: true, includeIfNull: false)
  final int cardId;

  // minimum: 1
  @JsonKey(name: r'batch_id', required: true, includeIfNull: false)
  final int batchId;

  // minimum: 1
  @JsonKey(name: r'batch_revision', required: true, includeIfNull: false)
  final int batchRevision;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  @JsonKey(name: r'period_name', required: true, includeIfNull: false)
  final String periodName;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: true)
  final int? subjectId;

  @JsonKey(name: r'scope_label', required: true, includeIfNull: false)
  final String scopeLabel;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportCommentRow &&
          other.cardId == cardId &&
          other.batchId == batchId &&
          other.batchRevision == batchRevision &&
          other.learnerId == learnerId &&
          other.learnerName == learnerName &&
          other.periodName == periodName &&
          other.subjectId == subjectId &&
          other.scopeLabel == scopeLabel &&
          other.revision == revision &&
          other.text == text;

  @override
  int get hashCode =>
      cardId.hashCode +
      batchId.hashCode +
      batchRevision.hashCode +
      learnerId.hashCode +
      learnerName.hashCode +
      periodName.hashCode +
      (subjectId == null ? 0 : subjectId.hashCode) +
      scopeLabel.hashCode +
      revision.hashCode +
      text.hashCode;

  factory AcademicReportCommentRow.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportCommentRowFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportCommentRowToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
