//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_detention_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineDetentionWrite {
  /// Returns a new [DisciplineDetentionWrite] instance.
  DisciplineDetentionWrite({
    required this.operationId,

    required this.sessionYearId,

    required this.gradeLevelId,

    required this.supervisorId,

    required this.onDate,

    required this.startsAt,

    required this.endsAt,

    required this.location,

    required this.capacity,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  // minimum: 1
  @JsonKey(name: r'supervisor_id', required: true, includeIfNull: false)
  final int supervisorId;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final String startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final String endsAt;

  @JsonKey(name: r'location', required: true, includeIfNull: false)
  final String location;

  // minimum: 1
  // maximum: 200
  @JsonKey(name: r'capacity', required: true, includeIfNull: false)
  final int capacity;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineDetentionWrite &&
          other.operationId == operationId &&
          other.sessionYearId == sessionYearId &&
          other.gradeLevelId == gradeLevelId &&
          other.supervisorId == supervisorId &&
          other.onDate == onDate &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.location == location &&
          other.capacity == capacity;

  @override
  int get hashCode =>
      operationId.hashCode +
      sessionYearId.hashCode +
      gradeLevelId.hashCode +
      supervisorId.hashCode +
      onDate.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      location.hashCode +
      capacity.hashCode;

  factory DisciplineDetentionWrite.fromJson(Map<String, dynamic> json) =>
      _$DisciplineDetentionWriteFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineDetentionWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
