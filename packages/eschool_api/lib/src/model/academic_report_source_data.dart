//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_report_payload.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_source_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportSourceData {
  /// Returns a new [AcademicReportSourceData] instance.
  AcademicReportSourceData({
    required this.canPrepare,

    required this.cards,

    required this.fingerprint,

    required this.gradeLevelId,
  });

  @JsonKey(name: r'can_prepare', required: true, includeIfNull: false)
  final bool canPrepare;

  @JsonKey(name: r'cards', required: true, includeIfNull: false)
  final List<AcademicReportPayload> cards;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportSourceData &&
          other.canPrepare == canPrepare &&
          other.cards == cards &&
          other.fingerprint == fingerprint &&
          other.gradeLevelId == gradeLevelId;

  @override
  int get hashCode =>
      canPrepare.hashCode +
      cards.hashCode +
      fingerprint.hashCode +
      gradeLevelId.hashCode;

  factory AcademicReportSourceData.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportSourceDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportSourceDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
