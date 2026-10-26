//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'gallery_reference.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GalleryReference {
  /// Returns a new [GalleryReference] instance.
  GalleryReference({required this.id, required this.revision});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GalleryReference && other.id == id && other.revision == revision;

  @override
  int get hashCode => id.hashCode + revision.hashCode;

  factory GalleryReference.fromJson(Map<String, dynamic> json) =>
      _$GalleryReferenceFromJson(json);

  Map<String, dynamic> toJson() => _$GalleryReferenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
