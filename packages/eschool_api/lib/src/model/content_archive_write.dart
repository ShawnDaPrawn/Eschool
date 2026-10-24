//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'content_archive_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentArchiveWrite {
  /// Returns a new [ContentArchiveWrite] instance.
  ContentArchiveWrite({
    required this.operationId,

    required this.itemRevision,

    required this.reason,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'item_revision', required: true, includeIfNull: false)
  final int itemRevision;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentArchiveWrite &&
          other.operationId == operationId &&
          other.itemRevision == itemRevision &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode + itemRevision.hashCode + reason.hashCode;

  factory ContentArchiveWrite.fromJson(Map<String, dynamic> json) =>
      _$ContentArchiveWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ContentArchiveWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
