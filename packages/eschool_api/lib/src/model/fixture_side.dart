//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/fixture_side_selections_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'fixture_side.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureSide {
  /// Returns a new [FixtureSide] instance.
  FixtureSide({
    required this.activityGroupId,

    required this.groupName,

    required this.teamLabel,

    required this.sheetShared,

    required this.notes,

    required this.revision,

    required this.selections,
  });

  @JsonKey(name: r'activity_group_id', required: true, includeIfNull: false)
  final int activityGroupId;

  @JsonKey(name: r'group_name', required: true, includeIfNull: false)
  final String groupName;

  @JsonKey(name: r'team_label', required: true, includeIfNull: false)
  final String teamLabel;

  @JsonKey(name: r'sheet_shared', required: true, includeIfNull: false)
  final bool sheetShared;

  @JsonKey(name: r'notes', required: true, includeIfNull: true)
  final String? notes;

  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'selections', required: true, includeIfNull: false)
  final List<FixtureSideSelectionsInner> selections;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FixtureSide &&
          other.activityGroupId == activityGroupId &&
          other.groupName == groupName &&
          other.teamLabel == teamLabel &&
          other.sheetShared == sheetShared &&
          other.notes == notes &&
          other.revision == revision &&
          other.selections == selections;

  @override
  int get hashCode =>
      activityGroupId.hashCode +
      groupName.hashCode +
      teamLabel.hashCode +
      sheetShared.hashCode +
      (notes == null ? 0 : notes.hashCode) +
      revision.hashCode +
      selections.hashCode;

  factory FixtureSide.fromJson(Map<String, dynamic> json) =>
      _$FixtureSideFromJson(json);

  Map<String, dynamic> toJson() => _$FixtureSideToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
