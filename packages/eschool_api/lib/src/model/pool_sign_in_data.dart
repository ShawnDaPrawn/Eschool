//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pool_document.dart';
import 'package:eschool_api/src/model/pool_profile.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pool_sign_in_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolSignInData {
  /// Returns a new [PoolSignInData] instance.
  PoolSignInData({
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
  final PoolProfile? profile;

  @JsonKey(
    name: r'password_change_required',
    required: true,
    includeIfNull: false,
  )
  final bool passwordChangeRequired;

  @JsonKey(name: r'documents', required: true, includeIfNull: false)
  final List<PoolDocument> documents;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolSignInData &&
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

  factory PoolSignInData.fromJson(Map<String, dynamic> json) =>
      _$PoolSignInDataFromJson(json);

  Map<String, dynamic> toJson() => _$PoolSignInDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
