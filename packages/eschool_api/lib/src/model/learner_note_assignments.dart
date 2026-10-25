//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_note_assignment.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learner_note_assignments.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerNoteAssignments {
  /// Returns a new [LearnerNoteAssignments] instance.
  LearnerNoteAssignments({required this.learnerId, required this.assignments});

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'assignments', required: true, includeIfNull: false)
  final List<LearnerNoteAssignment> assignments;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerNoteAssignments &&
          other.learnerId == learnerId &&
          other.assignments == assignments;

  @override
  int get hashCode => learnerId.hashCode + assignments.hashCode;

  factory LearnerNoteAssignments.fromJson(Map<String, dynamic> json) =>
      _$LearnerNoteAssignmentsFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerNoteAssignmentsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
