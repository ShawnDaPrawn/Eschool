//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_passport_export_passport.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learner_passport_export.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerPassportExport {
  /// Returns a new [LearnerPassportExport] instance.
  LearnerPassportExport({
    required this.format,

    required this.generatedAt,

    required this.passport,
  });

  @JsonKey(
    name: r'format',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LearnerPassportExportFormatEnum.unknownDefaultOpenApi,
  )
  final LearnerPassportExportFormatEnum format;

  @JsonKey(name: r'generated_at', required: true, includeIfNull: false)
  final DateTime generatedAt;

  @JsonKey(name: r'passport', required: true, includeIfNull: false)
  final LearnerPassportExportPassport passport;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerPassportExport &&
          other.format == format &&
          other.generatedAt == generatedAt &&
          other.passport == passport;

  @override
  int get hashCode =>
      format.hashCode + generatedAt.hashCode + passport.hashCode;

  factory LearnerPassportExport.fromJson(Map<String, dynamic> json) =>
      _$LearnerPassportExportFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerPassportExportToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearnerPassportExportFormatEnum {
  @JsonValue(r'navigateed-learner-passport-v1')
  navigateedLearnerPassportV1(r'navigateed-learner-passport-v1'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearnerPassportExportFormatEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
