//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pool_member_page_response_code.dart';
import 'package:eschool_api/src/model/pool_withdrawal.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pool_withdrawal_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolWithdrawalResponse {
  /// Returns a new [PoolWithdrawalResponse] instance.
  PoolWithdrawalResponse({
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
  final PoolWithdrawal data;

  @JsonKey(name: r'code', required: true, includeIfNull: true)
  final PoolMemberPageResponseCode? code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolWithdrawalResponse &&
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

  factory PoolWithdrawalResponse.fromJson(Map<String, dynamic> json) =>
      _$PoolWithdrawalResponseFromJson(json);

  Map<String, dynamic> toJson() => _$PoolWithdrawalResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
