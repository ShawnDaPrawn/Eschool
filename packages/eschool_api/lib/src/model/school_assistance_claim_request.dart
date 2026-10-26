//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_claim_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceClaimRequest {
  /// Returns a new [SchoolAssistanceClaimRequest] instance.
  SchoolAssistanceClaimRequest({
    required this.operationId,

    required this.expectedRevision,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceClaimRequest &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision;

  @override
  int get hashCode => operationId.hashCode + expectedRevision.hashCode;

  factory SchoolAssistanceClaimRequest.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceClaimRequestFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceClaimRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
