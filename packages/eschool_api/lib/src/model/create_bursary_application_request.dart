//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/bursary_household_input.dart';
import 'package:json_annotation/json_annotation.dart';

part 'create_bursary_application_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CreateBursaryApplicationRequest {
  /// Returns a new [CreateBursaryApplicationRequest] instance.
  CreateBursaryApplicationRequest({
    required this.operationId,

    required this.learnerId,

    required this.sessionYearId,

    required this.household,
  });

  /// Retry the same request with the same value.
  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'household', required: true, includeIfNull: false)
  final BursaryHouseholdInput household;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CreateBursaryApplicationRequest &&
          other.operationId == operationId &&
          other.learnerId == learnerId &&
          other.sessionYearId == sessionYearId &&
          other.household == household;

  @override
  int get hashCode =>
      operationId.hashCode +
      learnerId.hashCode +
      sessionYearId.hashCode +
      household.hashCode;

  factory CreateBursaryApplicationRequest.fromJson(Map<String, dynamic> json) =>
      _$CreateBursaryApplicationRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$CreateBursaryApplicationRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
