//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/sign_in_provider_option.dart';
import 'package:json_annotation/json_annotation.dart';

part 'sign_in_providers_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SignInProvidersResponseData {
  /// Returns a new [SignInProvidersResponseData] instance.
  SignInProvidersResponseData({required this.providers});

  @JsonKey(name: r'providers', required: true, includeIfNull: false)
  final List<SignInProviderOption> providers;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SignInProvidersResponseData && other.providers == providers;

  @override
  int get hashCode => providers.hashCode;

  factory SignInProvidersResponseData.fromJson(Map<String, dynamic> json) =>
      _$SignInProvidersResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$SignInProvidersResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
