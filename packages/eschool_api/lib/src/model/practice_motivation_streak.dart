//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_motivation_streak.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeMotivationStreak {
  /// Returns a new [PracticeMotivationStreak] instance.
  PracticeMotivationStreak({
    required this.currentDays,

    required this.bestDays,

    required this.restDayAvailable,
  });

  // minimum: 0
  @JsonKey(name: r'current_days', required: true, includeIfNull: false)
  final int currentDays;

  // minimum: 0
  @JsonKey(name: r'best_days', required: true, includeIfNull: false)
  final int bestDays;

  @JsonKey(name: r'rest_day_available', required: true, includeIfNull: false)
  final bool restDayAvailable;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeMotivationStreak &&
          other.currentDays == currentDays &&
          other.bestDays == bestDays &&
          other.restDayAvailable == restDayAvailable;

  @override
  int get hashCode =>
      currentDays.hashCode + bestDays.hashCode + restDayAvailable.hashCode;

  factory PracticeMotivationStreak.fromJson(Map<String, dynamic> json) =>
      _$PracticeMotivationStreakFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeMotivationStreakToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
