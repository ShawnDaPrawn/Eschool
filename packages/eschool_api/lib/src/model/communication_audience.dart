//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/communication_audience_smart.dart';
import 'package:json_annotation/json_annotation.dart';

part 'communication_audience.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CommunicationAudience {
  /// Returns a new [CommunicationAudience] instance.
  CommunicationAudience({
    required this.id,

    required this.name,

    required this.revision,

    required this.active,

    required this.editable,

    required this.userIds,

    required this.kind,

    required this.shared,

    required this.smart,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @JsonKey(name: r'editable', required: true, includeIfNull: false)
  final bool editable;

  @JsonKey(name: r'user_ids', required: true, includeIfNull: false)
  final Set<int> userIds;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CommunicationAudienceKindEnum.unknownDefaultOpenApi,
  )
  final CommunicationAudienceKindEnum kind;

  /// Shared lists are offered to other staff who may address everyone on them (every rule, for a smart group). Private ones only to their creator and communication managers.
  @JsonKey(name: r'shared', required: true, includeIfNull: false)
  final bool shared;

  @JsonKey(name: r'smart', required: true, includeIfNull: true)
  final CommunicationAudienceSmart? smart;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationAudience &&
          other.id == id &&
          other.name == name &&
          other.revision == revision &&
          other.active == active &&
          other.editable == editable &&
          other.userIds == userIds &&
          other.kind == kind &&
          other.shared == shared &&
          other.smart == smart;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      revision.hashCode +
      active.hashCode +
      editable.hashCode +
      userIds.hashCode +
      kind.hashCode +
      shared.hashCode +
      (smart == null ? 0 : smart.hashCode);

  factory CommunicationAudience.fromJson(Map<String, dynamic> json) =>
      _$CommunicationAudienceFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationAudienceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CommunicationAudienceKindEnum {
  @JsonValue(r'fixed')
  fixed(r'fixed'),
  @JsonValue(r'smart')
  smart(r'smart'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CommunicationAudienceKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
