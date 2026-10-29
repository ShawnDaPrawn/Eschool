//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wallet_payment_personal.dart';
import 'package:json_annotation/json_annotation.dart';

part 'wallet_payment_personal_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WalletPaymentPersonalPage {
  /// Returns a new [WalletPaymentPersonalPage] instance.
  WalletPaymentPersonalPage({
    required this.schoolId,

    required this.actorUserId,

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

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<WalletPaymentPersonal> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WalletPaymentPersonalPage &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      items.hashCode +
      page.hashCode +
      lastPage.hashCode;

  factory WalletPaymentPersonalPage.fromJson(Map<String, dynamic> json) =>
      _$WalletPaymentPersonalPageFromJson(json);

  Map<String, dynamic> toJson() => _$WalletPaymentPersonalPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
