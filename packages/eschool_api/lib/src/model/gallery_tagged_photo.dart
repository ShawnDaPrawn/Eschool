//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/gallery_photo.dart';
import 'package:json_annotation/json_annotation.dart';

part 'gallery_tagged_photo.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GalleryTaggedPhoto {
  /// Returns a new [GalleryTaggedPhoto] instance.
  GalleryTaggedPhoto({
    required this.tagId,

    required this.tagRevision,

    required this.status,

    required this.retainedOnTransfer,

    required this.photo,
  });

  // minimum: 1
  @JsonKey(name: r'tag_id', required: true, includeIfNull: false)
  final int tagId;

  // minimum: 1
  @JsonKey(name: r'tag_revision', required: true, includeIfNull: false)
  final int tagRevision;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: GalleryTaggedPhotoStatusEnum.unknownDefaultOpenApi,
  )
  final GalleryTaggedPhotoStatusEnum status;

  @JsonKey(name: r'retained_on_transfer', required: true, includeIfNull: false)
  final bool retainedOnTransfer;

  @JsonKey(name: r'photo', required: true, includeIfNull: false)
  final GalleryPhoto photo;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GalleryTaggedPhoto &&
          other.tagId == tagId &&
          other.tagRevision == tagRevision &&
          other.status == status &&
          other.retainedOnTransfer == retainedOnTransfer &&
          other.photo == photo;

  @override
  int get hashCode =>
      tagId.hashCode +
      tagRevision.hashCode +
      status.hashCode +
      retainedOnTransfer.hashCode +
      photo.hashCode;

  factory GalleryTaggedPhoto.fromJson(Map<String, dynamic> json) =>
      _$GalleryTaggedPhotoFromJson(json);

  Map<String, dynamic> toJson() => _$GalleryTaggedPhotoToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum GalleryTaggedPhotoStatusEnum {
  @JsonValue(r'awaiting_guardian')
  awaitingGuardian(r'awaiting_guardian'),
  @JsonValue(r'approved')
  approved(r'approved'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const GalleryTaggedPhotoStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
