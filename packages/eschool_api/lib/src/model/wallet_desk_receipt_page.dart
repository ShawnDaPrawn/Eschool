//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wallet_desk_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_desk_receipt_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletDeskReceiptPage {
  /// Returns a new [WalletDeskReceiptPage] instance.
  WalletDeskReceiptPage({
    required this.schoolId,

    required this.actorUserId,

    required this.walletId,

    required this.ownerUserId,

    required this.currency,

    required this.readOnly,

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

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'wallet_id', required: true, includeIfNull: false)
  final int walletId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'owner_user_id', required: true, includeIfNull: false)
  final int ownerUserId;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  @JsonKey(name: r'read_only', required: true, includeIfNull: false)
  final bool readOnly;

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
  final List<WalletDeskReceipt> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletDeskReceiptPage &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.walletId == walletId &&
          other.ownerUserId == ownerUserId &&
          other.currency == currency &&
          other.readOnly == readOnly &&
          other.currentPage == currentPage &&
          other.perPage == perPage &&
          other.total == total &&
          other.items == items;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      walletId.hashCode +
      ownerUserId.hashCode +
      currency.hashCode +
      readOnly.hashCode +
      currentPage.hashCode +
      perPage.hashCode +
      total.hashCode +
      items.hashCode;

  factory WalletDeskReceiptPage.fromJson(Map<String, dynamic> json) =>
      _$WalletDeskReceiptPageFromJson(json);

  Map<String, dynamic> toJson() => _$WalletDeskReceiptPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
