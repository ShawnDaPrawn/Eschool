//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'gallery_group.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GalleryGroup {
  /// Returns a new [GalleryGroup] instance.
  GalleryGroup({
    required this.id,

    required this.name,

    required this.sessionYearId,

    required this.canUpload,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'can_upload', required: true, includeIfNull: false)
  final bool canUpload;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GalleryGroup &&
          other.id == id &&
          other.name == name &&
          other.sessionYearId == sessionYearId &&
          other.canUpload == canUpload;

  @override
  int get hashCode =>
      id.hashCode + name.hashCode + sessionYearId.hashCode + canUpload.hashCode;

  factory GalleryGroup.fromJson(Map<String, dynamic> json) =>
      _$GalleryGroupFromJson(json);

  Map<String, dynamic> toJson() => _$GalleryGroupToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
