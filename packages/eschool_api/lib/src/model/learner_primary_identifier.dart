//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_primary_identifier.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerPrimaryIdentifier {
  /// Returns a new [LearnerPrimaryIdentifier] instance.
  LearnerPrimaryIdentifier({required this.type, required this.value});

  @JsonKey(
    name: r'type',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LearnerPrimaryIdentifierTypeEnum.unknownDefaultOpenApi,
  )
  final LearnerPrimaryIdentifierTypeEnum type;

  @JsonKey(name: r'value', required: true, includeIfNull: false)
  final String value;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerPrimaryIdentifier &&
          other.type == type &&
          other.value == value;

  @override
  int get hashCode => type.hashCode + value.hashCode;

  factory LearnerPrimaryIdentifier.fromJson(Map<String, dynamic> json) =>
      _$LearnerPrimaryIdentifierFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerPrimaryIdentifierToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearnerPrimaryIdentifierTypeEnum {
  @JsonValue(r'lurits')
  lurits(r'lurits'),
  @JsonValue(r'navigateed')
  navigateed(r'navigateed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearnerPrimaryIdentifierTypeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
