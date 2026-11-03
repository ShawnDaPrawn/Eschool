//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CommunicationAudience &&
          other.id == id &&
          other.name == name &&
          other.revision == revision &&
          other.active == active &&
          other.editable == editable &&
          other.userIds == userIds;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      revision.hashCode +
      active.hashCode +
      editable.hashCode +
      userIds.hashCode;

  factory CommunicationAudience.fromJson(Map<String, dynamic> json) =>
      _$CommunicationAudienceFromJson(json);

  Map<String, dynamic> toJson() => _$CommunicationAudienceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
