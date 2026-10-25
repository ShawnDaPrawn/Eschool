//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/facilitator_profile.dart';
import 'package:eschool_api/src/model/facilitator_policy.dart';
import 'package:json_annotation/json_annotation.dart';

part 'facilitator_sign_in_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FacilitatorSignInData {
  /// Returns a new [FacilitatorSignInData] instance.
  FacilitatorSignInData({
    required this.authenticated,

    required this.token,

    required this.profile,

    required this.passwordChangeRequired,

    required this.documents,
  });

  @JsonKey(name: r'authenticated', required: true, includeIfNull: false)
  final bool authenticated;

  @JsonKey(name: r'token', required: true, includeIfNull: true)
  final String? token;

  @JsonKey(name: r'profile', required: true, includeIfNull: true)
  final FacilitatorProfile? profile;

  @JsonKey(
    name: r'password_change_required',
    required: true,
    includeIfNull: false,
  )
  final bool passwordChangeRequired;

  @JsonKey(name: r'documents', required: true, includeIfNull: false)
  final List<FacilitatorPolicy> documents;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FacilitatorSignInData &&
          other.authenticated == authenticated &&
          other.token == token &&
          other.profile == profile &&
          other.passwordChangeRequired == passwordChangeRequired &&
          other.documents == documents;

  @override
  int get hashCode =>
      authenticated.hashCode +
      (token == null ? 0 : token.hashCode) +
      (profile == null ? 0 : profile.hashCode) +
      passwordChangeRequired.hashCode +
      documents.hashCode;

  factory FacilitatorSignInData.fromJson(Map<String, dynamic> json) =>
      _$FacilitatorSignInDataFromJson(json);

  Map<String, dynamic> toJson() => _$FacilitatorSignInDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
