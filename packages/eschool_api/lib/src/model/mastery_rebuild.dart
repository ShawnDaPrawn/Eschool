//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'mastery_rebuild.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MasteryRebuild {
  /// Returns a new [MasteryRebuild] instance.
  MasteryRebuild({required this.groupId, required this.queued});

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'queued', required: true, includeIfNull: false)
  final bool queued;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MasteryRebuild &&
          other.groupId == groupId &&
          other.queued == queued;

  @override
  int get hashCode => groupId.hashCode + queued.hashCode;

  factory MasteryRebuild.fromJson(Map<String, dynamic> json) =>
      _$MasteryRebuildFromJson(json);

  Map<String, dynamic> toJson() => _$MasteryRebuildToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
