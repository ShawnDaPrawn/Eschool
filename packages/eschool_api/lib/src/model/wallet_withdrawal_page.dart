//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wallet_withdrawal_slot.dart';
import 'package:eschool_api/src/model/wallet_withdrawal_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_withdrawal_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletWithdrawalPage {
  /// Returns a new [WalletWithdrawalPage] instance.
  WalletWithdrawalPage({
    required this.schoolId,

    required this.actorUserId,

    required this.currency,

    required this.isAdult,

    required this.readOnly,

    required this.canRequest,

    required this.policyRevision,

    required this.cashThresholdCents,

    required this.cashAtBoundary,

    required this.timezone,

    required this.slots,

    required this.currentPage,

    required this.perPage,

    required this.total,

    required this.items,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'actor_user_id', required: true, includeIfNull: false)
  final int actorUserId;

  @JsonKey(name: r'currency', required: true, includeIfNull: true)
  final String? currency;

  @JsonKey(name: r'is_adult', required: true, includeIfNull: false)
  final bool isAdult;

  @JsonKey(name: r'read_only', required: true, includeIfNull: false)
  final bool readOnly;

  @JsonKey(name: r'can_request', required: true, includeIfNull: false)
  final bool canRequest;

  // minimum: 1
  @JsonKey(name: r'policy_revision', required: true, includeIfNull: true)
  final int? policyRevision;

  // minimum: 1
  // maximum: 100000000000
  @JsonKey(name: r'cash_threshold_cents', required: true, includeIfNull: true)
  final int? cashThresholdCents;

  @JsonKey(name: r'cash_at_boundary', required: true, includeIfNull: true)
  final bool? cashAtBoundary;

  @JsonKey(name: r'timezone', required: true, includeIfNull: false)
  final String timezone;

  @JsonKey(name: r'slots', required: true, includeIfNull: false)
  final List<WalletWithdrawalSlot> slots;

  // minimum: 1
  // maximum: 1000000
  @JsonKey(name: r'current_page', required: true, includeIfNull: false)
  final int currentPage;

  // minimum: 1
  // maximum: 50
  @JsonKey(name: r'per_page', required: true, includeIfNull: false)
  final int perPage;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<WalletWithdrawalReceipt> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletWithdrawalPage &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.currency == currency &&
          other.isAdult == isAdult &&
          other.readOnly == readOnly &&
          other.canRequest == canRequest &&
          other.policyRevision == policyRevision &&
          other.cashThresholdCents == cashThresholdCents &&
          other.cashAtBoundary == cashAtBoundary &&
          other.timezone == timezone &&
          other.slots == slots &&
          other.currentPage == currentPage &&
          other.perPage == perPage &&
          other.total == total &&
          other.items == items;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      (currency == null ? 0 : currency.hashCode) +
      isAdult.hashCode +
      readOnly.hashCode +
      canRequest.hashCode +
      (policyRevision == null ? 0 : policyRevision.hashCode) +
      (cashThresholdCents == null ? 0 : cashThresholdCents.hashCode) +
      (cashAtBoundary == null ? 0 : cashAtBoundary.hashCode) +
      timezone.hashCode +
      slots.hashCode +
      currentPage.hashCode +
      perPage.hashCode +
      total.hashCode +
      items.hashCode;

  factory WalletWithdrawalPage.fromJson(Map<String, dynamic> json) =>
      _$WalletWithdrawalPageFromJson(json);

  Map<String, dynamic> toJson() => _$WalletWithdrawalPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
