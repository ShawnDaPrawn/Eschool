//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'gallery_year.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GalleryYear {
  /// Returns a new [GalleryYear] instance.
  GalleryYear({
    required this.id,

    required this.name,

    required this.startsOn,

    required this.endsOn,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'starts_on', required: true, includeIfNull: false)
  final String startsOn;

  @JsonKey(name: r'ends_on', required: true, includeIfNull: false)
  final String endsOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GalleryYear &&
          other.id == id &&
          other.name == name &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn;

  @override
  int get hashCode =>
      id.hashCode + name.hashCode + startsOn.hashCode + endsOn.hashCode;

  factory GalleryYear.fromJson(Map<String, dynamic> json) =>
      _$GalleryYearFromJson(json);

  Map<String, dynamic> toJson() => _$GalleryYearToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
