//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wallet_transfer_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_transfer_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletTransferPage {
  /// Returns a new [WalletTransferPage] instance.
  WalletTransferPage({
    required this.schoolId,

    required this.actorUserId,

    required this.canTransfer,

    required this.childReturns,

    required this.policyRevision,

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

  @JsonKey(name: r'can_transfer', required: true, includeIfNull: false)
  final bool canTransfer;

  @JsonKey(
    name: r'child_returns',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WalletTransferPageChildReturnsEnum.unknownDefaultOpenApi,
  )
  final WalletTransferPageChildReturnsEnum childReturns;

  // minimum: 1
  @JsonKey(name: r'policy_revision', required: true, includeIfNull: true)
  final int? policyRevision;

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
  final List<WalletTransferReceipt> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletTransferPage &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.canTransfer == canTransfer &&
          other.childReturns == childReturns &&
          other.policyRevision == policyRevision &&
          other.currentPage == currentPage &&
          other.perPage == perPage &&
          other.total == total &&
          other.items == items;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      canTransfer.hashCode +
      childReturns.hashCode +
      (policyRevision == null ? 0 : policyRevision.hashCode) +
      currentPage.hashCode +
      perPage.hashCode +
      total.hashCode +
      items.hashCode;

  factory WalletTransferPage.fromJson(Map<String, dynamic> json) =>
      _$WalletTransferPageFromJson(json);

  Map<String, dynamic> toJson() => _$WalletTransferPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WalletTransferPageChildReturnsEnum {
  @JsonValue(r'unconfigured')
  unconfigured(r'unconfigured'),
  @JsonValue(r'disabled')
  disabled(r'disabled'),
  @JsonValue(r'current_approving_guardian')
  currentApprovingGuardian(r'current_approving_guardian'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WalletTransferPageChildReturnsEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
