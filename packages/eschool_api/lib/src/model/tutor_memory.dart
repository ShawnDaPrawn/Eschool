//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/tutor_memory_subject.dart';
import 'package:json_annotation/json_annotation.dart';

part 'tutor_memory.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorMemory {
  /// Returns a new [TutorMemory] instance.
  TutorMemory({
    required this.schoolId,

    required this.readerId,

    required this.learnerId,

    required this.retentionDays,

    required this.clearedAt,

    required this.note,

    required this.subjects,

    required this.containsRawChat,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  // maximum: 365
  @JsonKey(name: r'retention_days', required: true, includeIfNull: false)
  final int retentionDays;

  @JsonKey(name: r'cleared_at', required: true, includeIfNull: true)
  final DateTime? clearedAt;

  @JsonKey(name: r'note', required: true, includeIfNull: false)
  final String note;

  @JsonKey(name: r'subjects', required: true, includeIfNull: false)
  final List<TutorMemorySubject> subjects;

  @JsonKey(name: r'contains_raw_chat', required: true, includeIfNull: false)
  final bool containsRawChat;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorMemory &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.learnerId == learnerId &&
          other.retentionDays == retentionDays &&
          other.clearedAt == clearedAt &&
          other.note == note &&
          other.subjects == subjects &&
          other.containsRawChat == containsRawChat;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      learnerId.hashCode +
      retentionDays.hashCode +
      (clearedAt == null ? 0 : clearedAt.hashCode) +
      note.hashCode +
      subjects.hashCode +
      containsRawChat.hashCode;

  factory TutorMemory.fromJson(Map<String, dynamic> json) =>
      _$TutorMemoryFromJson(json);

  Map<String, dynamic> toJson() => _$TutorMemoryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
