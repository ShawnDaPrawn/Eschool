//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_level.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineLevel {
  /// Returns a new [DisciplineLevel] instance.
  DisciplineLevel({
    required this.code,

    required this.name,

    required this.threshold,

    required this.kind,

    required this.sanctions,
  });

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final String code;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  // maximum: 1000
  @JsonKey(name: r'threshold', required: true, includeIfNull: false)
  final int threshold;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DisciplineLevelKindEnum.unknownDefaultOpenApi,
  )
  final DisciplineLevelKindEnum kind;

  @JsonKey(name: r'sanctions', required: true, includeIfNull: false)
  final List<String> sanctions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineLevel &&
          other.code == code &&
          other.name == name &&
          other.threshold == threshold &&
          other.kind == kind &&
          other.sanctions == sanctions;

  @override
  int get hashCode =>
      code.hashCode +
      name.hashCode +
      threshold.hashCode +
      kind.hashCode +
      sanctions.hashCode;

  factory DisciplineLevel.fromJson(Map<String, dynamic> json) =>
      _$DisciplineLevelFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineLevelToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DisciplineLevelKindEnum {
  @JsonValue(r'sanction')
  sanction(r'sanction'),
  @JsonValue(r'hearing')
  hearing(r'hearing'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineLevelKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
