//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'sign_in_link_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SignInLinkInput {
  /// Returns a new [SignInLinkInput] instance.
  SignInLinkInput({
    required this.idToken,

    required this.nonce,

    required this.password,
  });

  @JsonKey(name: r'id_token', required: true, includeIfNull: false)
  final String idToken;

  @JsonKey(name: r'nonce', required: true, includeIfNull: false)
  final String nonce;

  @JsonKey(name: r'password', required: true, includeIfNull: false)
  final String password;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SignInLinkInput &&
          other.idToken == idToken &&
          other.nonce == nonce &&
          other.password == password;

  @override
  int get hashCode => idToken.hashCode + nonce.hashCode + password.hashCode;

  factory SignInLinkInput.fromJson(Map<String, dynamic> json) =>
      _$SignInLinkInputFromJson(json);

  Map<String, dynamic> toJson() => _$SignInLinkInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
