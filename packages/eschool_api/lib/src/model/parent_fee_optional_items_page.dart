//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/parent_fee_optional_item.dart';
import 'package:json_annotation/json_annotation.dart';

part 'parent_fee_optional_items_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ParentFeeOptionalItemsPage {
  /// Returns a new [ParentFeeOptionalItemsPage] instance.
  ParentFeeOptionalItemsPage({
    required this.schoolId,

    required this.holderUserId,

    required this.feeAccountId,

    required this.sessionYearId,

    required this.writable,

    required this.items,

    required this.currentPage,

    required this.perPage,

    required this.total,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'holder_user_id', required: true, includeIfNull: false)
  final int holderUserId;

  // minimum: 1
  @JsonKey(name: r'fee_account_id', required: true, includeIfNull: false)
  final int feeAccountId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<ParentFeeOptionalItem> items;

  // minimum: 1
  @JsonKey(name: r'current_page', required: true, includeIfNull: false)
  final int currentPage;

  // minimum: 1
  // maximum: 50
  @JsonKey(name: r'per_page', required: true, includeIfNull: false)
  final int perPage;

  // minimum: 0
  // maximum: 20000
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ParentFeeOptionalItemsPage &&
          other.schoolId == schoolId &&
          other.holderUserId == holderUserId &&
          other.feeAccountId == feeAccountId &&
          other.sessionYearId == sessionYearId &&
          other.writable == writable &&
          other.items == items &&
          other.currentPage == currentPage &&
          other.perPage == perPage &&
          other.total == total;

  @override
  int get hashCode =>
      schoolId.hashCode +
      holderUserId.hashCode +
      feeAccountId.hashCode +
      sessionYearId.hashCode +
      writable.hashCode +
      items.hashCode +
      currentPage.hashCode +
      perPage.hashCode +
      total.hashCode;

  factory ParentFeeOptionalItemsPage.fromJson(Map<String, dynamic> json) =>
      _$ParentFeeOptionalItemsPageFromJson(json);

  Map<String, dynamic> toJson() => _$ParentFeeOptionalItemsPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
