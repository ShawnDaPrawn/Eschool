//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/child_mode_session.dart';
import 'package:eschool_api/src/model/pool_member_page_response_code.dart';
import 'package:json_annotation/json_annotation.dart';

part 'child_mode_session_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ChildModeSessionResponse {
  /// Returns a new [ChildModeSessionResponse] instance.
  ChildModeSessionResponse({
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
  final ChildModeSession data;

  @JsonKey(name: r'code', required: true, includeIfNull: true)
  final PoolMemberPageResponseCode? code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ChildModeSessionResponse &&
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

  factory ChildModeSessionResponse.fromJson(Map<String, dynamic> json) =>
      _$ChildModeSessionResponseFromJson(json);

  Map<String, dynamic> toJson() => _$ChildModeSessionResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
