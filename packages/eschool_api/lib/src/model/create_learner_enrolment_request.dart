//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'create_learner_enrolment_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CreateLearnerEnrolmentRequest {
  /// Returns a new [CreateLearnerEnrolmentRequest] instance.
  CreateLearnerEnrolmentRequest({
    required this.classSectionId,

    required this.sessionYearId,

    required this.startsOn,

    required this.reason,
  });

  @JsonKey(name: r'class_section_id', required: true, includeIfNull: false)
  final int classSectionId;

  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final DateTime startsOn;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CreateLearnerEnrolmentRequest &&
          other.classSectionId == classSectionId &&
          other.sessionYearId == sessionYearId &&
          other.startsOn == startsOn &&
          other.reason == reason;

  @override
  int get hashCode =>
      classSectionId.hashCode +
      sessionYearId.hashCode +
      startsOn.hashCode +
      reason.hashCode;

  factory CreateLearnerEnrolmentRequest.fromJson(Map<String, dynamic> json) =>
      _$CreateLearnerEnrolmentRequestFromJson(json);

  Map<String, dynamic> toJson() => _$CreateLearnerEnrolmentRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
