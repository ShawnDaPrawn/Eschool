//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'facilitator_sign_in_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FacilitatorSignInInput {
  /// Returns a new [FacilitatorSignInInput] instance.
  FacilitatorSignInInput({
    required this.school,

    required this.email,

    required this.password,

    this.newPassword,

    this.newPasswordConfirmation,

    this.acceptances,
  });

  @JsonKey(name: r'school', required: true, includeIfNull: false)
  final String school;

  @JsonKey(name: r'email', required: true, includeIfNull: false)
  final String email;

  @JsonKey(name: r'password', required: true, includeIfNull: false)
  final String password;

  @JsonKey(name: r'new_password', required: false, includeIfNull: false)
  final String? newPassword;

  @JsonKey(
    name: r'new_password_confirmation',
    required: false,
    includeIfNull: false,
  )
  final String? newPasswordConfirmation;

  @JsonKey(name: r'acceptances', required: false, includeIfNull: false)
  final Map<String, String>? acceptances;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FacilitatorSignInInput &&
          other.school == school &&
          other.email == email &&
          other.password == password &&
          other.newPassword == newPassword &&
          other.newPasswordConfirmation == newPasswordConfirmation &&
          other.acceptances == acceptances;

  @override
  int get hashCode =>
      school.hashCode +
      email.hashCode +
      password.hashCode +
      newPassword.hashCode +
      newPasswordConfirmation.hashCode +
      acceptances.hashCode;

  factory FacilitatorSignInInput.fromJson(Map<String, dynamic> json) =>
      _$FacilitatorSignInInputFromJson(json);

  Map<String, dynamic> toJson() => _$FacilitatorSignInInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
