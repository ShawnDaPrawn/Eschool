//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/discipline_infraction.dart';
import 'package:eschool_api/src/model/discipline_sanction.dart';
import 'package:eschool_api/src/model/discipline_level.dart';
import 'package:json_annotation/json_annotation.dart';

part 'discipline_framework_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineFrameworkWrite {
  /// Returns a new [DisciplineFrameworkWrite] instance.
  DisciplineFrameworkWrite({
    required this.operationId,

    required this.sessionYearId,

    required this.expectedRevision,

    required this.title,

    required this.counterScope,

    required this.reset,

    this.rollingDays,

    required this.levels,

    required this.sanctions,

    required this.infractions,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 0
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(
    name: r'counter_scope',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        DisciplineFrameworkWriteCounterScopeEnum.unknownDefaultOpenApi,
  )
  final DisciplineFrameworkWriteCounterScopeEnum counterScope;

  @JsonKey(
    name: r'reset',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DisciplineFrameworkWriteResetEnum.unknownDefaultOpenApi,
  )
  final DisciplineFrameworkWriteResetEnum reset;

  // minimum: 1
  // maximum: 365
  @JsonKey(name: r'rolling_days', required: false, includeIfNull: false)
  final int? rollingDays;

  @JsonKey(name: r'levels', required: true, includeIfNull: false)
  final List<DisciplineLevel> levels;

  @JsonKey(name: r'sanctions', required: true, includeIfNull: false)
  final List<DisciplineSanction> sanctions;

  @JsonKey(name: r'infractions', required: true, includeIfNull: false)
  final List<DisciplineInfraction> infractions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineFrameworkWrite &&
          other.operationId == operationId &&
          other.sessionYearId == sessionYearId &&
          other.expectedRevision == expectedRevision &&
          other.title == title &&
          other.counterScope == counterScope &&
          other.reset == reset &&
          other.rollingDays == rollingDays &&
          other.levels == levels &&
          other.sanctions == sanctions &&
          other.infractions == infractions;

  @override
  int get hashCode =>
      operationId.hashCode +
      sessionYearId.hashCode +
      expectedRevision.hashCode +
      title.hashCode +
      counterScope.hashCode +
      reset.hashCode +
      (rollingDays == null ? 0 : rollingDays.hashCode) +
      levels.hashCode +
      sanctions.hashCode +
      infractions.hashCode;

  factory DisciplineFrameworkWrite.fromJson(Map<String, dynamic> json) =>
      _$DisciplineFrameworkWriteFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineFrameworkWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DisciplineFrameworkWriteCounterScopeEnum {
  @JsonValue(r'same_infraction')
  sameInfraction(r'same_infraction'),
  @JsonValue(r'all_infractions')
  allInfractions(r'all_infractions'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineFrameworkWriteCounterScopeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum DisciplineFrameworkWriteResetEnum {
  @JsonValue(r'school_year')
  schoolYear(r'school_year'),
  @JsonValue(r'term')
  term(r'term'),
  @JsonValue(r'rolling_days')
  rollingDays(r'rolling_days'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineFrameworkWriteResetEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
