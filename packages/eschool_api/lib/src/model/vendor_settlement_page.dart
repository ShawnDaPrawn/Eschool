//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/vendor_settlement_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'vendor_settlement_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class VendorSettlementPage {
  /// Returns a new [VendorSettlementPage] instance.
  VendorSettlementPage({
    required this.schoolId,

    required this.actorUserId,

    required this.vendorId,

    required this.items,

    required this.page,

    required this.lastPage,
  });

  // minimum: 1
  // maximum: 100000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  // maximum: 100000000000
  @JsonKey(name: r'actor_user_id', required: true, includeIfNull: false)
  final int actorUserId;

  // minimum: 1
  // maximum: 100000000000
  @JsonKey(name: r'vendor_id', required: true, includeIfNull: false)
  final int vendorId;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<VendorSettlementSummary> items;

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
      other is VendorSettlementPage &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.vendorId == vendorId &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      vendorId.hashCode +
      items.hashCode +
      page.hashCode +
      lastPage.hashCode;

  factory VendorSettlementPage.fromJson(Map<String, dynamic> json) =>
      _$VendorSettlementPageFromJson(json);

  Map<String, dynamic> toJson() => _$VendorSettlementPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
