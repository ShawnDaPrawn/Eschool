//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/commerce_order_receipt.dart';
import 'package:json_annotation/json_annotation.dart';

part 'commerce_order_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommerceOrderPage {
  /// Returns a new [CommerceOrderPage] instance.
  CommerceOrderPage({
    required this.schoolId,

    required this.actorUserId,

    required this.readOnly,

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

  @JsonKey(name: r'read_only', required: true, includeIfNull: false)
  final bool readOnly;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<CommerceOrderReceipt> items;

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
      other is CommerceOrderPage &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.readOnly == readOnly &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      readOnly.hashCode +
      items.hashCode +
      page.hashCode +
      lastPage.hashCode;

  factory CommerceOrderPage.fromJson(Map<String, dynamic> json) =>
      _$CommerceOrderPageFromJson(json);

  Map<String, dynamic> toJson() => _$CommerceOrderPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
