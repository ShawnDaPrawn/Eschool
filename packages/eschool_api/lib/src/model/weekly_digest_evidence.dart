//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/weekly_digest_mark.dart';
import 'package:eschool_api/src/model/weekly_digest_practice.dart';
import 'package:eschool_api/src/model/weekly_digest_attendance.dart';
import 'package:eschool_api/src/model/weekly_digest_event.dart';
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_evidence.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestEvidence {
  /// Returns a new [WeeklyDigestEvidence] instance.
  WeeklyDigestEvidence({
    required this.marks,

    required this.attendance,

    required this.practice,

    required this.events,

    required this.eventCount,

    required this.actionKind,

    required this.contentVersionId,
  });

  @JsonKey(name: r'marks', required: true, includeIfNull: false)
  final List<WeeklyDigestMark> marks;

  @JsonKey(name: r'attendance', required: true, includeIfNull: false)
  final WeeklyDigestAttendance attendance;

  @JsonKey(name: r'practice', required: true, includeIfNull: false)
  final WeeklyDigestPractice practice;

  @JsonKey(name: r'events', required: true, includeIfNull: false)
  final List<WeeklyDigestEvent> events;

  // minimum: 0
  @JsonKey(name: r'event_count', required: true, includeIfNull: false)
  final int eventCount;

  @JsonKey(
    name: r'action_kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WeeklyDigestEvidenceActionKindEnum.unknownDefaultOpenApi,
  )
  final WeeklyDigestEvidenceActionKindEnum actionKind;

  // minimum: 1
  @JsonKey(name: r'content_version_id', required: true, includeIfNull: true)
  final int? contentVersionId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestEvidence &&
          other.marks == marks &&
          other.attendance == attendance &&
          other.practice == practice &&
          other.events == events &&
          other.eventCount == eventCount &&
          other.actionKind == actionKind &&
          other.contentVersionId == contentVersionId;

  @override
  int get hashCode =>
      marks.hashCode +
      attendance.hashCode +
      practice.hashCode +
      events.hashCode +
      eventCount.hashCode +
      actionKind.hashCode +
      (contentVersionId == null ? 0 : contentVersionId.hashCode);

  factory WeeklyDigestEvidence.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestEvidenceFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestEvidenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WeeklyDigestEvidenceActionKindEnum {
  @JsonValue(r'practice')
  practice(r'practice'),
  @JsonValue(r'marks')
  marks(r'marks'),
  @JsonValue(r'attendance')
  attendance(r'attendance'),
  @JsonValue(r'calendar')
  calendar(r'calendar'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WeeklyDigestEvidenceActionKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
