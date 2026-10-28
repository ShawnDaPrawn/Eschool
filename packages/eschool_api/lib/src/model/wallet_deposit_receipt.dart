//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wallet_deposit_receipt_verified_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_deposit_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletDepositReceipt {
  /// Returns a new [WalletDepositReceipt] instance.
  WalletDepositReceipt({
    required this.id,

    required this.reference,

    required this.schoolId,

    required this.actorUserId,

    required this.operationId,

    required this.walletId,

    required this.currency,

    required this.requestedCents,

    required this.requestedMethod,

    required this.requestedReference,

    required this.paidOn,

    required this.status,

    required this.reasonCode,

    required this.createdAt,

    required this.processedAt,

    required this.verifiedReceipt,
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
  @JsonKey(name: r'actor_user_id', required: true, includeIfNull: false)
  final int actorUserId;

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
  @JsonKey(name: r'requested_cents', required: true, includeIfNull: false)
  final int requestedCents;

  @JsonKey(
    name: r'requested_method',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        WalletDepositReceiptRequestedMethodEnum.unknownDefaultOpenApi,
  )
  final WalletDepositReceiptRequestedMethodEnum requestedMethod;

  @JsonKey(name: r'requested_reference', required: true, includeIfNull: false)
  final String requestedReference;

  @JsonKey(name: r'paid_on', required: true, includeIfNull: false)
  final DateTime paidOn;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WalletDepositReceiptStatusEnum.unknownDefaultOpenApi,
  )
  final WalletDepositReceiptStatusEnum status;

  @JsonKey(
    name: r'reason_code',
    required: true,
    includeIfNull: true,
    unknownEnumValue: WalletDepositReceiptReasonCodeEnum.unknownDefaultOpenApi,
  )
  final WalletDepositReceiptReasonCodeEnum? reasonCode;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'processed_at', required: true, includeIfNull: true)
  final DateTime? processedAt;

  @JsonKey(name: r'verified_receipt', required: true, includeIfNull: true)
  final WalletDepositReceiptVerifiedReceipt? verifiedReceipt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletDepositReceipt &&
          other.id == id &&
          other.reference == reference &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.operationId == operationId &&
          other.walletId == walletId &&
          other.currency == currency &&
          other.requestedCents == requestedCents &&
          other.requestedMethod == requestedMethod &&
          other.requestedReference == requestedReference &&
          other.paidOn == paidOn &&
          other.status == status &&
          other.reasonCode == reasonCode &&
          other.createdAt == createdAt &&
          other.processedAt == processedAt &&
          other.verifiedReceipt == verifiedReceipt;

  @override
  int get hashCode =>
      id.hashCode +
      reference.hashCode +
      schoolId.hashCode +
      actorUserId.hashCode +
      operationId.hashCode +
      walletId.hashCode +
      currency.hashCode +
      requestedCents.hashCode +
      requestedMethod.hashCode +
      requestedReference.hashCode +
      paidOn.hashCode +
      status.hashCode +
      (reasonCode == null ? 0 : reasonCode.hashCode) +
      createdAt.hashCode +
      (processedAt == null ? 0 : processedAt.hashCode) +
      (verifiedReceipt == null ? 0 : verifiedReceipt.hashCode);

  factory WalletDepositReceipt.fromJson(Map<String, dynamic> json) =>
      _$WalletDepositReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$WalletDepositReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WalletDepositReceiptRequestedMethodEnum {
  @JsonValue(r'cash')
  cash(r'cash'),
  @JsonValue(r'eft')
  eft(r'eft'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletDepositReceiptRequestedMethodEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum WalletDepositReceiptStatusEnum {
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'verified')
  verified(r'verified'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'rejected')
  rejected(r'rejected'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletDepositReceiptStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum WalletDepositReceiptReasonCodeEnum {
  @JsonValue(r'verified')
  verified(r'verified'),
  @JsonValue(r'withdrawn')
  withdrawn(r'withdrawn'),
  @JsonValue(r'not_verified')
  notVerified(r'not_verified'),
  @JsonValue(r'duplicate')
  duplicate(r'duplicate'),
  @JsonValue(r'request_incorrect')
  requestIncorrect(r'request_incorrect'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletDepositReceiptReasonCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
