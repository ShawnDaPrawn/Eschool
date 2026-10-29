//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wallet_counter_payment_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_counter_payment_resolution.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletCounterPaymentResolution {
  /// Returns a new [WalletCounterPaymentResolution] instance.
  WalletCounterPaymentResolution({
    required this.schoolId,

    required this.actorUserId,

    required this.vendorId,

    required this.operationId,

    required this.status,

    required this.payment,
  });

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

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        WalletCounterPaymentResolutionStatusEnum.unknownDefaultOpenApi,
  )
  final WalletCounterPaymentResolutionStatusEnum status;

  @JsonKey(name: r'payment', required: true, includeIfNull: true)
  final WalletCounterPaymentReceipt? payment;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletCounterPaymentResolution &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.vendorId == vendorId &&
          other.operationId == operationId &&
          other.status == status &&
          other.payment == payment;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      vendorId.hashCode +
      operationId.hashCode +
      status.hashCode +
      (payment == null ? 0 : payment.hashCode);

  factory WalletCounterPaymentResolution.fromJson(Map<String, dynamic> json) =>
      _$WalletCounterPaymentResolutionFromJson(json);

  Map<String, dynamic> toJson() => _$WalletCounterPaymentResolutionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WalletCounterPaymentResolutionStatusEnum {
  @JsonValue(r'committed')
  committed(r'committed'),
  @JsonValue(r'abandoned')
  abandoned(r'abandoned'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletCounterPaymentResolutionStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
