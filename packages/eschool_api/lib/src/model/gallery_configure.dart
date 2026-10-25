//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'gallery_configure.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GalleryConfigure {
  /// Returns a new [GalleryConfigure] instance.
  GalleryConfigure({
    required this.operationId,

    required this.revision,

    required this.guardianRequired,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'guardian_required', required: true, includeIfNull: false)
  final bool guardianRequired;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GalleryConfigure &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.guardianRequired == guardianRequired;

  @override
  int get hashCode =>
      operationId.hashCode + revision.hashCode + guardianRequired.hashCode;

  factory GalleryConfigure.fromJson(Map<String, dynamic> json) =>
      _$GalleryConfigureFromJson(json);

  Map<String, dynamic> toJson() => _$GalleryConfigureToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
