//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/intervention_version_record.dart';
import 'package:json_annotation/json_annotation.dart';

part 'intervention_version.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class InterventionVersion {
  /// Returns a new [InterventionVersion] instance.
  InterventionVersion({
    required this.revision,

    required this.createdAt,

    required this.actorId,

    required this.record,
  });

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  // minimum: 1
  @JsonKey(name: r'actor_id', required: true, includeIfNull: false)
  final int actorId;

  @JsonKey(name: r'record', required: true, includeIfNull: false)
  final InterventionVersionRecord record;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is InterventionVersion &&
          other.revision == revision &&
          other.createdAt == createdAt &&
          other.actorId == actorId &&
          other.record == record;

  @override
  int get hashCode =>
      revision.hashCode +
      createdAt.hashCode +
      actorId.hashCode +
      record.hashCode;

  factory InterventionVersion.fromJson(Map<String, dynamic> json) =>
      _$InterventionVersionFromJson(json);

  Map<String, dynamic> toJson() => _$InterventionVersionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
