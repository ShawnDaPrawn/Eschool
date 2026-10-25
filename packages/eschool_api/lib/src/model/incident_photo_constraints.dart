//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'incident_photo_constraints.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class IncidentPhotoConstraints {
  /// Returns a new [IncidentPhotoConstraints] instance.
  IncidentPhotoConstraints({
    required this.maximumBytes,

    required this.maximumPhotos,

    required this.allowedMimes,
  });

  // minimum: 1
  @JsonKey(name: r'maximum_bytes', required: true, includeIfNull: false)
  final int maximumBytes;

  // minimum: 1
  @JsonKey(name: r'maximum_photos', required: true, includeIfNull: false)
  final int maximumPhotos;

  @JsonKey(name: r'allowed_mimes', required: true, includeIfNull: false)
  final List<String> allowedMimes;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is IncidentPhotoConstraints &&
          other.maximumBytes == maximumBytes &&
          other.maximumPhotos == maximumPhotos &&
          other.allowedMimes == allowedMimes;

  @override
  int get hashCode =>
      maximumBytes.hashCode + maximumPhotos.hashCode + allowedMimes.hashCode;

  factory IncidentPhotoConstraints.fromJson(Map<String, dynamic> json) =>
      _$IncidentPhotoConstraintsFromJson(json);

  Map<String, dynamic> toJson() => _$IncidentPhotoConstraintsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
