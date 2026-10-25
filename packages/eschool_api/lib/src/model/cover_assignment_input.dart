//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'cover_assignment_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CoverAssignmentInput {
  /// Returns a new [CoverAssignmentInput] instance.
  CoverAssignmentInput({
    required this.operationId,

    required this.revision,

    required this.substituteTeacherId,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'substitute_teacher_id', required: true, includeIfNull: false)
  final int substituteTeacherId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CoverAssignmentInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.substituteTeacherId == substituteTeacherId;

  @override
  int get hashCode =>
      operationId.hashCode + revision.hashCode + substituteTeacherId.hashCode;

  factory CoverAssignmentInput.fromJson(Map<String, dynamic> json) =>
      _$CoverAssignmentInputFromJson(json);

  Map<String, dynamic> toJson() => _$CoverAssignmentInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
