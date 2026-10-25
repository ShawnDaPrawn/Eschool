//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_assignment_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingAssignmentInput {
  /// Returns a new [WellbeingAssignmentInput] instance.
  WellbeingAssignmentInput({
    required this.operationId,

    required this.revision,

    required this.userId,

    required this.startsOn,

    required this.endsOn,

    required this.active,

    required this.reason,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'user_id', required: true, includeIfNull: false)
  final int userId;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingAssignmentInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.userId == userId &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.active == active &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      userId.hashCode +
      startsOn.hashCode +
      endsOn.hashCode +
      active.hashCode +
      reason.hashCode;

  factory WellbeingAssignmentInput.fromJson(Map<String, dynamic> json) =>
      _$WellbeingAssignmentInputFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingAssignmentInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
