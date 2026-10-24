//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_register_settings_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRegisterSettingsData {
  /// Returns a new [AcademicRegisterSettingsData] instance.
  AcademicRegisterSettingsData({
    required this.absenceCutoff,

    required this.teachingDays,
  });

  @JsonKey(name: r'absence_cutoff', required: true, includeIfNull: false)
  final String absenceCutoff;

  @JsonKey(name: r'teaching_days', required: true, includeIfNull: false)
  final List<int> teachingDays;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRegisterSettingsData &&
          other.absenceCutoff == absenceCutoff &&
          other.teachingDays == teachingDays;

  @override
  int get hashCode => absenceCutoff.hashCode + teachingDays.hashCode;

  factory AcademicRegisterSettingsData.fromJson(Map<String, dynamic> json) =>
      _$AcademicRegisterSettingsDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicRegisterSettingsDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
