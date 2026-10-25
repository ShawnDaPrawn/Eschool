//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_audience_selection.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationAudienceSelection {
  /// Returns a new [CommunicationAudienceSelection] instance.
  CommunicationAudienceSelection({
    required this.kind,

    required this.sessionYearId,

    this.targetId,

    required this.parents,
  });

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        CommunicationAudienceSelectionKindEnum.unknownDefaultOpenApi,
  )
  final CommunicationAudienceSelectionKindEnum kind;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'target_id', required: false, includeIfNull: false)
  final int? targetId;

  @JsonKey(name: r'parents', required: true, includeIfNull: false)
  final bool parents;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationAudienceSelection &&
          other.kind == kind &&
          other.sessionYearId == sessionYearId &&
          other.targetId == targetId &&
          other.parents == parents;

  @override
  int get hashCode =>
      kind.hashCode +
      sessionYearId.hashCode +
      (targetId == null ? 0 : targetId.hashCode) +
      parents.hashCode;

  factory CommunicationAudienceSelection.fromJson(Map<String, dynamic> json) =>
      _$CommunicationAudienceSelectionFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationAudienceSelectionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommunicationAudienceSelectionKindEnum {
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

  const CommunicationAudienceSelectionKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
