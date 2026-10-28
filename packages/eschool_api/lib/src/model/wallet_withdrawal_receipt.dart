//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_withdrawal_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletWithdrawalReceipt {
  /// Returns a new [WalletWithdrawalReceipt] instance.
  WalletWithdrawalReceipt({
    required this.id,

    required this.reference,

    required this.schoolId,

    required this.holderUserId,

    required this.operationId,

    required this.walletId,

    required this.currency,

    required this.amountCents,

    required this.method,

    required this.status,

    required this.createdAt,

    required this.processedAt,

    required this.pickupStartsAt,

    required this.pickupEndsAt,

    required this.paidOn,

    required this.payoutReference,

    required this.pickupLabel,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'reference', required: true, includeIfNull: false)
  final String reference;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'holder_user_id', required: true, includeIfNull: false)
  final int holderUserId;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'wallet_id', required: true, includeIfNull: false)
  final int walletId;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  // minimum: 1
  // maximum: 100000000000
  @JsonKey(name: r'amount_cents', required: true, includeIfNull: false)
  final int amountCents;

  @JsonKey(
    name: r'method',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WalletWithdrawalReceiptMethodEnum.unknownDefaultOpenApi,
  )
  final WalletWithdrawalReceiptMethodEnum method;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WalletWithdrawalReceiptStatusEnum.unknownDefaultOpenApi,
  )
  final WalletWithdrawalReceiptStatusEnum status;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'processed_at', required: true, includeIfNull: true)
  final DateTime? processedAt;

  @JsonKey(name: r'pickup_starts_at', required: true, includeIfNull: true)
  final DateTime? pickupStartsAt;

  @JsonKey(name: r'pickup_ends_at', required: true, includeIfNull: true)
  final DateTime? pickupEndsAt;

  @JsonKey(name: r'paid_on', required: true, includeIfNull: true)
  final DateTime? paidOn;

  @JsonKey(name: r'payout_reference', required: true, includeIfNull: true)
  final String? payoutReference;

  @JsonKey(name: r'pickup_label', required: true, includeIfNull: true)
  final String? pickupLabel;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletWithdrawalReceipt &&
          other.id == id &&
          other.reference == reference &&
          other.schoolId == schoolId &&
          other.holderUserId == holderUserId &&
          other.operationId == operationId &&
          other.walletId == walletId &&
          other.currency == currency &&
          other.amountCents == amountCents &&
          other.method == method &&
          other.status == status &&
          other.createdAt == createdAt &&
          other.processedAt == processedAt &&
          other.pickupStartsAt == pickupStartsAt &&
          other.pickupEndsAt == pickupEndsAt &&
          other.paidOn == paidOn &&
          other.payoutReference == payoutReference &&
          other.pickupLabel == pickupLabel;

  @override
  int get hashCode =>
      id.hashCode +
      reference.hashCode +
      schoolId.hashCode +
      holderUserId.hashCode +
      operationId.hashCode +
      walletId.hashCode +
      currency.hashCode +
      amountCents.hashCode +
      method.hashCode +
      status.hashCode +
      createdAt.hashCode +
      (processedAt == null ? 0 : processedAt.hashCode) +
      (pickupStartsAt == null ? 0 : pickupStartsAt.hashCode) +
      (pickupEndsAt == null ? 0 : pickupEndsAt.hashCode) +
      (paidOn == null ? 0 : paidOn.hashCode) +
      (payoutReference == null ? 0 : payoutReference.hashCode) +
      (pickupLabel == null ? 0 : pickupLabel.hashCode);

  factory WalletWithdrawalReceipt.fromJson(Map<String, dynamic> json) =>
      _$WalletWithdrawalReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$WalletWithdrawalReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WalletWithdrawalReceiptMethodEnum {
  @JsonValue(r'cash')
  cash(r'cash'),
  @JsonValue(r'eft')
  eft(r'eft'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletWithdrawalReceiptMethodEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum WalletWithdrawalReceiptStatusEnum {
  @JsonValue(r'requested')
  requested(r'requested'),
  @JsonValue(r'approved')
  approved(r'approved'),
  @JsonValue(r'paid')
  paid(r'paid'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'rejected')
  rejected(r'rejected'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletWithdrawalReceiptStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
