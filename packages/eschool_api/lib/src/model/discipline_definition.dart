//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/discipline_infraction.dart';
import 'package:eschool_api/src/model/discipline_sanction.dart';
import 'package:eschool_api/src/model/discipline_level.dart';
import 'package:json_annotation/json_annotation.dart';

part 'discipline_definition.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineDefinition {
  /// Returns a new [DisciplineDefinition] instance.
  DisciplineDefinition({
    required this.counterScope,

    required this.reset,

    this.rollingDays,

    required this.levels,

    required this.sanctions,

    required this.infractions,
  });

  @JsonKey(
    name: r'counter_scope',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        DisciplineDefinitionCounterScopeEnum.unknownDefaultOpenApi,
  )
  final DisciplineDefinitionCounterScopeEnum counterScope;

  @JsonKey(
    name: r'reset',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DisciplineDefinitionResetEnum.unknownDefaultOpenApi,
  )
  final DisciplineDefinitionResetEnum reset;

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
      other is DisciplineDefinition &&
          other.counterScope == counterScope &&
          other.reset == reset &&
          other.rollingDays == rollingDays &&
          other.levels == levels &&
          other.sanctions == sanctions &&
          other.infractions == infractions;

  @override
  int get hashCode =>
      counterScope.hashCode +
      reset.hashCode +
      (rollingDays == null ? 0 : rollingDays.hashCode) +
      levels.hashCode +
      sanctions.hashCode +
      infractions.hashCode;

  factory DisciplineDefinition.fromJson(Map<String, dynamic> json) =>
      _$DisciplineDefinitionFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineDefinitionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DisciplineDefinitionCounterScopeEnum {
  @JsonValue(r'same_infraction')
  sameInfraction(r'same_infraction'),
  @JsonValue(r'all_infractions')
  allInfractions(r'all_infractions'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineDefinitionCounterScopeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum DisciplineDefinitionResetEnum {
  @JsonValue(r'school_year')
  schoolYear(r'school_year'),
  @JsonValue(r'term')
  term(r'term'),
  @JsonValue(r'rolling_days')
  rollingDays(r'rolling_days'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineDefinitionResetEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
