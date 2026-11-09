//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'strengths_skill.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsSkill {
  /// Returns a new [StrengthsSkill] instance.
  StrengthsSkill({required this.kind, required this.label});

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: StrengthsSkillKindEnum.unknownDefaultOpenApi,
  )
  final StrengthsSkillKindEnum kind;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsSkill && other.kind == kind && other.label == label;

  @override
  int get hashCode => kind.hashCode + label.hashCode;

  factory StrengthsSkill.fromJson(Map<String, dynamic> json) =>
      _$StrengthsSkillFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsSkillToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum StrengthsSkillKindEnum {
  @JsonValue(r'leading')
  leading(r'leading'),
  @JsonValue(r'helping')
  helping(r'helping'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const StrengthsSkillKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
