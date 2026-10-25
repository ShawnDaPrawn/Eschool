//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'incident_photo.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class IncidentPhoto {
  /// Returns a new [IncidentPhoto] instance.
  IncidentPhoto({
    required this.id,

    required this.mime,

    required this.bytes,

    required this.width,

    required this.height,

    required this.thumbnailBase64,

    required this.createdAt,

    required this.reportRevision,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(
    name: r'mime',
    required: true,
    includeIfNull: false,
    unknownEnumValue: IncidentPhotoMimeEnum.unknownDefaultOpenApi,
  )
  final IncidentPhotoMimeEnum mime;

  // minimum: 1
  @JsonKey(name: r'bytes', required: true, includeIfNull: false)
  final int bytes;

  // minimum: 1
  @JsonKey(name: r'width', required: true, includeIfNull: false)
  final int width;

  // minimum: 1
  @JsonKey(name: r'height', required: true, includeIfNull: false)
  final int height;

  @JsonKey(name: r'thumbnail_base64', required: true, includeIfNull: false)
  final String thumbnailBase64;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  // minimum: 1
  @JsonKey(name: r'report_revision', required: true, includeIfNull: false)
  final int reportRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is IncidentPhoto &&
          other.id == id &&
          other.mime == mime &&
          other.bytes == bytes &&
          other.width == width &&
          other.height == height &&
          other.thumbnailBase64 == thumbnailBase64 &&
          other.createdAt == createdAt &&
          other.reportRevision == reportRevision;

  @override
  int get hashCode =>
      id.hashCode +
      mime.hashCode +
      bytes.hashCode +
      width.hashCode +
      height.hashCode +
      thumbnailBase64.hashCode +
      createdAt.hashCode +
      reportRevision.hashCode;

  factory IncidentPhoto.fromJson(Map<String, dynamic> json) =>
      _$IncidentPhotoFromJson(json);

  Map<String, dynamic> toJson() => _$IncidentPhotoToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum IncidentPhotoMimeEnum {
  @JsonValue(r'image/jpeg')
  imageSlashJpeg(r'image/jpeg'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const IncidentPhotoMimeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
