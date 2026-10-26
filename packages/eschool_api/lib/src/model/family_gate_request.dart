//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_gate_request.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyGateRequest {
  /// Returns a new [FamilyGateRequest] instance.
  FamilyGateRequest({
    required this.operationId,

    required this.learnerId,

    required this.collector,

    required this.reason,

    required this.startsAt,

    required this.endsAt,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'collector', required: true, includeIfNull: false)
  final String collector;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final String startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final String endsAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyGateRequest &&
          other.operationId == operationId &&
          other.learnerId == learnerId &&
          other.collector == collector &&
          other.reason == reason &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt;

  @override
  int get hashCode =>
      operationId.hashCode +
      learnerId.hashCode +
      collector.hashCode +
      reason.hashCode +
      startsAt.hashCode +
      endsAt.hashCode;

  factory FamilyGateRequest.fromJson(Map<String, dynamic> json) =>
      _$FamilyGateRequestFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyGateRequestToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
