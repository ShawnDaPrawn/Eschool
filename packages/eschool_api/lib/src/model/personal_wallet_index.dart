//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/wallet_owner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'personal_wallet_index.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PersonalWalletIndex {
  /// Returns a new [PersonalWalletIndex] instance.
  PersonalWalletIndex({
    required this.schoolId,

    required this.actorUserId,

    required this.currency,

    required this.owners,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'actor_user_id', required: true, includeIfNull: false)
  final int actorUserId;

  @JsonKey(name: r'currency', required: true, includeIfNull: true)
  final String? currency;

  @JsonKey(name: r'owners', required: true, includeIfNull: false)
  final List<WalletOwner> owners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PersonalWalletIndex &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.currency == currency &&
          other.owners == owners;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      (currency == null ? 0 : currency.hashCode) +
      owners.hashCode;

  factory PersonalWalletIndex.fromJson(Map<String, dynamic> json) =>
      _$PersonalWalletIndexFromJson(json);

  Map<String, dynamic> toJson() => _$PersonalWalletIndexToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
