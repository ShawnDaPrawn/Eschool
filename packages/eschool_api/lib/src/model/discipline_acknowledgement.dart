//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_acknowledgement.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineAcknowledgement {
  /// Returns a new [DisciplineAcknowledgement] instance.
  DisciplineAcknowledgement({
    required this.recordId,

    required this.revision,

    required this.acknowledgedAt,
  });

  // minimum: 1
  @JsonKey(name: r'record_id', required: true, includeIfNull: false)
  final int recordId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'acknowledged_at', required: true, includeIfNull: false)
  final DateTime acknowledgedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineAcknowledgement &&
          other.recordId == recordId &&
          other.revision == revision &&
          other.acknowledgedAt == acknowledgedAt;

  @override
  int get hashCode =>
      recordId.hashCode + revision.hashCode + acknowledgedAt.hashCode;

  factory DisciplineAcknowledgement.fromJson(Map<String, dynamic> json) =>
      _$DisciplineAcknowledgementFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineAcknowledgementToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
