//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wallet_statement_entry.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_entry_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletEntryPage {
  /// Returns a new [WalletEntryPage] instance.
  WalletEntryPage({
    required this.items,

    required this.currentPage,

    required this.perPage,

    required this.total,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<WalletStatementEntry> items;

  // minimum: 1
  // maximum: 1000000
  @JsonKey(name: r'current_page', required: true, includeIfNull: false)
  final int currentPage;

  // minimum: 1
  // maximum: 50
  @JsonKey(name: r'per_page', required: true, includeIfNull: false)
  final int perPage;

  // minimum: 0
  // maximum: 9000000000000000
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletEntryPage &&
          other.items == items &&
          other.currentPage == currentPage &&
          other.perPage == perPage &&
          other.total == total;

  @override
  int get hashCode =>
      items.hashCode + currentPage.hashCode + perPage.hashCode + total.hashCode;

  factory WalletEntryPage.fromJson(Map<String, dynamic> json) =>
      _$WalletEntryPageFromJson(json);

  Map<String, dynamic> toJson() => _$WalletEntryPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
