//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'consultation_history_entry.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationHistoryEntry {
  /// Returns a new [ConsultationHistoryEntry] instance.
  ConsultationHistoryEntry({
    required this.id,

    required this.action,

    required this.revision,

    required this.actorId,

    required this.actorName,

    required this.createdAt,
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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationHistoryEntry &&
          other.id == id &&
          other.action == action &&
          other.revision == revision &&
          other.actorId == actorId &&
          other.actorName == actorName &&
          other.createdAt == createdAt;

  @override
  int get hashCode =>
      id.hashCode +
      action.hashCode +
      revision.hashCode +
      (actorId == null ? 0 : actorId.hashCode) +
      (actorName == null ? 0 : actorName.hashCode) +
      createdAt.hashCode;

  factory ConsultationHistoryEntry.fromJson(Map<String, dynamic> json) =>
      _$ConsultationHistoryEntryFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationHistoryEntryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
