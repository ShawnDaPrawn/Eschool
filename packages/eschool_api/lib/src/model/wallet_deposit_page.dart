//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wallet_deposit_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_deposit_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletDepositPage {
  /// Returns a new [WalletDepositPage] instance.
  WalletDepositPage({
    required this.schoolId,

    required this.actorUserId,

    required this.currency,

    required this.isParent,

    required this.readOnly,

    required this.canSubmit,

    required this.cashAllowed,

    required this.eftAllowed,

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

  @JsonKey(name: r'is_parent', required: true, includeIfNull: false)
  final bool isParent;

  @JsonKey(name: r'read_only', required: true, includeIfNull: false)
  final bool readOnly;

  @JsonKey(name: r'can_submit', required: true, includeIfNull: false)
  final bool canSubmit;

  @JsonKey(name: r'cash_allowed', required: true, includeIfNull: false)
  final bool cashAllowed;

  @JsonKey(name: r'eft_allowed', required: true, includeIfNull: false)
  final bool eftAllowed;

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
  final List<WalletDepositReceipt> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletDepositPage &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.currency == currency &&
          other.isParent == isParent &&
          other.readOnly == readOnly &&
          other.canSubmit == canSubmit &&
          other.cashAllowed == cashAllowed &&
          other.eftAllowed == eftAllowed &&
          other.currentPage == currentPage &&
          other.perPage == perPage &&
          other.total == total &&
          other.items == items;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      (currency == null ? 0 : currency.hashCode) +
      isParent.hashCode +
      readOnly.hashCode +
      canSubmit.hashCode +
      cashAllowed.hashCode +
      eftAllowed.hashCode +
      currentPage.hashCode +
      perPage.hashCode +
      total.hashCode +
      items.hashCode;

  factory WalletDepositPage.fromJson(Map<String, dynamic> json) =>
      _$WalletDepositPageFromJson(json);

  Map<String, dynamic> toJson() => _$WalletDepositPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
