//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'risk_owner_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskOwnerChoice {
  /// Returns a new [RiskOwnerChoice] instance.
  RiskOwnerChoice({
    required this.id,

    required this.name,

    required this.authority,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(
    name: r'authority',
    required: true,
    includeIfNull: false,
    unknownEnumValue: RiskOwnerChoiceAuthorityEnum.unknownDefaultOpenApi,
  )
  final RiskOwnerChoiceAuthorityEnum authority;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskOwnerChoice &&
          other.id == id &&
          other.name == name &&
          other.authority == authority;

  @override
  int get hashCode => id.hashCode + name.hashCode + authority.hashCode;

  factory RiskOwnerChoice.fromJson(Map<String, dynamic> json) =>
      _$RiskOwnerChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$RiskOwnerChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum RiskOwnerChoiceAuthorityEnum {
  @JsonValue(r'professional')
  professional(r'professional'),
  @JsonValue(r'academic')
  academic(r'academic'),
  @JsonValue(r'leadership')
  leadership(r'leadership'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const RiskOwnerChoiceAuthorityEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
