//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'cover_source.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CoverSource {
  /// Returns a new [CoverSource] instance.
  CoverSource({
    required this.lessonId,

    required this.lessonRevision,

    required this.sessionYearId,

    required this.teacherId,

    required this.teacherName,

    required this.groupId,

    required this.homeroomId,

    required this.title,

    required this.subjectName,

    required this.roomId,

    required this.roomName,

    required this.periodId,

    required this.startsAt,

    required this.endsAt,

    required this.onDate,
  });

  // minimum: 1
  @JsonKey(name: r'lesson_id', required: true, includeIfNull: false)
  final int lessonId;

  // minimum: 1
  @JsonKey(name: r'lesson_revision', required: true, includeIfNull: false)
  final int lessonRevision;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'teacher_id', required: true, includeIfNull: false)
  final int teacherId;

  @JsonKey(name: r'teacher_name', required: true, includeIfNull: false)
  final String teacherName;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: true)
  final int? groupId;

  // minimum: 1
  @JsonKey(name: r'homeroom_id', required: true, includeIfNull: true)
  final int? homeroomId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: true)
  final String? subjectName;

  // minimum: 1
  @JsonKey(name: r'room_id', required: true, includeIfNull: false)
  final int roomId;

  @JsonKey(name: r'room_name', required: true, includeIfNull: false)
  final String roomName;

  // minimum: 1
  @JsonKey(name: r'period_id', required: true, includeIfNull: false)
  final int periodId;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final String startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final String endsAt;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CoverSource &&
          other.lessonId == lessonId &&
          other.lessonRevision == lessonRevision &&
          other.sessionYearId == sessionYearId &&
          other.teacherId == teacherId &&
          other.teacherName == teacherName &&
          other.groupId == groupId &&
          other.homeroomId == homeroomId &&
          other.title == title &&
          other.subjectName == subjectName &&
          other.roomId == roomId &&
          other.roomName == roomName &&
          other.periodId == periodId &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.onDate == onDate;

  @override
  int get hashCode =>
      lessonId.hashCode +
      lessonRevision.hashCode +
      sessionYearId.hashCode +
      teacherId.hashCode +
      teacherName.hashCode +
      (groupId == null ? 0 : groupId.hashCode) +
      (homeroomId == null ? 0 : homeroomId.hashCode) +
      title.hashCode +
      (subjectName == null ? 0 : subjectName.hashCode) +
      roomId.hashCode +
      roomName.hashCode +
      periodId.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      onDate.hashCode;

  factory CoverSource.fromJson(Map<String, dynamic> json) =>
      _$CoverSourceFromJson(json);

  Map<String, dynamic> toJson() => _$CoverSourceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
