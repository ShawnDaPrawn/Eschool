//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/child_mode_ended_response_data.dart';
import 'package:eschool_api/src/model/pool_member_page_response_code.dart';
import 'package:json_annotation/json_annotation.dart';

part 'child_mode_ended_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ChildModeEndedResponse {
  /// Returns a new [ChildModeEndedResponse] instance.
  ChildModeEndedResponse({
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
  final ChildModeEndedResponseData data;

  @JsonKey(name: r'code', required: true, includeIfNull: true)
  final PoolMemberPageResponseCode? code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ChildModeEndedResponse &&
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

  factory ChildModeEndedResponse.fromJson(Map<String, dynamic> json) =>
      _$ChildModeEndedResponseFromJson(json);

  Map<String, dynamic> toJson() => _$ChildModeEndedResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
