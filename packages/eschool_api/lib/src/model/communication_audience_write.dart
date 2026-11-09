//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/communication_smart_rules_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'communication_audience_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationAudienceWrite {
  /// Returns a new [CommunicationAudienceWrite] instance.
  CommunicationAudienceWrite({
    required this.operationId,

    required this.revision,

    required this.sessionYearId,

    required this.name,

    required this.active,

    this.userIds,

    this.kind = CommunicationAudienceWriteKindEnum.fixed,

    this.shared = true,

    this.match,

    this.parents,

    this.rules,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @JsonKey(name: r'user_ids', required: false, includeIfNull: false)
  final Set<int>? userIds;

  /// Fixed for a new audience unless smart. An existing audience keeps its kind.
  @JsonKey(
    defaultValue: CommunicationAudienceWriteKindEnum.fixed,
    name: r'kind',
    required: false,
    includeIfNull: false,
    unknownEnumValue: CommunicationAudienceWriteKindEnum.unknownDefaultOpenApi,
  )
  final CommunicationAudienceWriteKindEnum? kind;

  @JsonKey(
    defaultValue: true,
    name: r'shared',
    required: false,
    includeIfNull: false,
  )
  final bool? shared;

  /// Smart groups only. Learners in any rule, or only those in all of them.
  @JsonKey(
    name: r'match',
    required: false,
    includeIfNull: false,
    unknownEnumValue: CommunicationAudienceWriteMatchEnum.unknownDefaultOpenApi,
  )
  final CommunicationAudienceWriteMatchEnum? match;

  /// Smart groups only. The learners, or their parents and guardians.
  @JsonKey(name: r'parents', required: false, includeIfNull: false)
  final bool? parents;

  /// Each rule is an audience scope the author may address. Required for a smart group, refused for a fixed list.
  @JsonKey(name: r'rules', required: false, includeIfNull: false)
  final List<CommunicationSmartRulesInner>? rules;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationAudienceWrite &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.sessionYearId == sessionYearId &&
          other.name == name &&
          other.active == active &&
          other.userIds == userIds &&
          other.kind == kind &&
          other.shared == shared &&
          other.match == match &&
          other.parents == parents &&
          other.rules == rules;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      sessionYearId.hashCode +
      name.hashCode +
      active.hashCode +
      userIds.hashCode +
      kind.hashCode +
      shared.hashCode +
      match.hashCode +
      parents.hashCode +
      rules.hashCode;

  factory CommunicationAudienceWrite.fromJson(Map<String, dynamic> json) =>
      _$CommunicationAudienceWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationAudienceWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

/// Fixed for a new audience unless smart. An existing audience keeps its kind.
enum CommunicationAudienceWriteKindEnum {
  @JsonValue(r'fixed')
  fixed(r'fixed'),
  @JsonValue(r'smart')
  smart(r'smart'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommunicationAudienceWriteKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

/// Smart groups only. Learners in any rule, or only those in all of them.
enum CommunicationAudienceWriteMatchEnum {
  @JsonValue(r'any')
  any(r'any'),
  @JsonValue(r'all')
  all(r'all'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommunicationAudienceWriteMatchEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
