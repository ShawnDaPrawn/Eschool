//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'gallery_photo.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GalleryPhoto {
  /// Returns a new [GalleryPhoto] instance.
  GalleryPhoto({
    required this.id,

    required this.publicId,

    required this.caption,

    required this.capturedOn,

    required this.schoolYear,

    required this.sessionYearId,

    required this.activityGroupId,

    required this.scope,

    required this.width,

    required this.height,

    required this.active,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'public_id', required: true, includeIfNull: false)
  final String publicId;

  @JsonKey(name: r'caption', required: true, includeIfNull: true)
  final String? caption;

  @JsonKey(name: r'captured_on', required: true, includeIfNull: false)
  final String capturedOn;

  @JsonKey(name: r'school_year', required: true, includeIfNull: false)
  final String schoolYear;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'activity_group_id', required: true, includeIfNull: true)
  final int? activityGroupId;

  @JsonKey(
    name: r'scope',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GalleryPhotoScopeEnum.unknownDefaultOpenApi,
  )
  final GalleryPhotoScopeEnum scope;

  // minimum: 1
  @JsonKey(name: r'width', required: true, includeIfNull: false)
  final int width;

  // minimum: 1
  @JsonKey(name: r'height', required: true, includeIfNull: false)
  final int height;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GalleryPhoto &&
          other.id == id &&
          other.publicId == publicId &&
          other.caption == caption &&
          other.capturedOn == capturedOn &&
          other.schoolYear == schoolYear &&
          other.sessionYearId == sessionYearId &&
          other.activityGroupId == activityGroupId &&
          other.scope == scope &&
          other.width == width &&
          other.height == height &&
          other.active == active &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      publicId.hashCode +
      (caption == null ? 0 : caption.hashCode) +
      capturedOn.hashCode +
      schoolYear.hashCode +
      sessionYearId.hashCode +
      (activityGroupId == null ? 0 : activityGroupId.hashCode) +
      scope.hashCode +
      width.hashCode +
      height.hashCode +
      active.hashCode +
      revision.hashCode;

  factory GalleryPhoto.fromJson(Map<String, dynamic> json) =>
      _$GalleryPhotoFromJson(json);

  Map<String, dynamic> toJson() => _$GalleryPhotoToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum GalleryPhotoScopeEnum {
  @JsonValue(r'family')
  family(r'family'),
  @JsonValue(r'group')
  group(r'group'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const GalleryPhotoScopeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
