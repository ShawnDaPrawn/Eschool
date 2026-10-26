//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'gallery_propose.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class GalleryPropose {
  /// Returns a new [GalleryPropose] instance.
  GalleryPropose({required this.operationId, this.learnerId, this.handle});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: false, includeIfNull: false)
  final int? learnerId;

  @JsonKey(name: r'handle', required: false, includeIfNull: false)
  final String? handle;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is GalleryPropose &&
          other.operationId == operationId &&
          other.learnerId == learnerId &&
          other.handle == handle;

  @override
  int get hashCode =>
      operationId.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode) +
      (handle == null ? 0 : handle.hashCode);

  factory GalleryPropose.fromJson(Map<String, dynamic> json) =>
      _$GalleryProposeFromJson(json);

  Map<String, dynamic> toJson() => _$GalleryProposeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
