//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/child_mode_start_response_data.dart';
import 'package:eschool_api/src/model/pool_member_page_response_code.dart';
import 'package:json_annotation/json_annotation.dart';

part 'child_mode_start_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ChildModeStartResponse {
  /// Returns a new [ChildModeStartResponse] instance.
  ChildModeStartResponse({
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
  final ChildModeStartResponseData data;

  @JsonKey(name: r'code', required: true, includeIfNull: true)
  final PoolMemberPageResponseCode? code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ChildModeStartResponse &&
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

  factory ChildModeStartResponse.fromJson(Map<String, dynamic> json) =>
      _$ChildModeStartResponseFromJson(json);

  Map<String, dynamic> toJson() => _$ChildModeStartResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
