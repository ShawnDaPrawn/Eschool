//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'subject_standard.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SubjectStandard {
  /// Returns a new [SubjectStandard] instance.
  SubjectStandard({
    required this.id,

    required this.framework,

    required this.code,

    required this.name,

    required this.phase,

    required this.gradeFrom,

    required this.gradeTo,

    required this.version,

    required this.sourceUrl,

    required this.effectiveOn,

    required this.componentWeights,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'framework',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SubjectStandardFrameworkEnum.unknownDefaultOpenApi,
  )
  final SubjectStandardFrameworkEnum framework;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final String code;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(
    name: r'phase',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SubjectStandardPhaseEnum.unknownDefaultOpenApi,
  )
  final SubjectStandardPhaseEnum phase;

  // minimum: 0
  // maximum: 12
  @JsonKey(name: r'grade_from', required: true, includeIfNull: false)
  final int gradeFrom;

  // minimum: 0
  // maximum: 12
  @JsonKey(name: r'grade_to', required: true, includeIfNull: false)
  final int gradeTo;

  @JsonKey(name: r'version', required: true, includeIfNull: false)
  final String version;

  @JsonKey(name: r'source_url', required: true, includeIfNull: false)
  final String sourceUrl;

  @JsonKey(name: r'effective_on', required: true, includeIfNull: false)
  final DateTime effectiveOn;

  @JsonKey(name: r'component_weights', required: true, includeIfNull: true)
  final Map<String, int>? componentWeights;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SubjectStandard &&
          other.id == id &&
          other.framework == framework &&
          other.code == code &&
          other.name == name &&
          other.phase == phase &&
          other.gradeFrom == gradeFrom &&
          other.gradeTo == gradeTo &&
          other.version == version &&
          other.sourceUrl == sourceUrl &&
          other.effectiveOn == effectiveOn &&
          other.componentWeights == componentWeights;

  @override
  int get hashCode =>
      id.hashCode +
      framework.hashCode +
      code.hashCode +
      name.hashCode +
      phase.hashCode +
      gradeFrom.hashCode +
      gradeTo.hashCode +
      version.hashCode +
      sourceUrl.hashCode +
      effectiveOn.hashCode +
      (componentWeights == null ? 0 : componentWeights.hashCode);

  factory SubjectStandard.fromJson(Map<String, dynamic> json) =>
      _$SubjectStandardFromJson(json);

  Map<String, dynamic> toJson() => _$SubjectStandardToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SubjectStandardFrameworkEnum {
  @JsonValue(r'CAPS')
  CAPS(r'CAPS'),
  @JsonValue(r'IEB')
  IEB(r'IEB'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SubjectStandardFrameworkEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum SubjectStandardPhaseEnum {
  @JsonValue(r'Foundation')
  foundation(r'Foundation'),
  @JsonValue(r'Intermediate')
  intermediate(r'Intermediate'),
  @JsonValue(r'Senior')
  senior(r'Senior'),
  @JsonValue(r'FET')
  FET(r'FET'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SubjectStandardPhaseEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
