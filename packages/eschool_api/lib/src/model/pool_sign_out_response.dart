//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pool_member_page_response_code.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pool_sign_out_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolSignOutResponse {
  /// Returns a new [PoolSignOutResponse] instance.
  PoolSignOutResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(
    fromJson: _onlyNullFromJson,
    toJson: _onlyNullToJson,

    name: r'data',
    required: true,
    includeIfNull: true,
  )
  final Null data;

  @JsonKey(name: r'code', required: true, includeIfNull: true)
  final PoolMemberPageResponseCode? code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolSignOutResponse &&
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

  factory PoolSignOutResponse.fromJson(Map<String, dynamic> json) =>
      _$PoolSignOutResponseFromJson(json);

  Map<String, dynamic> toJson() => _$PoolSignOutResponseToJson(this);

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
