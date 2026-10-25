//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'incident_photo_read_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class IncidentPhotoReadInput {
  /// Returns a new [IncidentPhotoReadInput] instance.
  IncidentPhotoReadInput({required this.secret, required this.photoId});

  @JsonKey(name: r'secret', required: true, includeIfNull: false)
  final String secret;

  @JsonKey(name: r'photo_id', required: true, includeIfNull: false)
  final String photoId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is IncidentPhotoReadInput &&
          other.secret == secret &&
          other.photoId == photoId;

  @override
  int get hashCode => secret.hashCode + photoId.hashCode;

  factory IncidentPhotoReadInput.fromJson(Map<String, dynamic> json) =>
      _$IncidentPhotoReadInputFromJson(json);

  Map<String, dynamic> toJson() => _$IncidentPhotoReadInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
