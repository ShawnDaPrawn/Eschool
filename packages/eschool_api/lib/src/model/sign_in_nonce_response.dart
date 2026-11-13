//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/sign_in_nonce_response_data.dart';
import 'package:json_annotation/json_annotation.dart';

part 'sign_in_nonce_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SignInNonceResponse {
  /// Returns a new [SignInNonceResponse] instance.
  SignInNonceResponse({
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
  final SignInNonceResponseData data;

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
      other is SignInNonceResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory SignInNonceResponse.fromJson(Map<String, dynamic> json) =>
      _$SignInNonceResponseFromJson(json);

  Map<String, dynamic> toJson() => _$SignInNonceResponseToJson(this);

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
