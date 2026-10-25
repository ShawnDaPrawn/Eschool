//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_configure.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionConfigure {
  /// Returns a new [RecognitionConfigure] instance.
  RecognitionConfigure({
    required this.operationId,

    required this.revision,

    required this.dailyStaffCap,

    required this.publicPointsAllowed,

    required this.principalApprovalRequired,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  // maximum: 1000000
  @JsonKey(name: r'daily_staff_cap', required: true, includeIfNull: false)
  final int dailyStaffCap;

  @JsonKey(name: r'public_points_allowed', required: true, includeIfNull: false)
  final bool publicPointsAllowed;

  @JsonKey(
    name: r'principal_approval_required',
    required: true,
    includeIfNull: false,
  )
  final bool principalApprovalRequired;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionConfigure &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.dailyStaffCap == dailyStaffCap &&
          other.publicPointsAllowed == publicPointsAllowed &&
          other.principalApprovalRequired == principalApprovalRequired;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      dailyStaffCap.hashCode +
      publicPointsAllowed.hashCode +
      principalApprovalRequired.hashCode;

  factory RecognitionConfigure.fromJson(Map<String, dynamic> json) =>
      _$RecognitionConfigureFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionConfigureToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
