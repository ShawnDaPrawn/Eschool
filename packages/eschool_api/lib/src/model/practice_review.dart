//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_schedule.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_review.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeReview {
  /// Returns a new [PracticeReview] instance.
  PracticeReview({
    required this.id,

    required this.entryId,

    required this.correct,

    required this.schedule,

    required this.reviewedOn,

    this.streakDays,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'entry_id', required: true, includeIfNull: false)
  final int entryId;

  @JsonKey(name: r'correct', required: true, includeIfNull: false)
  final bool correct;

  @JsonKey(name: r'schedule', required: true, includeIfNull: false)
  final PracticeSchedule schedule;

  @JsonKey(name: r'reviewed_on', required: true, includeIfNull: false)
  final String reviewedOn;

  // minimum: 0
  // maximum: 365
  @JsonKey(name: r'streak_days', required: false, includeIfNull: false)
  final int? streakDays;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeReview &&
          other.id == id &&
          other.entryId == entryId &&
          other.correct == correct &&
          other.schedule == schedule &&
          other.reviewedOn == reviewedOn &&
          other.streakDays == streakDays;

  @override
  int get hashCode =>
      id.hashCode +
      entryId.hashCode +
      correct.hashCode +
      schedule.hashCode +
      reviewedOn.hashCode +
      streakDays.hashCode;

  factory PracticeReview.fromJson(Map<String, dynamic> json) =>
      _$PracticeReviewFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeReviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
