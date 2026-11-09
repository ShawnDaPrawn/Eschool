//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_options_limits.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestOptionsLimits {
  /// Returns a new [PracticeTestOptionsLimits] instance.
  PracticeTestOptionsLimits({
    required this.topicsMax,

    required this.questionsMin,

    required this.questionsMax,

    required this.minutesMin,

    required this.minutesMax,

    required this.windowDaysMax,
  });

  // minimum: 1
  @JsonKey(name: r'topics_max', required: true, includeIfNull: false)
  final int topicsMax;

  // minimum: 1
  @JsonKey(name: r'questions_min', required: true, includeIfNull: false)
  final int questionsMin;

  // minimum: 1
  @JsonKey(name: r'questions_max', required: true, includeIfNull: false)
  final int questionsMax;

  // minimum: 1
  @JsonKey(name: r'minutes_min', required: true, includeIfNull: false)
  final int minutesMin;

  // minimum: 1
  @JsonKey(name: r'minutes_max', required: true, includeIfNull: false)
  final int minutesMax;

  // minimum: 1
  @JsonKey(name: r'window_days_max', required: true, includeIfNull: false)
  final int windowDaysMax;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestOptionsLimits &&
          other.topicsMax == topicsMax &&
          other.questionsMin == questionsMin &&
          other.questionsMax == questionsMax &&
          other.minutesMin == minutesMin &&
          other.minutesMax == minutesMax &&
          other.windowDaysMax == windowDaysMax;

  @override
  int get hashCode =>
      topicsMax.hashCode +
      questionsMin.hashCode +
      questionsMax.hashCode +
      minutesMin.hashCode +
      minutesMax.hashCode +
      windowDaysMax.hashCode;

  factory PracticeTestOptionsLimits.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestOptionsLimitsFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestOptionsLimitsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
