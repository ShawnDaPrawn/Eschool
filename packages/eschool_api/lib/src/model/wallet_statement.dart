//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wallet_controls.dart';
import 'package:eschool_api/src/model/wallet_entry_page.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_statement.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletStatement {
  /// Returns a new [WalletStatement] instance.
  WalletStatement({
    required this.id,

    required this.schoolId,

    required this.ownerUserId,

    required this.ownerName,

    required this.currency,

    required this.balanceCents,

    required this.isChild,

    required this.readOnly,

    required this.canManageLimits,

    required this.schoolDailySpendCents,

    required this.childLimitsRequired,

    required this.limits,

    required this.entries,
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
  @JsonKey(name: r'owner_user_id', required: true, includeIfNull: false)
  final int ownerUserId;

  @JsonKey(name: r'owner_name', required: true, includeIfNull: false)
  final String ownerName;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  // minimum: 0
  // maximum: 9000000000000000
  @JsonKey(name: r'balance_cents', required: true, includeIfNull: false)
  final int balanceCents;

  @JsonKey(name: r'is_child', required: true, includeIfNull: false)
  final bool isChild;

  @JsonKey(name: r'read_only', required: true, includeIfNull: false)
  final bool readOnly;

  @JsonKey(name: r'can_manage_limits', required: true, includeIfNull: false)
  final bool canManageLimits;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(
    name: r'school_daily_spend_cents',
    required: true,
    includeIfNull: false,
  )
  final int schoolDailySpendCents;

  @JsonKey(name: r'child_limits_required', required: true, includeIfNull: false)
  final bool childLimitsRequired;

  @JsonKey(name: r'limits', required: true, includeIfNull: false)
  final WalletControls limits;

  @JsonKey(name: r'entries', required: true, includeIfNull: false)
  final WalletEntryPage entries;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletStatement &&
          other.id == id &&
          other.schoolId == schoolId &&
          other.ownerUserId == ownerUserId &&
          other.ownerName == ownerName &&
          other.currency == currency &&
          other.balanceCents == balanceCents &&
          other.isChild == isChild &&
          other.readOnly == readOnly &&
          other.canManageLimits == canManageLimits &&
          other.schoolDailySpendCents == schoolDailySpendCents &&
          other.childLimitsRequired == childLimitsRequired &&
          other.limits == limits &&
          other.entries == entries;

  @override
  int get hashCode =>
      id.hashCode +
      schoolId.hashCode +
      ownerUserId.hashCode +
      ownerName.hashCode +
      currency.hashCode +
      balanceCents.hashCode +
      isChild.hashCode +
      readOnly.hashCode +
      canManageLimits.hashCode +
      schoolDailySpendCents.hashCode +
      childLimitsRequired.hashCode +
      limits.hashCode +
      entries.hashCode;

  factory WalletStatement.fromJson(Map<String, dynamic> json) =>
      _$WalletStatementFromJson(json);

  Map<String, dynamic> toJson() => _$WalletStatementToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
