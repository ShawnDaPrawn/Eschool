//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/pathway_elective_result.dart';
import 'package:json_annotation/json_annotation.dart';

part 'pathway_selection_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PathwaySelectionResult {
  /// Returns a new [PathwaySelectionResult] instance.
  PathwaySelectionResult({
    required this.valid,

    required this.selectedSubjectIds,

    required this.missingCompulsorySubjectIds,

    required this.groups,
  });

  @JsonKey(name: r'valid', required: true, includeIfNull: false)
  final bool valid;

  @JsonKey(name: r'selected_subject_ids', required: true, includeIfNull: false)
  final List<int> selectedSubjectIds;

  @JsonKey(
    name: r'missing_compulsory_subject_ids',
    required: true,
    includeIfNull: false,
  )
  final List<int> missingCompulsorySubjectIds;

  @JsonKey(name: r'groups', required: true, includeIfNull: false)
  final List<PathwayElectiveResult> groups;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PathwaySelectionResult &&
          other.valid == valid &&
          other.selectedSubjectIds == selectedSubjectIds &&
          other.missingCompulsorySubjectIds == missingCompulsorySubjectIds &&
          other.groups == groups;

  @override
  int get hashCode =>
      valid.hashCode +
      selectedSubjectIds.hashCode +
      missingCompulsorySubjectIds.hashCode +
      groups.hashCode;

  factory PathwaySelectionResult.fromJson(Map<String, dynamic> json) =>
      _$PathwaySelectionResultFromJson(json);

  Map<String, dynamic> toJson() => _$PathwaySelectionResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
