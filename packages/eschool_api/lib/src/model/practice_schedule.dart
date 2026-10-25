//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_schedule.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeSchedule {
  /// Returns a new [PracticeSchedule] instance.
  PracticeSchedule({
    required this.version,

    required this.rating,

    required this.difficulty,

    required this.stabilityDays,

    required this.retrievabilityBefore,

    required this.intervalDays,
  });

  @JsonKey(name: r'version', required: true, includeIfNull: false)
  final String version;

  // minimum: 1
  // maximum: 4
  @JsonKey(name: r'rating', required: true, includeIfNull: false)
  final int rating;

  // minimum: 1
  // maximum: 10
  @JsonKey(name: r'difficulty', required: true, includeIfNull: false)
  final num difficulty;

  // minimum: 0.01
  // maximum: 36500
  @JsonKey(name: r'stability_days', required: true, includeIfNull: false)
  final num stabilityDays;

  // minimum: 0
  // maximum: 1
  @JsonKey(name: r'retrievability_before', required: true, includeIfNull: true)
  final num? retrievabilityBefore;

  // minimum: 1
  // maximum: 36500
  @JsonKey(name: r'interval_days', required: true, includeIfNull: false)
  final int intervalDays;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeSchedule &&
          other.version == version &&
          other.rating == rating &&
          other.difficulty == difficulty &&
          other.stabilityDays == stabilityDays &&
          other.retrievabilityBefore == retrievabilityBefore &&
          other.intervalDays == intervalDays;

  @override
  int get hashCode =>
      version.hashCode +
      rating.hashCode +
      difficulty.hashCode +
      stabilityDays.hashCode +
      (retrievabilityBefore == null ? 0 : retrievabilityBefore.hashCode) +
      intervalDays.hashCode;

  factory PracticeSchedule.fromJson(Map<String, dynamic> json) =>
      _$PracticeScheduleFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeScheduleToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
