//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_scope.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationScope {
  /// Returns a new [CommunicationScope] instance.
  CommunicationScope({
    required this.kind,

    required this.targetId,

    required this.name,
  });

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CommunicationScopeKindEnum.unknownDefaultOpenApi,
  )
  final CommunicationScopeKindEnum kind;

  // minimum: 1
  @JsonKey(name: r'target_id', required: true, includeIfNull: true)
  final int? targetId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationScope &&
          other.kind == kind &&
          other.targetId == targetId &&
          other.name == name;

  @override
  int get hashCode =>
      kind.hashCode +
      (targetId == null ? 0 : targetId.hashCode) +
      name.hashCode;

  factory CommunicationScope.fromJson(Map<String, dynamic> json) =>
      _$CommunicationScopeFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationScopeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommunicationScopeKindEnum {
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'grade')
  grade(r'grade'),
  @JsonValue(r'homeroom')
  homeroom(r'homeroom'),
  @JsonValue(r'group')
  group(r'group'),
  @JsonValue(r'custom')
  custom(r'custom'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommunicationScopeKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
