//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'sign_in_nonce_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SignInNonceInput {
  /// Returns a new [SignInNonceInput] instance.
  SignInNonceInput({required this.school, required this.app});

  @JsonKey(name: r'school', required: true, includeIfNull: false)
  final String school;

  @JsonKey(
    name: r'app',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SignInNonceInputAppEnum.unknownDefaultOpenApi,
  )
  final SignInNonceInputAppEnum app;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SignInNonceInput && other.school == school && other.app == app;

  @override
  int get hashCode => school.hashCode + app.hashCode;

  factory SignInNonceInput.fromJson(Map<String, dynamic> json) =>
      _$SignInNonceInputFromJson(json);

  Map<String, dynamic> toJson() => _$SignInNonceInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SignInNonceInputAppEnum {
  @JsonValue(r'teacher')
  teacher(r'teacher'),
  @JsonValue(r'student')
  student(r'student'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SignInNonceInputAppEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
