//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wallet_counter_product.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_counter_catalogue.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletCounterCatalogue {
  /// Returns a new [WalletCounterCatalogue] instance.
  WalletCounterCatalogue({
    required this.schoolId,

    required this.actorUserId,

    required this.vendorId,

    required this.currency,

    required this.items,

    required this.page,

    required this.lastPage,
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
  @JsonKey(name: r'vendor_id', required: true, includeIfNull: false)
  final int vendorId;

  @JsonKey(name: r'currency', required: true, includeIfNull: false)
  final String currency;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<WalletCounterProduct> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletCounterCatalogue &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.vendorId == vendorId &&
          other.currency == currency &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      vendorId.hashCode +
      currency.hashCode +
      items.hashCode +
      page.hashCode +
      lastPage.hashCode;

  factory WalletCounterCatalogue.fromJson(Map<String, dynamic> json) =>
      _$WalletCounterCatalogueFromJson(json);

  Map<String, dynamic> toJson() => _$WalletCounterCatalogueToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
