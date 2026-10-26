//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/exam_coach_licence.dart';
import 'package:eschool_api/src/model/exam_coach_question_write.dart';
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_paper_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachPaperWrite {
  /// Returns a new [ExamCoachPaperWrite] instance.
  ExamCoachPaperWrite({
    required this.operationId,

    required this.expectedRevision,

    required this.title,

    required this.framework,

    required this.paperYear,

    required this.paperCode,

    required this.groupIds,

    required this.licence,

    required this.questions,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'framework', required: true, includeIfNull: false)
  final String framework;

  // minimum: 1850
  // maximum: 2100
  @JsonKey(name: r'paper_year', required: true, includeIfNull: false)
  final int paperYear;

  @JsonKey(name: r'paper_code', required: true, includeIfNull: false)
  final String paperCode;

  @JsonKey(name: r'group_ids', required: true, includeIfNull: false)
  final List<int> groupIds;

  @JsonKey(name: r'licence', required: true, includeIfNull: false)
  final ExamCoachLicence licence;

  @JsonKey(name: r'questions', required: true, includeIfNull: false)
  final List<ExamCoachQuestionWrite> questions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachPaperWrite &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision &&
          other.title == title &&
          other.framework == framework &&
          other.paperYear == paperYear &&
          other.paperCode == paperCode &&
          other.groupIds == groupIds &&
          other.licence == licence &&
          other.questions == questions;

  @override
  int get hashCode =>
      operationId.hashCode +
      expectedRevision.hashCode +
      title.hashCode +
      framework.hashCode +
      paperYear.hashCode +
      paperCode.hashCode +
      groupIds.hashCode +
      licence.hashCode +
      questions.hashCode;

  factory ExamCoachPaperWrite.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachPaperWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachPaperWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
