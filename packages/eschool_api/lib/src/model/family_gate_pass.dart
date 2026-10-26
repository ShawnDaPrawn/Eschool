//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_gate_pass.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyGatePass {
  /// Returns a new [FamilyGatePass] instance.
  FamilyGatePass({
    required this.id,

    required this.learnerId,

    required this.requestedBy,

    required this.approvedBy,

    required this.collector,

    required this.reason,

    required this.startsAt,

    required this.endsAt,

    required this.status,

    required this.revision,

    required this.collectedAt,

    this.learnerName,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'requested_by', required: true, includeIfNull: false)
  final int requestedBy;

  // minimum: 1
  @JsonKey(name: r'approved_by', required: true, includeIfNull: true)
  final int? approvedBy;

  @JsonKey(name: r'collector', required: true, includeIfNull: false)
  final String collector;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final DateTime startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final DateTime endsAt;

  @JsonKey(name: r'status', required: true, includeIfNull: false)
  final String status;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'collected_at', required: true, includeIfNull: true)
  final DateTime? collectedAt;

  @JsonKey(name: r'learner_name', required: false, includeIfNull: false)
  final String? learnerName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyGatePass &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.requestedBy == requestedBy &&
          other.approvedBy == approvedBy &&
          other.collector == collector &&
          other.reason == reason &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.status == status &&
          other.revision == revision &&
          other.collectedAt == collectedAt &&
          other.learnerName == learnerName;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      requestedBy.hashCode +
      (approvedBy == null ? 0 : approvedBy.hashCode) +
      collector.hashCode +
      reason.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      status.hashCode +
      revision.hashCode +
      (collectedAt == null ? 0 : collectedAt.hashCode) +
      learnerName.hashCode;

  factory FamilyGatePass.fromJson(Map<String, dynamic> json) =>
      _$FamilyGatePassFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyGatePassToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
