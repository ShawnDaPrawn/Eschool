//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'content_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentReceipt {
  /// Returns a new [ContentReceipt] instance.
  ContentReceipt({
    required this.id,

    required this.versionId,

    required this.itemRevision,

    required this.versionRevision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'version_id', required: true, includeIfNull: true)
  final int? versionId;

  @JsonKey(name: r'item_revision', required: true, includeIfNull: false)
  final int itemRevision;

  @JsonKey(name: r'version_revision', required: true, includeIfNull: true)
  final int? versionRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentReceipt &&
          other.id == id &&
          other.versionId == versionId &&
          other.itemRevision == itemRevision &&
          other.versionRevision == versionRevision;

  @override
  int get hashCode =>
      id.hashCode +
      (versionId == null ? 0 : versionId.hashCode) +
      itemRevision.hashCode +
      (versionRevision == null ? 0 : versionRevision.hashCode);

  factory ContentReceipt.fromJson(Map<String, dynamic> json) =>
      _$ContentReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$ContentReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
