//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'child_mode_ended_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ChildModeEndedResponseData {
  /// Returns a new [ChildModeEndedResponseData] instance.
  ChildModeEndedResponseData({required this.ended});

  // minimum: 0
  @JsonKey(name: r'ended', required: true, includeIfNull: false)
  final int ended;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ChildModeEndedResponseData && other.ended == ended;

  @override
  int get hashCode => ended.hashCode;

  factory ChildModeEndedResponseData.fromJson(Map<String, dynamic> json) =>
      _$ChildModeEndedResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$ChildModeEndedResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
