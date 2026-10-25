//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'tutor_session_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorSessionResult {
  /// Returns a new [TutorSessionResult] instance.
  TutorSessionResult({
    required this.schoolId,

    required this.readerId,

    required this.id,

    required this.learnerId,

    required this.subjectId,

    required this.groupId,

    required this.sessionYearId,

    required this.state,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TutorSessionResultStateEnum.unknownDefaultOpenApi,
  )
  final TutorSessionResultStateEnum state;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorSessionResult &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.subjectId == subjectId &&
          other.groupId == groupId &&
          other.sessionYearId == sessionYearId &&
          other.state == state;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      id.hashCode +
      learnerId.hashCode +
      subjectId.hashCode +
      groupId.hashCode +
      sessionYearId.hashCode +
      state.hashCode;

  factory TutorSessionResult.fromJson(Map<String, dynamic> json) =>
      _$TutorSessionResultFromJson(json);

  Map<String, dynamic> toJson() => _$TutorSessionResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TutorSessionResultStateEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TutorSessionResultStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
