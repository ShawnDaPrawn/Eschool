//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_exit_selection.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicExitSelection {
  /// Returns a new [AcademicExitSelection] instance.
  AcademicExitSelection({
    required this.transitionId,

    required this.reportIds,

    required this.documentIds,
  });

  // minimum: 1
  @JsonKey(name: r'transition_id', required: true, includeIfNull: false)
  final int transitionId;

  @JsonKey(name: r'report_ids', required: true, includeIfNull: false)
  final Set<int> reportIds;

  @JsonKey(name: r'document_ids', required: true, includeIfNull: false)
  final Set<String> documentIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicExitSelection &&
          other.transitionId == transitionId &&
          other.reportIds == reportIds &&
          other.documentIds == documentIds;

  @override
  int get hashCode =>
      transitionId.hashCode + reportIds.hashCode + documentIds.hashCode;

  factory AcademicExitSelection.fromJson(Map<String, dynamic> json) =>
      _$AcademicExitSelectionFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicExitSelectionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
