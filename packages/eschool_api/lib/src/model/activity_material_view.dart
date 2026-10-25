//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'activity_material_view.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityMaterialView {
  /// Returns a new [ActivityMaterialView] instance.
  ActivityMaterialView({
    required this.id,

    required this.activityGroupId,

    required this.authorId,

    required this.title,

    required this.markdown,

    required this.published,

    required this.revision,

    required this.html,
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

  @JsonKey(name: r'html', required: true, includeIfNull: false)
  final String html;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityMaterialView &&
          other.id == id &&
          other.activityGroupId == activityGroupId &&
          other.authorId == authorId &&
          other.title == title &&
          other.markdown == markdown &&
          other.published == published &&
          other.revision == revision &&
          other.html == html;

  @override
  int get hashCode =>
      id.hashCode +
      activityGroupId.hashCode +
      authorId.hashCode +
      title.hashCode +
      markdown.hashCode +
      published.hashCode +
      revision.hashCode +
      html.hashCode;

  factory ActivityMaterialView.fromJson(Map<String, dynamic> json) =>
      _$ActivityMaterialViewFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityMaterialViewToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
