//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_case_link_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingCaseLinkInput {
  /// Returns a new [WellbeingCaseLinkInput] instance.
  WellbeingCaseLinkInput({
    required this.operationId,

    required this.revision,

    required this.incidentReportId,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'incident_report_id', required: true, includeIfNull: false)
  final int incidentReportId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingCaseLinkInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.incidentReportId == incidentReportId;

  @override
  int get hashCode =>
      operationId.hashCode + revision.hashCode + incidentReportId.hashCode;

  factory WellbeingCaseLinkInput.fromJson(Map<String, dynamic> json) =>
      _$WellbeingCaseLinkInputFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingCaseLinkInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
