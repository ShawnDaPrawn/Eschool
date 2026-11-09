//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/tutor_memory_topic.dart';
import 'package:json_annotation/json_annotation.dart';

part 'tutor_memory_subject.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorMemorySubject {
  /// Returns a new [TutorMemorySubject] instance.
  TutorMemorySubject({
    required this.subjectId,

    required this.subjectName,

    required this.sessions,

    required this.learningSteps,

    required this.explainedBack,

    required this.topics,

    required this.lastActiveOn,
  });

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: false)
  final String subjectName;

  // minimum: 0
  @JsonKey(name: r'sessions', required: true, includeIfNull: false)
  final int sessions;

  // minimum: 0
  @JsonKey(name: r'learning_steps', required: true, includeIfNull: false)
  final int learningSteps;

  // minimum: 0
  @JsonKey(name: r'explained_back', required: true, includeIfNull: false)
  final int explainedBack;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<TutorMemoryTopic> topics;

  @JsonKey(name: r'last_active_on', required: true, includeIfNull: false)
  final DateTime lastActiveOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorMemorySubject &&
          other.subjectId == subjectId &&
          other.subjectName == subjectName &&
          other.sessions == sessions &&
          other.learningSteps == learningSteps &&
          other.explainedBack == explainedBack &&
          other.topics == topics &&
          other.lastActiveOn == lastActiveOn;

  @override
  int get hashCode =>
      subjectId.hashCode +
      subjectName.hashCode +
      sessions.hashCode +
      learningSteps.hashCode +
      explainedBack.hashCode +
      topics.hashCode +
      lastActiveOn.hashCode;

  factory TutorMemorySubject.fromJson(Map<String, dynamic> json) =>
      _$TutorMemorySubjectFromJson(json);

  Map<String, dynamic> toJson() => _$TutorMemorySubjectToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
