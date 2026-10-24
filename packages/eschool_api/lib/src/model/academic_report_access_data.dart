//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_access_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportAccessData {
  /// Returns a new [AcademicReportAccessData] instance.
  AcademicReportAccessData({
    required this.id,

    required this.learnerId,

    required this.downloadUrl,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'download_url', required: true, includeIfNull: false)
  final String downloadUrl;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportAccessData &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.downloadUrl == downloadUrl;

  @override
  int get hashCode => id.hashCode + learnerId.hashCode + downloadUrl.hashCode;

  factory AcademicReportAccessData.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportAccessDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportAccessDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
