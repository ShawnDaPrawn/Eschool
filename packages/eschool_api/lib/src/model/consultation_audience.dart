//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'consultation_audience.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationAudience {
  /// Returns a new [ConsultationAudience] instance.
  ConsultationAudience({
    required this.kind,

    required this.targetId,

    required this.sessionYearId,

    required this.parents,

    required this.includeStaff,

    this.userIds,
  });

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ConsultationAudienceKindEnum.unknownDefaultOpenApi,
  )
  final ConsultationAudienceKindEnum kind;

  // minimum: 1
  @JsonKey(name: r'target_id', required: true, includeIfNull: true)
  final int? targetId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'parents', required: true, includeIfNull: false)
  final bool parents;

  @JsonKey(name: r'include_staff', required: true, includeIfNull: false)
  final bool includeStaff;

  @JsonKey(name: r'user_ids', required: false, includeIfNull: false)
  final List<int>? userIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationAudience &&
          other.kind == kind &&
          other.targetId == targetId &&
          other.sessionYearId == sessionYearId &&
          other.parents == parents &&
          other.includeStaff == includeStaff &&
          other.userIds == userIds;

  @override
  int get hashCode =>
      kind.hashCode +
      (targetId == null ? 0 : targetId.hashCode) +
      sessionYearId.hashCode +
      parents.hashCode +
      includeStaff.hashCode +
      userIds.hashCode;

  factory ConsultationAudience.fromJson(Map<String, dynamic> json) =>
      _$ConsultationAudienceFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationAudienceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ConsultationAudienceKindEnum {
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

  const ConsultationAudienceKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
