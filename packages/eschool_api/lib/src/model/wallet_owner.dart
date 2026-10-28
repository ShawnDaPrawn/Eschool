//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wallet_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_owner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletOwner {
  /// Returns a new [WalletOwner] instance.
  WalletOwner({
    required this.ownerUserId,

    required this.ownerName,

    required this.isSelf,

    required this.canOpen,

    required this.wallets,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'owner_user_id', required: true, includeIfNull: false)
  final int ownerUserId;

  @JsonKey(name: r'owner_name', required: true, includeIfNull: false)
  final String ownerName;

  @JsonKey(name: r'is_self', required: true, includeIfNull: false)
  final bool isSelf;

  @JsonKey(name: r'can_open', required: true, includeIfNull: false)
  final bool canOpen;

  @JsonKey(name: r'wallets', required: true, includeIfNull: false)
  final List<WalletSummary> wallets;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletOwner &&
          other.ownerUserId == ownerUserId &&
          other.ownerName == ownerName &&
          other.isSelf == isSelf &&
          other.canOpen == canOpen &&
          other.wallets == wallets;

  @override
  int get hashCode =>
      ownerUserId.hashCode +
      ownerName.hashCode +
      isSelf.hashCode +
      canOpen.hashCode +
      wallets.hashCode;

  factory WalletOwner.fromJson(Map<String, dynamic> json) =>
      _$WalletOwnerFromJson(json);

  Map<String, dynamic> toJson() => _$WalletOwnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
