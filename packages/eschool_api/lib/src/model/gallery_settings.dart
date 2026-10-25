//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'gallery_settings.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GallerySettings {
  /// Returns a new [GallerySettings] instance.
  GallerySettings({required this.guardianRequired, required this.revision});

  @JsonKey(name: r'guardian_required', required: true, includeIfNull: false)
  final bool guardianRequired;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GallerySettings &&
          other.guardianRequired == guardianRequired &&
          other.revision == revision;

  @override
  int get hashCode => guardianRequired.hashCode + revision.hashCode;

  factory GallerySettings.fromJson(Map<String, dynamic> json) =>
      _$GallerySettingsFromJson(json);

  Map<String, dynamic> toJson() => _$GallerySettingsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
