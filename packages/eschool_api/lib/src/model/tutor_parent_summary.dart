//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'tutor_parent_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorParentSummary {
  /// Returns a new [TutorParentSummary] instance.
  TutorParentSummary({
    required this.schoolId,

    required this.readerId,

    required this.learnerId,

    required this.subjectId,

    required this.subjectName,

    required this.learningSessions,

    required this.learningSteps,

    required this.suggestionKey,

    required this.containsRawChat,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: false)
  final String subjectName;

  // minimum: 0
  @JsonKey(name: r'learning_sessions', required: true, includeIfNull: false)
  final int learningSessions;

  // minimum: 0
  @JsonKey(name: r'learning_steps', required: true, includeIfNull: false)
  final int learningSteps;

  @JsonKey(name: r'suggestion_key', required: true, includeIfNull: false)
  final String suggestionKey;

  @JsonKey(name: r'contains_raw_chat', required: true, includeIfNull: false)
  final bool containsRawChat;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorParentSummary &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.learnerId == learnerId &&
          other.subjectId == subjectId &&
          other.subjectName == subjectName &&
          other.learningSessions == learningSessions &&
          other.learningSteps == learningSteps &&
          other.suggestionKey == suggestionKey &&
          other.containsRawChat == containsRawChat;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      learnerId.hashCode +
      subjectId.hashCode +
      subjectName.hashCode +
      learningSessions.hashCode +
      learningSteps.hashCode +
      suggestionKey.hashCode +
      containsRawChat.hashCode;

  factory TutorParentSummary.fromJson(Map<String, dynamic> json) =>
      _$TutorParentSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$TutorParentSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
