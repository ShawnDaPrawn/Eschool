//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'transfer_gallery_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TransferGalleryChoice {
  /// Returns a new [TransferGalleryChoice] instance.
  TransferGalleryChoice({
    required this.fingerprint,

    required this.photos,

    required this.canKeep,
  });

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  // minimum: 0
  // maximum: 200
  @JsonKey(name: r'photos', required: true, includeIfNull: false)
  final int photos;

  @JsonKey(name: r'can_keep', required: true, includeIfNull: false)
  final bool canKeep;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TransferGalleryChoice &&
          other.fingerprint == fingerprint &&
          other.photos == photos &&
          other.canKeep == canKeep;

  @override
  int get hashCode => fingerprint.hashCode + photos.hashCode + canKeep.hashCode;

  factory TransferGalleryChoice.fromJson(Map<String, dynamic> json) =>
      _$TransferGalleryChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$TransferGalleryChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
