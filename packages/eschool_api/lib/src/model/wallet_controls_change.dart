//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wallet_controls_change.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletControlsChange {
  /// Returns a new [WalletControlsChange] instance.
  WalletControlsChange({
    required this.operationId,

    required this.revision,

    required this.dailySpendCents,

    required this.allCategories,

    required this.categories,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 0
  // maximum: 100000000000
  @JsonKey(name: r'daily_spend_cents', required: true, includeIfNull: false)
  final int dailySpendCents;

  @JsonKey(name: r'all_categories', required: true, includeIfNull: false)
  final bool allCategories;

  @JsonKey(name: r'categories', required: true, includeIfNull: false)
  final Set<String> categories;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletControlsChange &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.dailySpendCents == dailySpendCents &&
          other.allCategories == allCategories &&
          other.categories == categories;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      dailySpendCents.hashCode +
      allCategories.hashCode +
      categories.hashCode;

  factory WalletControlsChange.fromJson(Map<String, dynamic> json) =>
      _$WalletControlsChangeFromJson(json);

  Map<String, dynamic> toJson() => _$WalletControlsChangeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
