//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/my_grade_signal.dart';
import 'package:json_annotation/json_annotation.dart';

part 'my_grade_learner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MyGradeLearner {
  /// Returns a new [MyGradeLearner] instance.
  MyGradeLearner({
    required this.learnerId,

    required this.name,

    required this.classSectionId,

    required this.registerId,

    required this.attendanceState,

    required this.signals,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 1
  @JsonKey(name: r'class_section_id', required: true, includeIfNull: false)
  final int classSectionId;

  // minimum: 1
  @JsonKey(name: r'register_id', required: true, includeIfNull: true)
  final int? registerId;

  @JsonKey(
    name: r'attendance_state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: MyGradeLearnerAttendanceStateEnum.unknownDefaultOpenApi,
  )
  final MyGradeLearnerAttendanceStateEnum attendanceState;

  @JsonKey(name: r'signals', required: true, includeIfNull: false)
  final List<MyGradeSignal> signals;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MyGradeLearner &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.classSectionId == classSectionId &&
          other.registerId == registerId &&
          other.attendanceState == attendanceState &&
          other.signals == signals;

  @override
  int get hashCode =>
      learnerId.hashCode +
      name.hashCode +
      classSectionId.hashCode +
      (registerId == null ? 0 : registerId.hashCode) +
      attendanceState.hashCode +
      signals.hashCode;

  factory MyGradeLearner.fromJson(Map<String, dynamic> json) =>
      _$MyGradeLearnerFromJson(json);

  Map<String, dynamic> toJson() => _$MyGradeLearnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum MyGradeLearnerAttendanceStateEnum {
  @JsonValue(r'present')
  present(r'present'),
  @JsonValue(r'absent')
  absent(r'absent'),
  @JsonValue(r'late')
  late_(r'late'),
  @JsonValue(r'excused')
  excused(r'excused'),
  @JsonValue(r'first_half')
  firstHalf(r'first_half'),
  @JsonValue(r'second_half')
  secondHalf(r'second_half'),
  @JsonValue(r'holiday')
  holiday(r'holiday'),
  @JsonValue(r'unrecorded')
  unrecorded(r'unrecorded'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const MyGradeLearnerAttendanceStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
