//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_meeting_slot.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_meeting_slot_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyMeetingSlotResponse {
  /// Returns a new [FamilyMeetingSlotResponse] instance.
  FamilyMeetingSlotResponse({
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
  final FamilyMeetingSlot data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: FamilyMeetingSlotResponseCodeEnum.unknownDefaultOpenApi,
  )
  final FamilyMeetingSlotResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyMeetingSlotResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory FamilyMeetingSlotResponse.fromJson(Map<String, dynamic> json) =>
      _$FamilyMeetingSlotResponseFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyMeetingSlotResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum FamilyMeetingSlotResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const FamilyMeetingSlotResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
