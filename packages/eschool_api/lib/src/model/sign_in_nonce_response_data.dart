//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'sign_in_nonce_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SignInNonceResponseData {
  /// Returns a new [SignInNonceResponseData] instance.
  SignInNonceResponseData({
    required this.nonce,

    required this.expiresAt,

    required this.clientId,
  });

  /// Pass to the native sign-in; it works once.
  @JsonKey(name: r'nonce', required: true, includeIfNull: false)
  final String nonce;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  /// The server (web) client id to ask the native sign-in for. Public.
  @JsonKey(name: r'client_id', required: true, includeIfNull: false)
  final String clientId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SignInNonceResponseData &&
          other.nonce == nonce &&
          other.expiresAt == expiresAt &&
          other.clientId == clientId;

  @override
  int get hashCode => nonce.hashCode + expiresAt.hashCode + clientId.hashCode;

  factory SignInNonceResponseData.fromJson(Map<String, dynamic> json) =>
      _$SignInNonceResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$SignInNonceResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
