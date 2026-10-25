//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/consultation_configuration_snapshot.dart';
import 'package:json_annotation/json_annotation.dart';

part 'consultation_history_record.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationHistoryRecord {
  /// Returns a new [ConsultationHistoryRecord] instance.
  ConsultationHistoryRecord({
    required this.id,

    required this.action,

    required this.revision,

    required this.actorId,

    required this.actorName,

    required this.createdAt,

    required this.snapshot,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'action', required: true, includeIfNull: false)
  final String action;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'actor_id', required: true, includeIfNull: true)
  final int? actorId;

  @JsonKey(name: r'actor_name', required: true, includeIfNull: true)
  final String? actorName;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'snapshot', required: true, includeIfNull: false)
  final ConsultationConfigurationSnapshot snapshot;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationHistoryRecord &&
          other.id == id &&
          other.action == action &&
          other.revision == revision &&
          other.actorId == actorId &&
          other.actorName == actorName &&
          other.createdAt == createdAt &&
          other.snapshot == snapshot;

  @override
  int get hashCode =>
      id.hashCode +
      action.hashCode +
      revision.hashCode +
      (actorId == null ? 0 : actorId.hashCode) +
      (actorName == null ? 0 : actorName.hashCode) +
      createdAt.hashCode +
      snapshot.hashCode;

  factory ConsultationHistoryRecord.fromJson(Map<String, dynamic> json) =>
      _$ConsultationHistoryRecordFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationHistoryRecordToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
