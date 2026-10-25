//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/gallery_tagged_photo.dart';
import 'package:json_annotation/json_annotation.dart';

part 'gallery_family_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GalleryFamilyPage {
  /// Returns a new [GalleryFamilyPage] instance.
  GalleryFamilyPage({
    required this.items,

    required this.total,

    required this.nextBefore,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<GalleryTaggedPhoto> items;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  // minimum: 1
  @JsonKey(name: r'next_before', required: true, includeIfNull: true)
  final int? nextBefore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GalleryFamilyPage &&
          other.items == items &&
          other.total == total &&
          other.nextBefore == nextBefore;

  @override
  int get hashCode =>
      items.hashCode +
      total.hashCode +
      (nextBefore == null ? 0 : nextBefore.hashCode);

  factory GalleryFamilyPage.fromJson(Map<String, dynamic> json) =>
      _$GalleryFamilyPageFromJson(json);

  Map<String, dynamic> toJson() => _$GalleryFamilyPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
