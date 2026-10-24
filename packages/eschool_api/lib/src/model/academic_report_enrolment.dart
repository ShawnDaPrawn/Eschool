//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_enrolment.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportEnrolment {
  /// Returns a new [AcademicReportEnrolment] instance.
  AcademicReportEnrolment({
    required this.id,

    required this.startsOn,

    required this.endsOn,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: true)
  final String? endsOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportEnrolment &&
          other.id == id &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn;

  @override
  int get hashCode =>
      id.hashCode + startsOn.hashCode + (endsOn == null ? 0 : endsOn.hashCode);

  factory AcademicReportEnrolment.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportEnrolmentFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportEnrolmentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
