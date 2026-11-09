//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_goal_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeGoalInput {
  /// Returns a new [PracticeGoalInput] instance.
  PracticeGoalInput({
    required this.operationId,

    required this.revision,

    required this.daysPerWeek,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  // maximum: 7
  @JsonKey(name: r'days_per_week', required: true, includeIfNull: true)
  final int? daysPerWeek;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeGoalInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.daysPerWeek == daysPerWeek;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      (daysPerWeek == null ? 0 : daysPerWeek.hashCode);

  factory PracticeGoalInput.fromJson(Map<String, dynamic> json) =>
      _$PracticeGoalInputFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeGoalInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
