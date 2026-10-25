//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'transfer_consent_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TransferConsentInput {
  /// Returns a new [TransferConsentInput] instance.
  TransferConsentInput({
    required this.operationId,

    required this.revision,

    required this.fingerprint,

    required this.version,

    required this.confirmation,

    this.keepPhotoTags,

    this.galleryFingerprint,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @JsonKey(name: r'version', required: true, includeIfNull: false)
  final String version;

  @JsonKey(name: r'confirmation', required: true, includeIfNull: false)
  final bool confirmation;

  /// Optional explicit guardian choice; omission removes tags on completion.
  @JsonKey(name: r'keep_photo_tags', required: false, includeIfNull: false)
  final bool? keepPhotoTags;

  /// Fresh private selection fingerprint required when keep_photo_tags is true.
  @JsonKey(name: r'gallery_fingerprint', required: false, includeIfNull: false)
  final String? galleryFingerprint;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TransferConsentInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.fingerprint == fingerprint &&
          other.version == version &&
          other.confirmation == confirmation &&
          other.keepPhotoTags == keepPhotoTags &&
          other.galleryFingerprint == galleryFingerprint;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      fingerprint.hashCode +
      version.hashCode +
      confirmation.hashCode +
      keepPhotoTags.hashCode +
      (galleryFingerprint == null ? 0 : galleryFingerprint.hashCode);

  factory TransferConsentInput.fromJson(Map<String, dynamic> json) =>
      _$TransferConsentInputFromJson(json);

  Map<String, dynamic> toJson() => _$TransferConsentInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
