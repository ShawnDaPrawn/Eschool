//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'communication_audience_snapshot.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationAudienceSnapshot {
  /// Returns a new [CommunicationAudienceSnapshot] instance.
  CommunicationAudienceSnapshot({
    required this.kind,

    required this.sessionYearId,

    required this.targetId,

    required this.parents,

    this.userIds,

    this.name,

    this.audienceRevision,
  });

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        CommunicationAudienceSnapshotKindEnum.unknownDefaultOpenApi,
  )
  final CommunicationAudienceSnapshotKindEnum kind;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'target_id', required: true, includeIfNull: true)
  final int? targetId;

  @JsonKey(name: r'parents', required: true, includeIfNull: false)
  final bool parents;

  @JsonKey(name: r'user_ids', required: false, includeIfNull: false)
  final Set<int>? userIds;

  @JsonKey(name: r'name', required: false, includeIfNull: false)
  final String? name;

  // minimum: 1
  @JsonKey(name: r'audience_revision', required: false, includeIfNull: false)
  final int? audienceRevision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationAudienceSnapshot &&
          other.kind == kind &&
          other.sessionYearId == sessionYearId &&
          other.targetId == targetId &&
          other.parents == parents &&
          other.userIds == userIds &&
          other.name == name &&
          other.audienceRevision == audienceRevision;

  @override
  int get hashCode =>
      kind.hashCode +
      sessionYearId.hashCode +
      (targetId == null ? 0 : targetId.hashCode) +
      parents.hashCode +
      userIds.hashCode +
      name.hashCode +
      audienceRevision.hashCode;

  factory CommunicationAudienceSnapshot.fromJson(Map<String, dynamic> json) =>
      _$CommunicationAudienceSnapshotFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationAudienceSnapshotToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommunicationAudienceSnapshotKindEnum {
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

  const CommunicationAudienceSnapshotKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
