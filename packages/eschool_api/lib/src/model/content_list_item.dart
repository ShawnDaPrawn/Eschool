//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'content_list_item.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentListItem {
  /// Returns a new [ContentListItem] instance.
  ContentListItem({
    required this.id,

    required this.title,

    required this.active,

    required this.draftVersionId,

    required this.publishedVersionId,

    required this.draftState,

    required this.revision,

    required this.updatedAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  // minimum: 1
  @JsonKey(name: r'draft_version_id', required: true, includeIfNull: true)
  final int? draftVersionId;

  // minimum: 1
  @JsonKey(name: r'published_version_id', required: true, includeIfNull: true)
  final int? publishedVersionId;

  @JsonKey(
    name: r'draft_state',
    required: true,
    includeIfNull: true,
    unknownEnumValue: ContentListItemDraftStateEnum.unknownDefaultOpenApi,
  )
  final ContentListItemDraftStateEnum? draftState;

  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'updated_at', required: true, includeIfNull: false)
  final DateTime updatedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentListItem &&
          other.id == id &&
          other.title == title &&
          other.active == active &&
          other.draftVersionId == draftVersionId &&
          other.publishedVersionId == publishedVersionId &&
          other.draftState == draftState &&
          other.revision == revision &&
          other.updatedAt == updatedAt;

  @override
  int get hashCode =>
      id.hashCode +
      title.hashCode +
      active.hashCode +
      (draftVersionId == null ? 0 : draftVersionId.hashCode) +
      (publishedVersionId == null ? 0 : publishedVersionId.hashCode) +
      (draftState == null ? 0 : draftState.hashCode) +
      revision.hashCode +
      updatedAt.hashCode;

  factory ContentListItem.fromJson(Map<String, dynamic> json) =>
      _$ContentListItemFromJson(json);

  Map<String, dynamic> toJson() => _$ContentListItemToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ContentListItemDraftStateEnum {
  @JsonValue(r'queued')
  queued(r'queued'),
  @JsonValue(r'scanning')
  scanning(r'scanning'),
  @JsonValue(r'converting')
  converting(r'converting'),
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'published')
  published(r'published'),
  @JsonValue(r'failed')
  failed(r'failed'),
  @JsonValue(r'quarantined')
  quarantined(r'quarantined'),
  @JsonValue(r'superseded')
  superseded(r'superseded'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ContentListItemDraftStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
