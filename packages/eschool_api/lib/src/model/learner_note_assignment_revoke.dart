//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_note_assignment_revoke.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerNoteAssignmentRevoke {
  /// Returns a new [LearnerNoteAssignmentRevoke] instance.
  LearnerNoteAssignmentRevoke({
    required this.operationId,

    required this.expectedRevision,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerNoteAssignmentRevoke &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision;

  @override
  int get hashCode => operationId.hashCode + expectedRevision.hashCode;

  factory LearnerNoteAssignmentRevoke.fromJson(Map<String, dynamic> json) =>
      _$LearnerNoteAssignmentRevokeFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerNoteAssignmentRevokeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
