//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_exit_package.dart';
import 'package:eschool_api/src/model/academic_exit_document_choice.dart';
import 'package:eschool_api/src/model/academic_transition_state.dart';
import 'package:eschool_api/src/model/academic_exit_report_choice.dart';
import 'package:eschool_api/src/model/academic_transition_record.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_learner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionLearner {
  /// Returns a new [AcademicTransitionLearner] instance.
  AcademicTransitionLearner({
    required this.id,

    required this.name,

    required this.studentNumber,

    required this.state,

    required this.page,

    required this.lastPage,

    required this.transitions,

    required this.canExport,

    required this.reportChoicesTruncated,

    required this.reportChoices,

    required this.documentChoices,

    required this.exports,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'student_number', required: true, includeIfNull: true)
  final String? studentNumber;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final AcademicTransitionState state;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'transitions', required: true, includeIfNull: false)
  final List<AcademicTransitionRecord> transitions;

  @JsonKey(name: r'can_export', required: true, includeIfNull: false)
  final bool canExport;

  @JsonKey(
    name: r'report_choices_truncated',
    required: true,
    includeIfNull: false,
  )
  final bool reportChoicesTruncated;

  @JsonKey(name: r'report_choices', required: true, includeIfNull: false)
  final List<AcademicExitReportChoice> reportChoices;

  @JsonKey(name: r'document_choices', required: true, includeIfNull: false)
  final List<AcademicExitDocumentChoice> documentChoices;

  @JsonKey(name: r'exports', required: true, includeIfNull: false)
  final List<AcademicExitPackage> exports;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionLearner &&
          other.id == id &&
          other.name == name &&
          other.studentNumber == studentNumber &&
          other.state == state &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.transitions == transitions &&
          other.canExport == canExport &&
          other.reportChoicesTruncated == reportChoicesTruncated &&
          other.reportChoices == reportChoices &&
          other.documentChoices == documentChoices &&
          other.exports == exports;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (studentNumber == null ? 0 : studentNumber.hashCode) +
      state.hashCode +
      page.hashCode +
      lastPage.hashCode +
      transitions.hashCode +
      canExport.hashCode +
      reportChoicesTruncated.hashCode +
      reportChoices.hashCode +
      documentChoices.hashCode +
      exports.hashCode;

  factory AcademicTransitionLearner.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionLearnerFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionLearnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
