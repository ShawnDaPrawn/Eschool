//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_reassign_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceReassignRequest {
  /// Returns a new [SchoolAssistanceReassignRequest] instance.
  SchoolAssistanceReassignRequest({
    required this.operationId,

    required this.expectedRevision,

    required this.assignedUserId,

    required this.reason,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  // minimum: 1
  @JsonKey(name: r'assigned_user_id', required: true, includeIfNull: false)
  final int assignedUserId;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceReassignRequest &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision &&
          other.assignedUserId == assignedUserId &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode +
      expectedRevision.hashCode +
      assignedUserId.hashCode +
      reason.hashCode;

  factory SchoolAssistanceReassignRequest.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceReassignRequestFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SchoolAssistanceReassignRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
