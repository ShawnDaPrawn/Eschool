//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_note_assignment_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerNoteAssignmentWrite {
  /// Returns a new [LearnerNoteAssignmentWrite] instance.
  LearnerNoteAssignmentWrite({
    required this.operationId,

    required this.expectedRevision,

    required this.startsOn,

    required this.endsOn,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerNoteAssignmentWrite &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn;

  @override
  int get hashCode =>
      operationId.hashCode +
      expectedRevision.hashCode +
      startsOn.hashCode +
      endsOn.hashCode;

  factory LearnerNoteAssignmentWrite.fromJson(Map<String, dynamic> json) =>
      _$LearnerNoteAssignmentWriteFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerNoteAssignmentWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
