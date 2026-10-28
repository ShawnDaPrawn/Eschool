//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_transfer_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletTransferReceipt {
  /// Returns a new [WalletTransferReceipt] instance.
  WalletTransferReceipt({
    required this.id,

    required this.schoolId,

    required this.actorUserId,

    required this.fromWalletId,

    required this.toWalletId,

    required this.fromOwnerId,

    required this.toOwnerId,

    required this.reference,

    required this.operationId,

    required this.kind,

    required this.currency,

    required this.amountCents,

    required this.createdAt,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

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
  @JsonKey(name: r'from_wallet_id', required: true, includeIfNull: false)
  final int fromWalletId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'to_wallet_id', required: true, includeIfNull: false)
  final int toWalletId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'from_owner_id', required: true, includeIfNull: false)
  final int fromOwnerId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'to_owner_id', required: true, includeIfNull: false)
  final int toOwnerId;

  @JsonKey(name: r'reference', required: true, includeIfNull: false)
  final String reference;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WalletTransferReceiptKindEnum.unknownDefaultOpenApi,
  )
  final WalletTransferReceiptKindEnum kind;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  // minimum: 1
  // maximum: 100000000000
  @JsonKey(name: r'amount_cents', required: true, includeIfNull: false)
  final int amountCents;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletTransferReceipt &&
          other.id == id &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.fromWalletId == fromWalletId &&
          other.toWalletId == toWalletId &&
          other.fromOwnerId == fromOwnerId &&
          other.toOwnerId == toOwnerId &&
          other.reference == reference &&
          other.operationId == operationId &&
          other.kind == kind &&
          other.currency == currency &&
          other.amountCents == amountCents &&
          other.createdAt == createdAt;

  @override
  int get hashCode =>
      id.hashCode +
      schoolId.hashCode +
      actorUserId.hashCode +
      fromWalletId.hashCode +
      toWalletId.hashCode +
      fromOwnerId.hashCode +
      toOwnerId.hashCode +
      reference.hashCode +
      operationId.hashCode +
      kind.hashCode +
      currency.hashCode +
      amountCents.hashCode +
      createdAt.hashCode;

  factory WalletTransferReceipt.fromJson(Map<String, dynamic> json) =>
      _$WalletTransferReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$WalletTransferReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WalletTransferReceiptKindEnum {
  @JsonValue(r'to_child')
  toChild(r'to_child'),
  @JsonValue(r'to_parent')
  toParent(r'to_parent'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletTransferReceiptKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
