//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'content_publish_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentPublishWrite {
  /// Returns a new [ContentPublishWrite] instance.
  ContentPublishWrite({
    required this.operationId,

    required this.itemRevision,

    required this.versionRevision,

    required this.fingerprint,

    required this.reviewed,

    this.reason,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'item_revision', required: true, includeIfNull: false)
  final int itemRevision;

  // minimum: 1
  @JsonKey(name: r'version_revision', required: true, includeIfNull: false)
  final int versionRevision;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  /// Must be true: the publisher has explicitly reviewed the body, tags and audience.
  @JsonKey(name: r'reviewed', required: true, includeIfNull: false)
  final bool reviewed;

  @JsonKey(name: r'reason', required: false, includeIfNull: false)
  final String? reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentPublishWrite &&
          other.operationId == operationId &&
          other.itemRevision == itemRevision &&
          other.versionRevision == versionRevision &&
          other.fingerprint == fingerprint &&
          other.reviewed == reviewed &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode +
      itemRevision.hashCode +
      versionRevision.hashCode +
      fingerprint.hashCode +
      reviewed.hashCode +
      (reason == null ? 0 : reason.hashCode);

  factory ContentPublishWrite.fromJson(Map<String, dynamic> json) =>
      _$ContentPublishWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ContentPublishWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
