//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'parent_fee_item_consent_change.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ParentFeeItemConsentChange {
  /// Returns a new [ParentFeeItemConsentChange] instance.
  ParentFeeItemConsentChange({
    required this.operationId,

    required this.sessionYearId,

    this.learnerId,

    required this.revision,

    required this.active,

    this.fingerprint,

    required this.confirmed,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: false, includeIfNull: false)
  final int? learnerId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  /// Required for acceptance; use the current reviewed offer. Withdrawal may omit it.
  @JsonKey(name: r'fingerprint', required: false, includeIfNull: false)
  final String? fingerprint;

  /// Must be true to confirm the reviewed approval change.
  @JsonKey(name: r'confirmed', required: true, includeIfNull: false)
  final bool confirmed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ParentFeeItemConsentChange &&
          other.operationId == operationId &&
          other.sessionYearId == sessionYearId &&
          other.learnerId == learnerId &&
          other.revision == revision &&
          other.active == active &&
          other.fingerprint == fingerprint &&
          other.confirmed == confirmed;

  @override
  int get hashCode =>
      operationId.hashCode +
      sessionYearId.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode) +
      revision.hashCode +
      active.hashCode +
      (fingerprint == null ? 0 : fingerprint.hashCode) +
      confirmed.hashCode;

  factory ParentFeeItemConsentChange.fromJson(Map<String, dynamic> json) =>
      _$ParentFeeItemConsentChangeFromJson(json);

  Map<String, dynamic> toJson() => _$ParentFeeItemConsentChangeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
