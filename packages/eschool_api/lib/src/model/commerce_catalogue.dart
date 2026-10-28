//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/commerce_catalogue_vendor.dart';
import 'package:eschool_api/src/model/commerce_product.dart';
import 'package:json_annotation/json_annotation.dart';

part 'commerce_catalogue.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommerceCatalogue {
  /// Returns a new [CommerceCatalogue] instance.
  CommerceCatalogue({
    required this.schoolId,

    required this.actorUserId,

    required this.canPurchase,

    required this.vendor,

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

  @JsonKey(name: r'can_purchase', required: true, includeIfNull: false)
  final bool canPurchase;

  @JsonKey(name: r'vendor', required: true, includeIfNull: false)
  final CommerceCatalogueVendor vendor;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<CommerceProduct> items;

  // minimum: 1
  // maximum: 1000000
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  // maximum: 1000000
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommerceCatalogue &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.canPurchase == canPurchase &&
          other.vendor == vendor &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      canPurchase.hashCode +
      vendor.hashCode +
      items.hashCode +
      page.hashCode +
      lastPage.hashCode;

  factory CommerceCatalogue.fromJson(Map<String, dynamic> json) =>
      _$CommerceCatalogueFromJson(json);

  Map<String, dynamic> toJson() => _$CommerceCatalogueToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
