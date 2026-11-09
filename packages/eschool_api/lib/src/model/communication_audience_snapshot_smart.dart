//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/communication_smart_rules_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'communication_audience_snapshot_smart.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationAudienceSnapshotSmart {
  /// Returns a new [CommunicationAudienceSnapshotSmart] instance.
  CommunicationAudienceSnapshotSmart({
    required this.sessionYearId,

    required this.match,

    required this.parents,

    required this.rules,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(
    name: r'match',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        CommunicationAudienceSnapshotSmartMatchEnum.unknownDefaultOpenApi,
  )
  final CommunicationAudienceSnapshotSmartMatchEnum match;

  @JsonKey(name: r'parents', required: true, includeIfNull: false)
  final bool parents;

  /// Each rule is an audience scope the author may address. Required for a smart group, refused for a fixed list.
  @JsonKey(name: r'rules', required: true, includeIfNull: false)
  final List<CommunicationSmartRulesInner> rules;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationAudienceSnapshotSmart &&
          other.sessionYearId == sessionYearId &&
          other.match == match &&
          other.parents == parents &&
          other.rules == rules;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      match.hashCode +
      parents.hashCode +
      rules.hashCode;

  factory CommunicationAudienceSnapshotSmart.fromJson(
    Map<String, dynamic> json,
  ) => _$CommunicationAudienceSnapshotSmartFromJson(json);

  Map<String, dynamic> toJson() =>
      _$CommunicationAudienceSnapshotSmartToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommunicationAudienceSnapshotSmartMatchEnum {
  @JsonValue(r'any')
  any(r'any'),
  @JsonValue(r'all')
  all(r'all'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommunicationAudienceSnapshotSmartMatchEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
