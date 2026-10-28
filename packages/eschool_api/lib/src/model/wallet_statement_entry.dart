//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_statement_entry.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletStatementEntry {
  /// Returns a new [WalletStatementEntry] instance.
  WalletStatementEntry({
    required this.entryId,

    required this.postedAt,

    required this.changeCents,

    required this.balanceAfterCents,

    required this.description,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'entry_id', required: true, includeIfNull: false)
  final int entryId;

  @JsonKey(name: r'posted_at', required: true, includeIfNull: false)
  final DateTime postedAt;

  // minimum: -9000000000000000
  // maximum: 9000000000000000
  @JsonKey(name: r'change_cents', required: true, includeIfNull: false)
  final int changeCents;

  // minimum: 0
  // maximum: 9000000000000000
  @JsonKey(name: r'balance_after_cents', required: true, includeIfNull: false)
  final int balanceAfterCents;

  @JsonKey(name: r'description', required: true, includeIfNull: false)
  final String description;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletStatementEntry &&
          other.entryId == entryId &&
          other.postedAt == postedAt &&
          other.changeCents == changeCents &&
          other.balanceAfterCents == balanceAfterCents &&
          other.description == description;

  @override
  int get hashCode =>
      entryId.hashCode +
      postedAt.hashCode +
      changeCents.hashCode +
      balanceAfterCents.hashCode +
      description.hashCode;

  factory WalletStatementEntry.fromJson(Map<String, dynamic> json) =>
      _$WalletStatementEntryFromJson(json);

  Map<String, dynamic> toJson() => _$WalletStatementEntryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
