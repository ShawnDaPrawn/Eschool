//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_motivation_goal.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeMotivationGoal {
  /// Returns a new [PracticeMotivationGoal] instance.
  PracticeMotivationGoal({
    required this.daysPerWeek,

    required this.practisedDaysThisWeek,

    required this.met,

    required this.weekStart,

    required this.revision,
  });

  // minimum: 1
  // maximum: 7
  @JsonKey(name: r'days_per_week', required: true, includeIfNull: true)
  final int? daysPerWeek;

  // minimum: 0
  // maximum: 7
  @JsonKey(
    name: r'practised_days_this_week',
    required: true,
    includeIfNull: false,
  )
  final int practisedDaysThisWeek;

  @JsonKey(name: r'met', required: true, includeIfNull: false)
  final bool met;

  @JsonKey(name: r'week_start', required: true, includeIfNull: false)
  final DateTime weekStart;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeMotivationGoal &&
          other.daysPerWeek == daysPerWeek &&
          other.practisedDaysThisWeek == practisedDaysThisWeek &&
          other.met == met &&
          other.weekStart == weekStart &&
          other.revision == revision;

  @override
  int get hashCode =>
      (daysPerWeek == null ? 0 : daysPerWeek.hashCode) +
      practisedDaysThisWeek.hashCode +
      met.hashCode +
      weekStart.hashCode +
      revision.hashCode;

  factory PracticeMotivationGoal.fromJson(Map<String, dynamic> json) =>
      _$PracticeMotivationGoalFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeMotivationGoalToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
