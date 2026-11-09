//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/child_mode_session.dart';
import 'package:json_annotation/json_annotation.dart';

part 'child_mode_start_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ChildModeStartResponseData {
  /// Returns a new [ChildModeStartResponseData] instance.
  ChildModeStartResponseData({required this.token, required this.session});

  /// The child token. Send it as the bearer token on the child mode session routes only.
  @JsonKey(name: r'token', required: true, includeIfNull: false)
  final String token;

  @JsonKey(name: r'session', required: true, includeIfNull: false)
  final ChildModeSession session;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ChildModeStartResponseData &&
          other.token == token &&
          other.session == session;

  @override
  int get hashCode => token.hashCode + session.hashCode;

  factory ChildModeStartResponseData.fromJson(Map<String, dynamic> json) =>
      _$ChildModeStartResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$ChildModeStartResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
