//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_note_assignment.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerNoteAssignment {
  /// Returns a new [LearnerNoteAssignment] instance.
  LearnerNoteAssignment({
    required this.id,

    required this.learnerId,

    required this.staffUserId,

    required this.staffName,

    required this.startsOn,

    required this.endsOn,

    required this.revision,

    this.revoked,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'staff_user_id', required: true, includeIfNull: false)
  final int staffUserId;

  @JsonKey(name: r'staff_name', required: true, includeIfNull: false)
  final String staffName;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  /// Optional for older servers; true means access ended immediately, while original grant dates remain retained.
  @JsonKey(name: r'revoked', required: false, includeIfNull: false)
  final bool? revoked;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerNoteAssignment &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.staffUserId == staffUserId &&
          other.staffName == staffName &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.revision == revision &&
          other.revoked == revoked;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      staffUserId.hashCode +
      staffName.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      revision.hashCode +
      revoked.hashCode;

  factory LearnerNoteAssignment.fromJson(Map<String, dynamic> json) =>
      _$LearnerNoteAssignmentFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerNoteAssignmentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
