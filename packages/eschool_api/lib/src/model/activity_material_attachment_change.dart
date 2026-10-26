//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'activity_material_attachment_change.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityMaterialAttachmentChange {
  /// Returns a new [ActivityMaterialAttachmentChange] instance.
  ActivityMaterialAttachmentChange({
    required this.id,

    required this.activityGroupId,

    required this.materialId,

    required this.name,

    required this.mime,

    required this.bytes,

    required this.revision,

    required this.removed,

    required this.materialRevision,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  @JsonKey(name: r'activity_group_id', required: true, includeIfNull: false)
  final int activityGroupId;

  // minimum: 1
  @JsonKey(name: r'material_id', required: true, includeIfNull: false)
  final int materialId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(
    name: r'mime',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        ActivityMaterialAttachmentChangeMimeEnum.unknownDefaultOpenApi,
  )
  final ActivityMaterialAttachmentChangeMimeEnum mime;

  // minimum: 1
  // maximum: 5242880
  @JsonKey(name: r'bytes', required: true, includeIfNull: false)
  final int bytes;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'removed', required: true, includeIfNull: false)
  final bool removed;

  // minimum: 1
  @JsonKey(name: r'material_revision', required: true, includeIfNull: false)
  final int materialRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityMaterialAttachmentChange &&
          other.id == id &&
          other.activityGroupId == activityGroupId &&
          other.materialId == materialId &&
          other.name == name &&
          other.mime == mime &&
          other.bytes == bytes &&
          other.revision == revision &&
          other.removed == removed &&
          other.materialRevision == materialRevision;

  @override
  int get hashCode =>
      id.hashCode +
      activityGroupId.hashCode +
      materialId.hashCode +
      name.hashCode +
      mime.hashCode +
      bytes.hashCode +
      revision.hashCode +
      removed.hashCode +
      materialRevision.hashCode;

  factory ActivityMaterialAttachmentChange.fromJson(
    Map<String, dynamic> json,
  ) => _$ActivityMaterialAttachmentChangeFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ActivityMaterialAttachmentChangeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ActivityMaterialAttachmentChangeMimeEnum {
  @JsonValue(r'application/pdf')
  applicationSlashPdf(r'application/pdf'),
  @JsonValue(r'image/jpeg')
  imageSlashJpeg(r'image/jpeg'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ActivityMaterialAttachmentChangeMimeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
