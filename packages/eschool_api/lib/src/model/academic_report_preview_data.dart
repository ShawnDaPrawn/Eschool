//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_report_selection.dart';
import 'package:eschool_api/src/model/academic_report_design_snapshot.dart';
import 'package:eschool_api/src/model/academic_report_payload.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_preview_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportPreviewData {
  /// Returns a new [AcademicReportPreviewData] instance.
  AcademicReportPreviewData({
    required this.selection,

    required this.fingerprint,

    required this.canPrepare,

    required this.design,

    required this.cards,
  });

  @JsonKey(name: r'selection', required: true, includeIfNull: false)
  final AcademicReportSelection selection;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @JsonKey(name: r'can_prepare', required: true, includeIfNull: false)
  final bool canPrepare;

  @JsonKey(name: r'design', required: true, includeIfNull: false)
  final AcademicReportDesignSnapshot design;

  @JsonKey(name: r'cards', required: true, includeIfNull: false)
  final List<AcademicReportPayload> cards;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportPreviewData &&
          other.selection == selection &&
          other.fingerprint == fingerprint &&
          other.canPrepare == canPrepare &&
          other.design == design &&
          other.cards == cards;

  @override
  int get hashCode =>
      selection.hashCode +
      fingerprint.hashCode +
      canPrepare.hashCode +
      design.hashCode +
      cards.hashCode;

  factory AcademicReportPreviewData.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportPreviewDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportPreviewDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
