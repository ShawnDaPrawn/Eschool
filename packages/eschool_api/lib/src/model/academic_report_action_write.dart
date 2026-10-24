//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_report_action_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicReportActionWrite {
  /// Returns a new [AcademicReportActionWrite] instance.
  AcademicReportActionWrite({
    required this.revision,

    required this.fingerprint,
  });

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicReportActionWrite &&
          other.revision == revision &&
          other.fingerprint == fingerprint;

  @override
  int get hashCode => revision.hashCode + fingerprint.hashCode;

  factory AcademicReportActionWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicReportActionWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicReportActionWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
