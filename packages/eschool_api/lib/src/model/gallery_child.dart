//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'gallery_child.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GalleryChild {
  /// Returns a new [GalleryChild] instance.
  GalleryChild({
    required this.id,

    required this.name,

    required this.handle,

    required this.canApprove,

    required this.archived,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'handle', required: true, includeIfNull: true)
  final String? handle;

  @JsonKey(name: r'can_approve', required: true, includeIfNull: false)
  final bool canApprove;

  @JsonKey(name: r'archived', required: true, includeIfNull: false)
  final bool archived;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GalleryChild &&
          other.id == id &&
          other.name == name &&
          other.handle == handle &&
          other.canApprove == canApprove &&
          other.archived == archived;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (handle == null ? 0 : handle.hashCode) +
      canApprove.hashCode +
      archived.hashCode;

  factory GalleryChild.fromJson(Map<String, dynamic> json) =>
      _$GalleryChildFromJson(json);

  Map<String, dynamic> toJson() => _$GalleryChildToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
