//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_read_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportReadData {
  /// Returns a new [AcademicReportReadData] instance.
  AcademicReportReadData({required this.id, required this.readAt});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'read_at', required: true, includeIfNull: false)
  final String readAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportReadData &&
          other.id == id &&
          other.readAt == readAt;

  @override
  int get hashCode => id.hashCode + readAt.hashCode;

  factory AcademicReportReadData.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportReadDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportReadDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
