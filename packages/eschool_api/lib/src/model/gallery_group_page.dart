//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/gallery_photo.dart';
import 'package:json_annotation/json_annotation.dart';

part 'gallery_group_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GalleryGroupPage {
  /// Returns a new [GalleryGroupPage] instance.
  GalleryGroupPage({required this.items, required this.nextBefore});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<GalleryPhoto> items;

  // minimum: 1
  @JsonKey(name: r'next_before', required: true, includeIfNull: true)
  final int? nextBefore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GalleryGroupPage &&
          other.items == items &&
          other.nextBefore == nextBefore;

  @override
  int get hashCode =>
      items.hashCode + (nextBefore == null ? 0 : nextBefore.hashCode);

  factory GalleryGroupPage.fromJson(Map<String, dynamic> json) =>
      _$GalleryGroupPageFromJson(json);

  Map<String, dynamic> toJson() => _$GalleryGroupPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
