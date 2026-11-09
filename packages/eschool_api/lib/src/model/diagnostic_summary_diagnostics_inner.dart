//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/diagnostic_status.dart';
import 'package:json_annotation/json_annotation.dart';

part 'diagnostic_summary_diagnostics_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiagnosticSummaryDiagnosticsInner {
  /// Returns a new [DiagnosticSummaryDiagnosticsInner] instance.
  DiagnosticSummaryDiagnosticsInner({
    required this.groupId,

    required this.subjectName,

    required this.status,

    required this.attemptId,

    required this.canStart,

    required this.message,

    required this.focusTopics,
  });

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: false)
  final String subjectName;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DiagnosticStatus.unknownDefaultOpenApi,
  )
  final DiagnosticStatus status;

  /// The learner's own attempt; always null for a guardian.
  // minimum: 1
  @JsonKey(name: r'attempt_id', required: true, includeIfNull: true)
  final int? attemptId;

  @JsonKey(name: r'can_start', required: true, includeIfNull: false)
  final bool canStart;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'focus_topics', required: true, includeIfNull: false)
  final List<String> focusTopics;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiagnosticSummaryDiagnosticsInner &&
          other.groupId == groupId &&
          other.subjectName == subjectName &&
          other.status == status &&
          other.attemptId == attemptId &&
          other.canStart == canStart &&
          other.message == message &&
          other.focusTopics == focusTopics;

  @override
  int get hashCode =>
      groupId.hashCode +
      subjectName.hashCode +
      status.hashCode +
      (attemptId == null ? 0 : attemptId.hashCode) +
      canStart.hashCode +
      message.hashCode +
      focusTopics.hashCode;

  factory DiagnosticSummaryDiagnosticsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$DiagnosticSummaryDiagnosticsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$DiagnosticSummaryDiagnosticsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
