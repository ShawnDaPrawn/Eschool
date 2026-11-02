//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_smart_rules_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationSmartRulesInner {
  /// Returns a new [CommunicationSmartRulesInner] instance.
  CommunicationSmartRulesInner({required this.kind, required this.targetId});

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        CommunicationSmartRulesInnerKindEnum.unknownDefaultOpenApi,
  )
  final CommunicationSmartRulesInnerKindEnum kind;

  // minimum: 1
  @JsonKey(name: r'target_id', required: true, includeIfNull: false)
  final int targetId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationSmartRulesInner &&
          other.kind == kind &&
          other.targetId == targetId;

  @override
  int get hashCode => kind.hashCode + targetId.hashCode;

  factory CommunicationSmartRulesInner.fromJson(Map<String, dynamic> json) =>
      _$CommunicationSmartRulesInnerFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationSmartRulesInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommunicationSmartRulesInnerKindEnum {
  @JsonValue(r'grade')
  grade(r'grade'),
  @JsonValue(r'homeroom')
  homeroom(r'homeroom'),
  @JsonValue(r'group')
  group(r'group'),
  @JsonValue(r'activity_group')
  activityGroup(r'activity_group'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommunicationSmartRulesInnerKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
