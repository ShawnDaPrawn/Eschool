//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_meeting_page.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_meeting_page_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyMeetingPageResponse {
  /// Returns a new [FamilyMeetingPageResponse] instance.
  FamilyMeetingPageResponse({
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
  final FamilyMeetingPage data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: FamilyMeetingPageResponseCodeEnum.unknownDefaultOpenApi,
  )
  final FamilyMeetingPageResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyMeetingPageResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory FamilyMeetingPageResponse.fromJson(Map<String, dynamic> json) =>
      _$FamilyMeetingPageResponseFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyMeetingPageResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum FamilyMeetingPageResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const FamilyMeetingPageResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
