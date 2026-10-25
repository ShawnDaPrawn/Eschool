//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'incident_submission_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class IncidentSubmissionReceipt {
  /// Returns a new [IncidentSubmissionReceipt] instance.
  IncidentSubmissionReceipt({
    required this.received,

    required this.submittedAt,

    required this.emergencyGuidance,
  });

  @JsonKey(name: r'received', required: true, includeIfNull: false)
  final bool received;

  @JsonKey(name: r'submitted_at', required: true, includeIfNull: true)
  final DateTime? submittedAt;

  @JsonKey(name: r'emergency_guidance', required: true, includeIfNull: false)
  final String emergencyGuidance;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is IncidentSubmissionReceipt &&
          other.received == received &&
          other.submittedAt == submittedAt &&
          other.emergencyGuidance == emergencyGuidance;

  @override
  int get hashCode =>
      received.hashCode +
      (submittedAt == null ? 0 : submittedAt.hashCode) +
      emergencyGuidance.hashCode;

  factory IncidentSubmissionReceipt.fromJson(Map<String, dynamic> json) =>
      _$IncidentSubmissionReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$IncidentSubmissionReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
