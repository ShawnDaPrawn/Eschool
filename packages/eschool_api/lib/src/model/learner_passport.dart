//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learner_passport_export_availability.dart';
import 'package:eschool_api/src/model/learner_passport_school.dart';
import 'package:eschool_api/src/model/learner_passport_transfer.dart';
import 'package:eschool_api/src/model/learner_passport_learner.dart';
import 'package:eschool_api/src/model/learner_passport_reading.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learner_passport.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerPassport {
  /// Returns a new [LearnerPassport] instance.
  LearnerPassport({
    required this.learner,

    required this.viewer,

    required this.scope,

    required this.schools,

    required this.transfers,

    required this.reading,

    required this.export_,
  });

  @JsonKey(name: r'learner', required: true, includeIfNull: false)
  final LearnerPassportLearner learner;

  @JsonKey(
    name: r'viewer',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LearnerPassportViewerEnum.unknownDefaultOpenApi,
  )
  final LearnerPassportViewerEnum viewer;

  @JsonKey(
    name: r'scope',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LearnerPassportScopeEnum.unknownDefaultOpenApi,
  )
  final LearnerPassportScopeEnum scope;

  @JsonKey(name: r'schools', required: true, includeIfNull: false)
  final List<LearnerPassportSchool> schools;

  @JsonKey(name: r'transfers', required: true, includeIfNull: false)
  final List<LearnerPassportTransfer> transfers;

  @JsonKey(name: r'reading', required: true, includeIfNull: false)
  final LearnerPassportReading reading;

  @JsonKey(name: r'export', required: true, includeIfNull: true)
  final LearnerPassportExportAvailability? export_;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerPassport &&
          other.learner == learner &&
          other.viewer == viewer &&
          other.scope == scope &&
          other.schools == schools &&
          other.transfers == transfers &&
          other.reading == reading &&
          other.export_ == export_;

  @override
  int get hashCode =>
      learner.hashCode +
      viewer.hashCode +
      scope.hashCode +
      schools.hashCode +
      transfers.hashCode +
      reading.hashCode +
      (export_ == null ? 0 : export_.hashCode);

  factory LearnerPassport.fromJson(Map<String, dynamic> json) =>
      _$LearnerPassportFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerPassportToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearnerPassportViewerEnum {
  @JsonValue(r'learner')
  learner(r'learner'),
  @JsonValue(r'guardian')
  guardian(r'guardian'),
  @JsonValue(r'staff')
  staff(r'staff'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearnerPassportViewerEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum LearnerPassportScopeEnum {
  @JsonValue(r'all_schools')
  allSchools(r'all_schools'),
  @JsonValue(r'this_school')
  thisSchool(r'this_school'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearnerPassportScopeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
