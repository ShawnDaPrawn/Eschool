//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pool_member_page_response_code.dart';
import 'package:json_annotation/json_annotation.dart';

part 'child_mode_left_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ChildModeLeftResponse {
  /// Returns a new [ChildModeLeftResponse] instance.
  ChildModeLeftResponse({
    required this.error,

    required this.message,

    required this.data,

    required this.code,
  });

  @JsonKey(name: r'error', required: true, includeIfNull: false)
  final bool error;

  @JsonKey(name: r'message', required: true, includeIfNull: false)
  final String message;

  @JsonKey(name: r'data', required: true, includeIfNull: true)
  final Object? data;

  @JsonKey(name: r'code', required: true, includeIfNull: true)
  final PoolMemberPageResponseCode? code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ChildModeLeftResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode +
      message.hashCode +
      (data == null ? 0 : data.hashCode) +
      (code == null ? 0 : code.hashCode);

  factory ChildModeLeftResponse.fromJson(Map<String, dynamic> json) =>
      _$ChildModeLeftResponseFromJson(json);

  Map<String, dynamic> toJson() => _$ChildModeLeftResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
