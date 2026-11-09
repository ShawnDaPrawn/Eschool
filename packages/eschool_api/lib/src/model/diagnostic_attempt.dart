//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/diagnostic_question.dart';
import 'package:eschool_api/src/model/diagnostic_status.dart';
import 'package:json_annotation/json_annotation.dart';

part 'diagnostic_attempt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiagnosticAttempt {
  /// Returns a new [DiagnosticAttempt] instance.
  DiagnosticAttempt({
    required this.id,

    required this.learnerId,

    required this.groupId,

    required this.subjectName,

    required this.instructions,

    required this.status,

    required this.answered,

    required this.remaining,

    required this.question,

    required this.message,

    required this.focusTopics,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: false)
  final String subjectName;

  @JsonKey(name: r'instructions', required: true, includeIfNull: false)
  final String instructions;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DiagnosticStatus.unknownDefaultOpenApi,
  )
  final DiagnosticStatus status;

  // minimum: 0
  // maximum: 20
  @JsonKey(name: r'answered', required: true, includeIfNull: false)
  final int answered;

  /// Questions still waiting; fewer may be asked, as a topic stops once two answers agree.
  // minimum: 0
  // maximum: 20
  @JsonKey(name: r'remaining', required: true, includeIfNull: false)
  final int remaining;

  @JsonKey(name: r'question', required: true, includeIfNull: true)
  final DiagnosticQuestion? question;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  /// Names of up to three topics practice begins with, once finished. Never a score.
  @JsonKey(name: r'focus_topics', required: true, includeIfNull: false)
  final List<String> focusTopics;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiagnosticAttempt &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.groupId == groupId &&
          other.subjectName == subjectName &&
          other.instructions == instructions &&
          other.status == status &&
          other.answered == answered &&
          other.remaining == remaining &&
          other.question == question &&
          other.message == message &&
          other.focusTopics == focusTopics;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      groupId.hashCode +
      subjectName.hashCode +
      instructions.hashCode +
      status.hashCode +
      answered.hashCode +
      remaining.hashCode +
      (question == null ? 0 : question.hashCode) +
      message.hashCode +
      focusTopics.hashCode;

  factory DiagnosticAttempt.fromJson(Map<String, dynamic> json) =>
      _$DiagnosticAttemptFromJson(json);

  Map<String, dynamic> toJson() => _$DiagnosticAttemptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
