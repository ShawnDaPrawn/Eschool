//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/sign_in_link.dart';
import 'package:eschool_api/src/model/sign_in_links_response_data_providers_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'sign_in_links_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SignInLinksResponseData {
  /// Returns a new [SignInLinksResponseData] instance.
  SignInLinksResponseData({required this.links, required this.providers});

  @JsonKey(name: r'links', required: true, includeIfNull: false)
  final List<SignInLink> links;

  @JsonKey(name: r'providers', required: true, includeIfNull: false)
  final List<SignInLinksResponseDataProvidersInner> providers;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SignInLinksResponseData &&
          other.links == links &&
          other.providers == providers;

  @override
  int get hashCode => links.hashCode + providers.hashCode;

  factory SignInLinksResponseData.fromJson(Map<String, dynamic> json) =>
      _$SignInLinksResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$SignInLinksResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
