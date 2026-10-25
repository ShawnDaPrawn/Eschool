//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_approval.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeApproval {
  /// Returns a new [PracticeApproval] instance.
  PracticeApproval({
    required this.id,

    required this.versionId,

    required this.revision,

    required this.active,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'version_id', required: true, includeIfNull: false)
  final int versionId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeApproval &&
          other.id == id &&
          other.versionId == versionId &&
          other.revision == revision &&
          other.active == active;

  @override
  int get hashCode =>
      id.hashCode + versionId.hashCode + revision.hashCode + active.hashCode;

  factory PracticeApproval.fromJson(Map<String, dynamic> json) =>
      _$PracticeApprovalFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeApprovalToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
