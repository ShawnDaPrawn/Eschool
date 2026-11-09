//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/fixture_side_write_selections_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'fixture_side_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FixtureSideWrite {
  /// Returns a new [FixtureSideWrite] instance.
  FixtureSideWrite({
    required this.operationId,

    required this.revision,

    this.activityGroupId,

    required this.teamLabel,

    required this.sheetShared,

    this.notes,

    required this.selections,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'activity_group_id', required: false, includeIfNull: false)
  final int? activityGroupId;

  @JsonKey(name: r'team_label', required: true, includeIfNull: false)
  final String teamLabel;

  @JsonKey(name: r'sheet_shared', required: true, includeIfNull: false)
  final bool sheetShared;

  @JsonKey(name: r'notes', required: false, includeIfNull: false)
  final String? notes;

  @JsonKey(name: r'selections', required: true, includeIfNull: false)
  final List<FixtureSideWriteSelectionsInner> selections;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FixtureSideWrite &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.activityGroupId == activityGroupId &&
          other.teamLabel == teamLabel &&
          other.sheetShared == sheetShared &&
          other.notes == notes &&
          other.selections == selections;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      activityGroupId.hashCode +
      teamLabel.hashCode +
      sheetShared.hashCode +
      (notes == null ? 0 : notes.hashCode) +
      selections.hashCode;

  factory FixtureSideWrite.fromJson(Map<String, dynamic> json) =>
      _$FixtureSideWriteFromJson(json);

  Map<String, dynamic> toJson() => _$FixtureSideWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
