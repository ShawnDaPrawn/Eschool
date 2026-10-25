//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'consultation_audience_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationAudienceInput {
  /// Returns a new [ConsultationAudienceInput] instance.
  ConsultationAudienceInput({
    required this.kind,

    this.targetId,

    this.parents,

    this.includeStaff,
  });

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ConsultationAudienceInputKindEnum.unknownDefaultOpenApi,
  )
  final ConsultationAudienceInputKindEnum kind;

  // minimum: 1
  @JsonKey(name: r'target_id', required: false, includeIfNull: false)
  final int? targetId;

  @JsonKey(name: r'parents', required: false, includeIfNull: false)
  final bool? parents;

  @JsonKey(name: r'include_staff', required: false, includeIfNull: false)
  final bool? includeStaff;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationAudienceInput &&
          other.kind == kind &&
          other.targetId == targetId &&
          other.parents == parents &&
          other.includeStaff == includeStaff;

  @override
  int get hashCode =>
      kind.hashCode +
      (targetId == null ? 0 : targetId.hashCode) +
      parents.hashCode +
      includeStaff.hashCode;

  factory ConsultationAudienceInput.fromJson(Map<String, dynamic> json) =>
      _$ConsultationAudienceInputFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationAudienceInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ConsultationAudienceInputKindEnum {
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'grade')
  grade(r'grade'),
  @JsonValue(r'homeroom')
  homeroom(r'homeroom'),
  @JsonValue(r'group')
  group(r'group'),
  @JsonValue(r'activity_group')
  activityGroup(r'activity_group'),
  @JsonValue(r'custom')
  custom(r'custom'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ConsultationAudienceInputKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
