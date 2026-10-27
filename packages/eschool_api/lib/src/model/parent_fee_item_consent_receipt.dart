//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'parent_fee_item_consent_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ParentFeeItemConsentReceipt {
  /// Returns a new [ParentFeeItemConsentReceipt] instance.
  ParentFeeItemConsentReceipt({
    required this.id,

    required this.schoolId,

    required this.holderUserId,

    required this.feeAccountId,

    required this.feeItemId,

    required this.sessionYearId,

    required this.learnerId,

    required this.revision,

    required this.active,

    required this.recordedAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'holder_user_id', required: true, includeIfNull: false)
  final int holderUserId;

  // minimum: 1
  @JsonKey(name: r'fee_account_id', required: true, includeIfNull: false)
  final int feeAccountId;

  // minimum: 1
  @JsonKey(name: r'fee_item_id', required: true, includeIfNull: false)
  final int feeItemId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: true)
  final int? learnerId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @JsonKey(name: r'recorded_at', required: true, includeIfNull: false)
  final DateTime recordedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ParentFeeItemConsentReceipt &&
          other.id == id &&
          other.schoolId == schoolId &&
          other.holderUserId == holderUserId &&
          other.feeAccountId == feeAccountId &&
          other.feeItemId == feeItemId &&
          other.sessionYearId == sessionYearId &&
          other.learnerId == learnerId &&
          other.revision == revision &&
          other.active == active &&
          other.recordedAt == recordedAt;

  @override
  int get hashCode =>
      id.hashCode +
      schoolId.hashCode +
      holderUserId.hashCode +
      feeAccountId.hashCode +
      feeItemId.hashCode +
      sessionYearId.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode) +
      revision.hashCode +
      active.hashCode +
      recordedAt.hashCode;

  factory ParentFeeItemConsentReceipt.fromJson(Map<String, dynamic> json) =>
      _$ParentFeeItemConsentReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$ParentFeeItemConsentReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
