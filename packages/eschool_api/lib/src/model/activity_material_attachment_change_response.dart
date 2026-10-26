//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/activity_material_attachment_change.dart';
import 'package:json_annotation/json_annotation.dart';

part 'activity_material_attachment_change_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityMaterialAttachmentChangeResponse {
  /// Returns a new [ActivityMaterialAttachmentChangeResponse] instance.
  ActivityMaterialAttachmentChangeResponse({
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
  final ActivityMaterialAttachmentChange data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        ActivityMaterialAttachmentChangeResponseCodeEnum.unknownDefaultOpenApi,
  )
  final ActivityMaterialAttachmentChangeResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityMaterialAttachmentChangeResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory ActivityMaterialAttachmentChangeResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$ActivityMaterialAttachmentChangeResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ActivityMaterialAttachmentChangeResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ActivityMaterialAttachmentChangeResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const ActivityMaterialAttachmentChangeResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
