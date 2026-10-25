//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'discipline_detention_entry.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DisciplineDetentionEntry {
  /// Returns a new [DisciplineDetentionEntry] instance.
  DisciplineDetentionEntry({
    required this.id,

    required this.recordId,

    required this.learnerId,

    required this.name,

    required this.attendance,

    required this.status,

    required this.sourceValid,

    required this.reason,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'record_id', required: true, includeIfNull: false)
  final int recordId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(
    name: r'attendance',
    required: true,
    includeIfNull: true,
    unknownEnumValue:
        DisciplineDetentionEntryAttendanceEnum.unknownDefaultOpenApi,
  )
  final DisciplineDetentionEntryAttendanceEnum? attendance;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DisciplineDetentionEntryStatusEnum.unknownDefaultOpenApi,
  )
  final DisciplineDetentionEntryStatusEnum status;

  @JsonKey(name: r'source_valid', required: true, includeIfNull: false)
  final bool sourceValid;

  @JsonKey(name: r'reason', required: true, includeIfNull: true)
  final String? reason;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DisciplineDetentionEntry &&
          other.id == id &&
          other.recordId == recordId &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.attendance == attendance &&
          other.status == status &&
          other.sourceValid == sourceValid &&
          other.reason == reason &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      recordId.hashCode +
      learnerId.hashCode +
      name.hashCode +
      (attendance == null ? 0 : attendance.hashCode) +
      status.hashCode +
      sourceValid.hashCode +
      (reason == null ? 0 : reason.hashCode) +
      revision.hashCode;

  factory DisciplineDetentionEntry.fromJson(Map<String, dynamic> json) =>
      _$DisciplineDetentionEntryFromJson(json);

  Map<String, dynamic> toJson() => _$DisciplineDetentionEntryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DisciplineDetentionEntryAttendanceEnum {
  @JsonValue(r'present')
  present(r'present'),
  @JsonValue(r'absent')
  absent(r'absent'),
  @JsonValue(r'excused')
  excused(r'excused'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineDetentionEntryAttendanceEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum DisciplineDetentionEntryStatusEnum {
  @JsonValue(r'active')
  active(r'active'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DisciplineDetentionEntryStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
