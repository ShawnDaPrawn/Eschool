//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'foundation_grant_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FoundationGrantInput {
  /// Returns a new [FoundationGrantInput] instance.
  FoundationGrantInput({
    required this.operationId,

    required this.deviceId,

    required this.teachingGroupId,

    required this.minutes,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'device_id', required: true, includeIfNull: false)
  final String deviceId;

  // minimum: 1
  @JsonKey(name: r'teaching_group_id', required: true, includeIfNull: false)
  final int teachingGroupId;

  // minimum: 1
  // maximum: 60
  @JsonKey(name: r'minutes', required: true, includeIfNull: false)
  final int minutes;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FoundationGrantInput &&
          other.operationId == operationId &&
          other.deviceId == deviceId &&
          other.teachingGroupId == teachingGroupId &&
          other.minutes == minutes;

  @override
  int get hashCode =>
      operationId.hashCode +
      deviceId.hashCode +
      teachingGroupId.hashCode +
      minutes.hashCode;

  factory FoundationGrantInput.fromJson(Map<String, dynamic> json) =>
      _$FoundationGrantInputFromJson(json);

  Map<String, dynamic> toJson() => _$FoundationGrantInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
