//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'activity_material.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityMaterial {
  /// Returns a new [ActivityMaterial] instance.
  ActivityMaterial({
    required this.id,

    required this.activityGroupId,

    required this.authorId,

    required this.title,

    required this.markdown,

    required this.published,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'activity_group_id', required: true, includeIfNull: false)
  final int activityGroupId;

  // minimum: 1
  @JsonKey(name: r'author_id', required: true, includeIfNull: false)
  final int authorId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'markdown', required: true, includeIfNull: false)
  final String markdown;

  @JsonKey(name: r'published', required: true, includeIfNull: false)
  final bool published;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityMaterial &&
          other.id == id &&
          other.activityGroupId == activityGroupId &&
          other.authorId == authorId &&
          other.title == title &&
          other.markdown == markdown &&
          other.published == published &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      activityGroupId.hashCode +
      authorId.hashCode +
      title.hashCode +
      markdown.hashCode +
      published.hashCode +
      revision.hashCode;

  factory ActivityMaterial.fromJson(Map<String, dynamic> json) =>
      _$ActivityMaterialFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityMaterialToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
