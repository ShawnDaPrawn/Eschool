//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/parent_fee_account_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'parent_fee_accounts_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ParentFeeAccountsPage {
  /// Returns a new [ParentFeeAccountsPage] instance.
  ParentFeeAccountsPage({
    required this.items,

    required this.currentPage,

    required this.perPage,

    required this.total,

    required this.schoolId,

    required this.holderUserId,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<ParentFeeAccountSummary> items;

  // minimum: 1
  @JsonKey(name: r'current_page', required: true, includeIfNull: false)
  final int currentPage;

  // minimum: 1
  // maximum: 50
  @JsonKey(name: r'per_page', required: true, includeIfNull: false)
  final int perPage;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'holder_user_id', required: true, includeIfNull: false)
  final int holderUserId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ParentFeeAccountsPage &&
          other.items == items &&
          other.currentPage == currentPage &&
          other.perPage == perPage &&
          other.total == total &&
          other.schoolId == schoolId &&
          other.holderUserId == holderUserId;

  @override
  int get hashCode =>
      items.hashCode +
      currentPage.hashCode +
      perPage.hashCode +
      total.hashCode +
      schoolId.hashCode +
      holderUserId.hashCode;

  factory ParentFeeAccountsPage.fromJson(Map<String, dynamic> json) =>
      _$ParentFeeAccountsPageFromJson(json);

  Map<String, dynamic> toJson() => _$ParentFeeAccountsPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
