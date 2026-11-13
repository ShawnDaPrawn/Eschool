//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/sign_in_provider_name.dart';
import 'package:json_annotation/json_annotation.dart';

part 'sign_in_provider_option.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SignInProviderOption {
  /// Returns a new [SignInProviderOption] instance.
  SignInProviderOption({required this.provider, required this.label});

  @JsonKey(
    name: r'provider',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SignInProviderName.unknownDefaultOpenApi,
  )
  final SignInProviderName provider;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SignInProviderOption &&
          other.provider == provider &&
          other.label == label;

  @override
  int get hashCode => provider.hashCode + label.hashCode;

  factory SignInProviderOption.fromJson(Map<String, dynamic> json) =>
      _$SignInProviderOptionFromJson(json);

  Map<String, dynamic> toJson() => _$SignInProviderOptionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
