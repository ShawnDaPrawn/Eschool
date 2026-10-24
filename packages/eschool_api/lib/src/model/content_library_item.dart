//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'content_library_item.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentLibraryItem {
  /// Returns a new [ContentLibraryItem] instance.
  ContentLibraryItem({
    required this.id,

    required this.versionId,

    required this.title,

    required this.summary,

    required this.subjectId,

    required this.subjectName,

    required this.publishedAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'version_id', required: true, includeIfNull: false)
  final int versionId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'summary', required: true, includeIfNull: false)
  final String summary;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: false)
  final String subjectName;

  @JsonKey(name: r'published_at', required: true, includeIfNull: false)
  final DateTime publishedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentLibraryItem &&
          other.id == id &&
          other.versionId == versionId &&
          other.title == title &&
          other.summary == summary &&
          other.subjectId == subjectId &&
          other.subjectName == subjectName &&
          other.publishedAt == publishedAt;

  @override
  int get hashCode =>
      id.hashCode +
      versionId.hashCode +
      title.hashCode +
      summary.hashCode +
      subjectId.hashCode +
      subjectName.hashCode +
      publishedAt.hashCode;

  factory ContentLibraryItem.fromJson(Map<String, dynamic> json) =>
      _$ContentLibraryItemFromJson(json);

  Map<String, dynamic> toJson() => _$ContentLibraryItemToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
