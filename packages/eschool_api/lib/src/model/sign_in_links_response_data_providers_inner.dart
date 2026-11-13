//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/sign_in_provider_name.dart';
import 'package:json_annotation/json_annotation.dart';

part 'sign_in_links_response_data_providers_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SignInLinksResponseDataProvidersInner {
  /// Returns a new [SignInLinksResponseDataProvidersInner] instance.
  SignInLinksResponseDataProvidersInner({
    required this.provider,

    required this.label,

    required this.mayLink,
  });

  @JsonKey(
    name: r'provider',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SignInProviderName.unknownDefaultOpenApi,
  )
  final SignInProviderName provider;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @JsonKey(name: r'may_link', required: true, includeIfNull: false)
  final bool mayLink;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SignInLinksResponseDataProvidersInner &&
          other.provider == provider &&
          other.label == label &&
          other.mayLink == mayLink;

  @override
  int get hashCode => provider.hashCode + label.hashCode + mayLink.hashCode;

  factory SignInLinksResponseDataProvidersInner.fromJson(
    Map<String, dynamic> json,
  ) => _$SignInLinksResponseDataProvidersInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SignInLinksResponseDataProvidersInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
