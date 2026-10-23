//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'class_grade_mapping_grade_level.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ClassGradeMappingGradeLevel {
  /// Returns a new [ClassGradeMappingGradeLevel] instance.
  ClassGradeMappingGradeLevel({
    required this.id,

    required this.code,

    required this.name,

    required this.phase,

    required this.sortOrder,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final String code;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(
    name: r'phase',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        ClassGradeMappingGradeLevelPhaseEnum.unknownDefaultOpenApi,
  )
  final ClassGradeMappingGradeLevelPhaseEnum phase;

  // minimum: 0
  // maximum: 12
  @JsonKey(name: r'sort_order', required: true, includeIfNull: false)
  final int sortOrder;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ClassGradeMappingGradeLevel &&
          other.id == id &&
          other.code == code &&
          other.name == name &&
          other.phase == phase &&
          other.sortOrder == sortOrder;

  @override
  int get hashCode =>
      id.hashCode +
      code.hashCode +
      name.hashCode +
      phase.hashCode +
      sortOrder.hashCode;

  factory ClassGradeMappingGradeLevel.fromJson(Map<String, dynamic> json) =>
      _$ClassGradeMappingGradeLevelFromJson(json);

  Map<String, dynamic> toJson() => _$ClassGradeMappingGradeLevelToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ClassGradeMappingGradeLevelPhaseEnum {
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

  const ClassGradeMappingGradeLevelPhaseEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
