//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/content_selection.dart';
import 'package:json_annotation/json_annotation.dart';

part 'content_preview.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentPreview {
  /// Returns a new [ContentPreview] instance.
  ContentPreview({
    required this.id,

    required this.contentItemId,

    required this.itemRevision,

    required this.versionRevision,

    required this.selection,

    required this.markdown,

    required this.fingerprint,

    required this.learnerCount,

    required this.reviewRequired,

    required this.requiresReason,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'content_item_id', required: true, includeIfNull: false)
  final int contentItemId;

  @JsonKey(name: r'item_revision', required: true, includeIfNull: false)
  final int itemRevision;

  @JsonKey(name: r'version_revision', required: true, includeIfNull: false)
  final int versionRevision;

  @JsonKey(name: r'selection', required: true, includeIfNull: false)
  final ContentSelection selection;

  @JsonKey(name: r'markdown', required: true, includeIfNull: false)
  final String markdown;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @JsonKey(name: r'learner_count', required: true, includeIfNull: false)
  final int learnerCount;

  @JsonKey(name: r'review_required', required: true, includeIfNull: false)
  final bool reviewRequired;

  @JsonKey(name: r'requires_reason', required: true, includeIfNull: false)
  final bool requiresReason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentPreview &&
          other.id == id &&
          other.contentItemId == contentItemId &&
          other.itemRevision == itemRevision &&
          other.versionRevision == versionRevision &&
          other.selection == selection &&
          other.markdown == markdown &&
          other.fingerprint == fingerprint &&
          other.learnerCount == learnerCount &&
          other.reviewRequired == reviewRequired &&
          other.requiresReason == requiresReason;

  @override
  int get hashCode =>
      id.hashCode +
      contentItemId.hashCode +
      itemRevision.hashCode +
      versionRevision.hashCode +
      selection.hashCode +
      markdown.hashCode +
      fingerprint.hashCode +
      learnerCount.hashCode +
      reviewRequired.hashCode +
      requiresReason.hashCode;

  factory ContentPreview.fromJson(Map<String, dynamic> json) =>
      _$ContentPreviewFromJson(json);

  Map<String, dynamic> toJson() => _$ContentPreviewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
