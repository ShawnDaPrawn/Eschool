//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/bursary_household_input.dart';
import 'package:json_annotation/json_annotation.dart';

part 'update_bursary_application_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class UpdateBursaryApplicationRequest {
  /// Returns a new [UpdateBursaryApplicationRequest] instance.
  UpdateBursaryApplicationRequest({
    required this.operationId,

    required this.revision,

    required this.household,
  });

  /// Retry the same request with the same value.
  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'household', required: true, includeIfNull: false)
  final BursaryHouseholdInput household;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is UpdateBursaryApplicationRequest &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.household == household;

  @override
  int get hashCode =>
      operationId.hashCode + revision.hashCode + household.hashCode;

  factory UpdateBursaryApplicationRequest.fromJson(Map<String, dynamic> json) =>
      _$UpdateBursaryApplicationRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$UpdateBursaryApplicationRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
