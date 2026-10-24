//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/content_history.dart';
import 'package:eschool_api/src/model/content_version.dart';
import 'package:json_annotation/json_annotation.dart';

part 'content_item.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentItem {
  /// Returns a new [ContentItem] instance.
  ContentItem({
    required this.id,

    required this.canWrite,

    required this.active,

    required this.revision,

    required this.authorId,

    required this.draft,

    required this.published,

    required this.page,

    required this.lastPage,

    required this.versions,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'can_write', required: true, includeIfNull: false)
  final bool canWrite;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'author_id', required: true, includeIfNull: false)
  final int authorId;

  @JsonKey(name: r'draft', required: true, includeIfNull: true)
  final ContentVersion? draft;

  @JsonKey(name: r'published', required: true, includeIfNull: true)
  final ContentVersion? published;

  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'versions', required: true, includeIfNull: false)
  final List<ContentHistory> versions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentItem &&
          other.id == id &&
          other.canWrite == canWrite &&
          other.active == active &&
          other.revision == revision &&
          other.authorId == authorId &&
          other.draft == draft &&
          other.published == published &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.versions == versions;

  @override
  int get hashCode =>
      id.hashCode +
      canWrite.hashCode +
      active.hashCode +
      revision.hashCode +
      authorId.hashCode +
      (draft == null ? 0 : draft.hashCode) +
      (published == null ? 0 : published.hashCode) +
      page.hashCode +
      lastPage.hashCode +
      versions.hashCode;

  factory ContentItem.fromJson(Map<String, dynamic> json) =>
      _$ContentItemFromJson(json);

  Map<String, dynamic> toJson() => _$ContentItemToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
