//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'content_revision_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentRevisionWrite {
  /// Returns a new [ContentRevisionWrite] instance.
  ContentRevisionWrite({
    required this.operationId,

    required this.itemRevision,

    required this.versionRevision,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'item_revision', required: true, includeIfNull: false)
  final int itemRevision;

  // minimum: 1
  @JsonKey(name: r'version_revision', required: true, includeIfNull: false)
  final int versionRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentRevisionWrite &&
          other.operationId == operationId &&
          other.itemRevision == itemRevision &&
          other.versionRevision == versionRevision;

  @override
  int get hashCode =>
      operationId.hashCode + itemRevision.hashCode + versionRevision.hashCode;

  factory ContentRevisionWrite.fromJson(Map<String, dynamic> json) =>
      _$ContentRevisionWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ContentRevisionWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
