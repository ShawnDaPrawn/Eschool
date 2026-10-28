//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/vendor_counter_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'vendor_counter_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class VendorCounterPage {
  /// Returns a new [VendorCounterPage] instance.
  VendorCounterPage({
    required this.schoolId,

    required this.actorUserId,

    required this.vendorId,

    required this.status,

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

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: VendorCounterPageStatusEnum.unknownDefaultOpenApi,
  )
  final VendorCounterPageStatusEnum status;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<VendorCounterReceipt> items;

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
      other is VendorCounterPage &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.vendorId == vendorId &&
          other.status == status &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      vendorId.hashCode +
      status.hashCode +
      items.hashCode +
      page.hashCode +
      lastPage.hashCode;

  factory VendorCounterPage.fromJson(Map<String, dynamic> json) =>
      _$VendorCounterPageFromJson(json);

  Map<String, dynamic> toJson() => _$VendorCounterPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum VendorCounterPageStatusEnum {
  @JsonValue(r'ready')
  ready(r'ready'),
  @JsonValue(r'collected')
  collected(r'collected'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const VendorCounterPageStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
