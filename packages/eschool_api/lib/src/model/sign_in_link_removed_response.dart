//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/sign_in_link_removed_response_data.dart';
import 'package:json_annotation/json_annotation.dart';

part 'sign_in_link_removed_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SignInLinkRemovedResponse {
  /// Returns a new [SignInLinkRemovedResponse] instance.
  SignInLinkRemovedResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: false)
  final SignInLinkRemovedResponseData data;

  @JsonKey(
    fromJson: _onlyNullFromJson,
    toJson: _onlyNullToJson,

    name: r'code',
    required: true,
    includeIfNull: true,
  )
  final Null code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SignInLinkRemovedResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory SignInLinkRemovedResponse.fromJson(Map<String, dynamic> json) =>
      _$SignInLinkRemovedResponseFromJson(json);

  Map<String, dynamic> toJson() => _$SignInLinkRemovedResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

Null _onlyNullFromJson(Object? value) {
  if (value != null) {
    throw FormatException('Expected a null-only response payload.');
  }
  return null;
}

Null _onlyNullToJson(Null value) => value;
