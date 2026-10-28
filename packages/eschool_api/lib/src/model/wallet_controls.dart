//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_controls.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletControls {
  /// Returns a new [WalletControls] instance.
  WalletControls({
    required this.revision,

    required this.dailySpendCents,

    required this.effectiveDailySpendCents,

    required this.allCategories,

    required this.categories,

    required this.configured,
  });

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'daily_spend_cents', required: true, includeIfNull: true)
  final int? dailySpendCents;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(
    name: r'effective_daily_spend_cents',
    required: true,
    includeIfNull: false,
  )
  final int effectiveDailySpendCents;

  @JsonKey(name: r'all_categories', required: true, includeIfNull: false)
  final bool allCategories;

  @JsonKey(name: r'categories', required: true, includeIfNull: false)
  final List<String> categories;

  @JsonKey(name: r'configured', required: true, includeIfNull: false)
  final bool configured;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletControls &&
          other.revision == revision &&
          other.dailySpendCents == dailySpendCents &&
          other.effectiveDailySpendCents == effectiveDailySpendCents &&
          other.allCategories == allCategories &&
          other.categories == categories &&
          other.configured == configured;

  @override
  int get hashCode =>
      revision.hashCode +
      (dailySpendCents == null ? 0 : dailySpendCents.hashCode) +
      effectiveDailySpendCents.hashCode +
      allCategories.hashCode +
      categories.hashCode +
      configured.hashCode;

  factory WalletControls.fromJson(Map<String, dynamic> json) =>
      _$WalletControlsFromJson(json);

  Map<String, dynamic> toJson() => _$WalletControlsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
