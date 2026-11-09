//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/communication_smart_rules_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'communication_audience_smart.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationAudienceSmart {
  /// Returns a new [CommunicationAudienceSmart] instance.
  CommunicationAudienceSmart({
    required this.match,

    required this.parents,

    required this.rules,
  });

  @JsonKey(
    name: r'match',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CommunicationAudienceSmartMatchEnum.unknownDefaultOpenApi,
  )
  final CommunicationAudienceSmartMatchEnum match;

  @JsonKey(name: r'parents', required: true, includeIfNull: false)
  final bool parents;

  /// Each rule is an audience scope the author may address. Required for a smart group, refused for a fixed list.
  @JsonKey(name: r'rules', required: true, includeIfNull: false)
  final List<CommunicationSmartRulesInner> rules;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationAudienceSmart &&
          other.match == match &&
          other.parents == parents &&
          other.rules == rules;

  @override
  int get hashCode => match.hashCode + parents.hashCode + rules.hashCode;

  factory CommunicationAudienceSmart.fromJson(Map<String, dynamic> json) =>
      _$CommunicationAudienceSmartFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationAudienceSmartToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommunicationAudienceSmartMatchEnum {
  @JsonValue(r'any')
  any(r'any'),
  @JsonValue(r'all')
  all(r'all'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommunicationAudienceSmartMatchEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
