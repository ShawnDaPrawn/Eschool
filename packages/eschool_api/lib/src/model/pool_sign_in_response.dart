//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pool_sign_in_data.dart';
import 'package:eschool_api/src/model/pool_member_page_response_code.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pool_sign_in_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolSignInResponse {
  /// Returns a new [PoolSignInResponse] instance.
  PoolSignInResponse({
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
  final PoolSignInData data;

  @JsonKey(name: r'code', required: true, includeIfNull: true)
  final PoolMemberPageResponseCode? code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolSignInResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode +
      message.hashCode +
      data.hashCode +
      (code == null ? 0 : code.hashCode);

  factory PoolSignInResponse.fromJson(Map<String, dynamic> json) =>
      _$PoolSignInResponseFromJson(json);

  Map<String, dynamic> toJson() => _$PoolSignInResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
