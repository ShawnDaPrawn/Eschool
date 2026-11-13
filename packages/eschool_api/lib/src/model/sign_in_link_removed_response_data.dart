//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'sign_in_link_removed_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SignInLinkRemovedResponseData {
  /// Returns a new [SignInLinkRemovedResponseData] instance.
  SignInLinkRemovedResponseData({required this.removed});

  @JsonKey(name: r'removed', required: true, includeIfNull: false)
  final bool removed;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SignInLinkRemovedResponseData && other.removed == removed;

  @override
  int get hashCode => removed.hashCode;

  factory SignInLinkRemovedResponseData.fromJson(Map<String, dynamic> json) =>
      _$SignInLinkRemovedResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$SignInLinkRemovedResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
