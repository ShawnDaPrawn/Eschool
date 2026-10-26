//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/activity_material_attachment.dart';
import 'package:json_annotation/json_annotation.dart';

part 'activity_material_attachment_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityMaterialAttachmentPage {
  /// Returns a new [ActivityMaterialAttachmentPage] instance.
  ActivityMaterialAttachmentPage({
    required this.items,

    required this.materialRevision,

    required this.canUpload,

    required this.canRemove,
  });

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<ActivityMaterialAttachment> items;

  // minimum: 1
  @JsonKey(name: r'material_revision', required: true, includeIfNull: false)
  final int materialRevision;

  @JsonKey(name: r'can_upload', required: true, includeIfNull: false)
  final bool canUpload;

  @JsonKey(name: r'can_remove', required: true, includeIfNull: false)
  final bool canRemove;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityMaterialAttachmentPage &&
          other.items == items &&
          other.materialRevision == materialRevision &&
          other.canUpload == canUpload &&
          other.canRemove == canRemove;

  @override
  int get hashCode =>
      items.hashCode +
      materialRevision.hashCode +
      canUpload.hashCode +
      canRemove.hashCode;

  factory ActivityMaterialAttachmentPage.fromJson(Map<String, dynamic> json) =>
      _$ActivityMaterialAttachmentPageFromJson(json);

  Map<String, dynamic> toJson() => _$ActivityMaterialAttachmentPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
