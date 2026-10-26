//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'activity_material_attachment_remove.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivityMaterialAttachmentRemove {
  /// Returns a new [ActivityMaterialAttachmentRemove] instance.
  ActivityMaterialAttachmentRemove({
    required this.operationId,

    required this.revision,

    required this.materialRevision,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'material_revision', required: true, includeIfNull: false)
  final int materialRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivityMaterialAttachmentRemove &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.materialRevision == materialRevision;

  @override
  int get hashCode =>
      operationId.hashCode + revision.hashCode + materialRevision.hashCode;

  factory ActivityMaterialAttachmentRemove.fromJson(
    Map<String, dynamic> json,
  ) => _$ActivityMaterialAttachmentRemoveFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ActivityMaterialAttachmentRemoveToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
