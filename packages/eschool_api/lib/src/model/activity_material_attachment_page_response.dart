//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/activity_material_attachment_page.dart';
import 'package:json_annotation/json_annotation.dart';

part 'activity_material_attachment_page_response.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityMaterialAttachmentPageResponse {
  /// Returns a new [ActivityMaterialAttachmentPageResponse] instance.
  ActivityMaterialAttachmentPageResponse({
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
  final ActivityMaterialAttachmentPage data;

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        ActivityMaterialAttachmentPageResponseCodeEnum.unknownDefaultOpenApi,
  )
  final ActivityMaterialAttachmentPageResponseCodeEnum code;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityMaterialAttachmentPageResponse &&
          other.error == error &&
          other.message == message &&
          other.data == data &&
          other.code == code;

  @override
  int get hashCode =>
      error.hashCode + message.hashCode + data.hashCode + code.hashCode;

  factory ActivityMaterialAttachmentPageResponse.fromJson(
    Map<String, dynamic> json,
  ) => _$ActivityMaterialAttachmentPageResponseFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ActivityMaterialAttachmentPageResponseToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ActivityMaterialAttachmentPageResponseCodeEnum {
  @JsonValue(200)
  number200('200'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const ActivityMaterialAttachmentPageResponseCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
