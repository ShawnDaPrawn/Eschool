//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'gallery_revision.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GalleryRevision {
  /// Returns a new [GalleryRevision] instance.
  GalleryRevision({required this.operationId, required this.revision});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GalleryRevision &&
          other.operationId == operationId &&
          other.revision == revision;

  @override
  int get hashCode => operationId.hashCode + revision.hashCode;

  factory GalleryRevision.fromJson(Map<String, dynamic> json) =>
      _$GalleryRevisionFromJson(json);

  Map<String, dynamic> toJson() => _$GalleryRevisionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
