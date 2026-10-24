//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_transition_enrolment.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTransitionEnrolment {
  /// Returns a new [AcademicTransitionEnrolment] instance.
  AcademicTransitionEnrolment({
    required this.id,

    required this.sessionYearId,

    required this.classSectionId,

    required this.startsOn,

    required this.endsOn,

    required this.cancelledAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'class_section_id', required: true, includeIfNull: false)
  final int classSectionId;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: true)
  final String? endsOn;

  @JsonKey(name: r'cancelled_at', required: true, includeIfNull: true)
  final DateTime? cancelledAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTransitionEnrolment &&
          other.id == id &&
          other.sessionYearId == sessionYearId &&
          other.classSectionId == classSectionId &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.cancelledAt == cancelledAt;

  @override
  int get hashCode =>
      id.hashCode +
      sessionYearId.hashCode +
      classSectionId.hashCode +
      startsOn.hashCode +
      (endsOn == null ? 0 : endsOn.hashCode) +
      (cancelledAt == null ? 0 : cancelledAt.hashCode);

  factory AcademicTransitionEnrolment.fromJson(Map<String, dynamic> json) =>
      _$AcademicTransitionEnrolmentFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTransitionEnrolmentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
