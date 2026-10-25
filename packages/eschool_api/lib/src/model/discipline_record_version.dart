//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/discipline_version_record.dart';
import 'package:json_annotation/json_annotation.dart';

part 'discipline_record_version.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineRecordVersion {
  /// Returns a new [DisciplineRecordVersion] instance.
  DisciplineRecordVersion({
    required this.revision,

    required this.actorId,

    required this.createdAt,

    required this.record,
  });

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'actor_id', required: true, includeIfNull: false)
  final int actorId;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'record', required: true, includeIfNull: false)
  final DisciplineVersionRecord record;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineRecordVersion &&
          other.revision == revision &&
          other.actorId == actorId &&
          other.createdAt == createdAt &&
          other.record == record;

  @override
  int get hashCode =>
      revision.hashCode +
      actorId.hashCode +
      createdAt.hashCode +
      record.hashCode;

  factory DisciplineRecordVersion.fromJson(Map<String, dynamic> json) =>
      _$DisciplineRecordVersionFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineRecordVersionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
