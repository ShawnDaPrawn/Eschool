//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/vendor_counter_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_counter_payment_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletCounterPaymentReceipt {
  /// Returns a new [WalletCounterPaymentReceipt] instance.
  WalletCounterPaymentReceipt({
    this.replayed,

    required this.schoolId,

    required this.actorUserId,

    required this.vendorId,

    required this.id,

    required this.intentId,

    required this.operationId,

    required this.status,

    required this.paidAt,

    required this.order,
  });

  /// True on a repeated confirmation. Inspect the original receipt and do not hand over goods again.
  @JsonKey(name: r'replayed', required: false, includeIfNull: false)
  final bool? replayed;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'actor_user_id', required: true, includeIfNull: false)
  final int actorUserId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'vendor_id', required: true, includeIfNull: false)
  final int vendorId;

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'intent_id', required: true, includeIfNull: false)
  final String intentId;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        WalletCounterPaymentReceiptStatusEnum.unknownDefaultOpenApi,
  )
  final WalletCounterPaymentReceiptStatusEnum status;

  @JsonKey(name: r'paid_at', required: true, includeIfNull: false)
  final DateTime paidAt;

  @JsonKey(name: r'order', required: true, includeIfNull: false)
  final VendorCounterReceipt order;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletCounterPaymentReceipt &&
          other.replayed == replayed &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.vendorId == vendorId &&
          other.id == id &&
          other.intentId == intentId &&
          other.operationId == operationId &&
          other.status == status &&
          other.paidAt == paidAt &&
          other.order == order;

  @override
  int get hashCode =>
      replayed.hashCode +
      schoolId.hashCode +
      actorUserId.hashCode +
      vendorId.hashCode +
      id.hashCode +
      intentId.hashCode +
      operationId.hashCode +
      status.hashCode +
      paidAt.hashCode +
      order.hashCode;

  factory WalletCounterPaymentReceipt.fromJson(Map<String, dynamic> json) =>
      _$WalletCounterPaymentReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$WalletCounterPaymentReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WalletCounterPaymentReceiptStatusEnum {
  @JsonValue(r'paid')
  paid(r'paid'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletCounterPaymentReceiptStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
