//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_sanction.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineSanction {
  /// Returns a new [DisciplineSanction] instance.
  DisciplineSanction({
    required this.code,

    required this.name,

    required this.kind,
  });

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final String code;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DisciplineSanctionKindEnum.unknownDefaultOpenApi,
  )
  final DisciplineSanctionKindEnum kind;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineSanction &&
          other.code == code &&
          other.name == name &&
          other.kind == kind;

  @override
  int get hashCode => code.hashCode + name.hashCode + kind.hashCode;

  factory DisciplineSanction.fromJson(Map<String, dynamic> json) =>
      _$DisciplineSanctionFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineSanctionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DisciplineSanctionKindEnum {
  @JsonValue(r'notice')
  notice(r'notice'),
  @JsonValue(r'detention')
  detention(r'detention'),
  @JsonValue(r'restorative')
  restorative(r'restorative'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineSanctionKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
