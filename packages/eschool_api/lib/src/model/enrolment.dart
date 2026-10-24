//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'enrolment.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class Enrolment {
  /// Returns a new [Enrolment] instance.
  Enrolment({
    this.cancelledAt,

    required this.id,

    required this.learnerId,

    required this.classSectionId,

    required this.sessionYearId,

    required this.startsOn,

    required this.endsOn,

    required this.reason,

    required this.legacyDates,

    required this.previousEnrolmentId,
  });

  @JsonKey(name: r'cancelled_at', required: false, includeIfNull: false)
  final DateTime? cancelledAt;

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'class_section_id', required: true, includeIfNull: false)
  final int classSectionId;

  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final DateTime startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: true)
  final DateTime? endsOn;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @JsonKey(name: r'legacy_dates', required: true, includeIfNull: false)
  final bool legacyDates;

  @JsonKey(name: r'previous_enrolment_id', required: true, includeIfNull: true)
  final int? previousEnrolmentId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is Enrolment &&
          other.cancelledAt == cancelledAt &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.classSectionId == classSectionId &&
          other.sessionYearId == sessionYearId &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.reason == reason &&
          other.legacyDates == legacyDates &&
          other.previousEnrolmentId == previousEnrolmentId;

  @override
  int get hashCode =>
      (cancelledAt == null ? 0 : cancelledAt.hashCode) +
      id.hashCode +
      learnerId.hashCode +
      classSectionId.hashCode +
      sessionYearId.hashCode +
      startsOn.hashCode +
      (endsOn == null ? 0 : endsOn.hashCode) +
      reason.hashCode +
      legacyDates.hashCode +
      (previousEnrolmentId == null ? 0 : previousEnrolmentId.hashCode);

  factory Enrolment.fromJson(Map<String, dynamic> json) =>
      _$EnrolmentFromJson(json);

  Map<String, dynamic> toJson() => _$EnrolmentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
