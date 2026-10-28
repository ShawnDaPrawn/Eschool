//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/commerce_vendor_brief.dart';
import 'package:json_annotation/json_annotation.dart';

part 'commerce_vendor_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommerceVendorPage {
  /// Returns a new [CommerceVendorPage] instance.
  CommerceVendorPage({
    required this.schoolId,

    required this.actorUserId,

    required this.canPurchase,

    required this.items,

    required this.page,

    required this.lastPage,

    this.ticketsAvailable,
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

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<CommerceVendorBrief> items;

  // minimum: 1
  // maximum: 1000000
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  // maximum: 1000000
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  /// Whether the ticketing module currently permits retained private ticket views.
  @JsonKey(name: r'tickets_available', required: false, includeIfNull: false)
  final bool? ticketsAvailable;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommerceVendorPage &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.canPurchase == canPurchase &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.ticketsAvailable == ticketsAvailable;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      canPurchase.hashCode +
      items.hashCode +
      page.hashCode +
      lastPage.hashCode +
      ticketsAvailable.hashCode;

  factory CommerceVendorPage.fromJson(Map<String, dynamic> json) =>
      _$CommerceVendorPageFromJson(json);

  Map<String, dynamic> toJson() => _$CommerceVendorPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
