//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_infraction.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineInfraction {
  /// Returns a new [DisciplineInfraction] instance.
  DisciplineInfraction({
    required this.code,

    required this.name,

    required this.baseLevel,
  });

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final String code;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'base_level', required: true, includeIfNull: false)
  final String baseLevel;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineInfraction &&
          other.code == code &&
          other.name == name &&
          other.baseLevel == baseLevel;

  @override
  int get hashCode => code.hashCode + name.hashCode + baseLevel.hashCode;

  factory DisciplineInfraction.fromJson(Map<String, dynamic> json) =>
      _$DisciplineInfractionFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineInfractionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
