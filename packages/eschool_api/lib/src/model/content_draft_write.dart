//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/content_selection.dart';
import 'package:json_annotation/json_annotation.dart';

part 'content_draft_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentDraftWrite {
  /// Returns a new [ContentDraftWrite] instance.
  ContentDraftWrite({
    required this.operationId,

    required this.itemRevision,

    required this.versionRevision,

    required this.selection,

    required this.markdown,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'item_revision', required: true, includeIfNull: false)
  final int itemRevision;

  // minimum: 1
  @JsonKey(name: r'version_revision', required: true, includeIfNull: false)
  final int versionRevision;

  @JsonKey(name: r'selection', required: true, includeIfNull: false)
  final ContentSelection selection;

  @JsonKey(name: r'markdown', required: true, includeIfNull: false)
  final String markdown;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentDraftWrite &&
          other.operationId == operationId &&
          other.itemRevision == itemRevision &&
          other.versionRevision == versionRevision &&
          other.selection == selection &&
          other.markdown == markdown;

  @override
  int get hashCode =>
      operationId.hashCode +
      itemRevision.hashCode +
      versionRevision.hashCode +
      selection.hashCode +
      markdown.hashCode;

  factory ContentDraftWrite.fromJson(Map<String, dynamic> json) =>
      _$ContentDraftWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ContentDraftWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
