//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_passport_school.dart';
import 'package:eschool_api/src/model/learner_passport_transfer.dart';
import 'package:eschool_api/src/model/learner_passport_learner.dart';
import 'package:eschool_api/src/model/learner_passport_reading.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learner_passport_export_passport.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerPassportExportPassport {
  /// Returns a new [LearnerPassportExportPassport] instance.
  LearnerPassportExportPassport({
    required this.learner,

    required this.scope,

    required this.schools,

    required this.transfers,

    required this.reading,
  });

  @JsonKey(name: r'learner', required: true, includeIfNull: false)
  final LearnerPassportLearner learner;

  @JsonKey(
    name: r'scope',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        LearnerPassportExportPassportScopeEnum.unknownDefaultOpenApi,
  )
  final LearnerPassportExportPassportScopeEnum scope;

  @JsonKey(name: r'schools', required: true, includeIfNull: false)
  final List<LearnerPassportSchool> schools;

  @JsonKey(name: r'transfers', required: true, includeIfNull: false)
  final List<LearnerPassportTransfer> transfers;

  @JsonKey(name: r'reading', required: true, includeIfNull: false)
  final LearnerPassportReading reading;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerPassportExportPassport &&
          other.learner == learner &&
          other.scope == scope &&
          other.schools == schools &&
          other.transfers == transfers &&
          other.reading == reading;

  @override
  int get hashCode =>
      learner.hashCode +
      scope.hashCode +
      schools.hashCode +
      transfers.hashCode +
      reading.hashCode;

  factory LearnerPassportExportPassport.fromJson(Map<String, dynamic> json) =>
      _$LearnerPassportExportPassportFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerPassportExportPassportToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearnerPassportExportPassportScopeEnum {
  @JsonValue(r'all_schools')
  allSchools(r'all_schools'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearnerPassportExportPassportScopeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
