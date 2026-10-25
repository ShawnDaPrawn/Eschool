//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'activity_material_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityMaterialWrite {
  /// Returns a new [ActivityMaterialWrite] instance.
  ActivityMaterialWrite({
    required this.operationId,

    required this.revision,

    required this.title,

    required this.markdown,

    required this.published,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'markdown', required: true, includeIfNull: false)
  final String markdown;

  @JsonKey(name: r'published', required: true, includeIfNull: false)
  final bool published;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityMaterialWrite &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.title == title &&
          other.markdown == markdown &&
          other.published == published;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      title.hashCode +
      markdown.hashCode +
      published.hashCode;

  factory ActivityMaterialWrite.fromJson(Map<String, dynamic> json) =>
      _$ActivityMaterialWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityMaterialWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
