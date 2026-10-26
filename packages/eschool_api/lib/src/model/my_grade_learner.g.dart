// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'my_grade_learner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MyGradeLearner _$MyGradeLearnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'MyGradeLearner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'learner_id',
        'name',
        'class_section_id',
        'register_id',
        'attendance_state',
        'signals',
      ],
    );
    final val = MyGradeLearner(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      classSectionId: $checkedConvert(
        'class_section_id',
        (v) => (v as num).toInt(),
      ),
      registerId: $checkedConvert('register_id', (v) => (v as num?)?.toInt()),
      attendanceState: $checkedConvert(
        'attendance_state',
        (v) => $enumDecode(
          _$MyGradeLearnerAttendanceStateEnumEnumMap,
          v,
          unknownValue: MyGradeLearnerAttendanceStateEnum.unknownDefaultOpenApi,
        ),
      ),
      signals: $checkedConvert(
        'signals',
        (v) => (v as List<dynamic>)
            .map((e) => MyGradeSignal.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'classSectionId': 'class_section_id',
    'registerId': 'register_id',
    'attendanceState': 'attendance_state',
  },
);

Map<String, dynamic> _$MyGradeLearnerToJson(MyGradeLearner instance) =>
    <String, dynamic>{
      'learner_id': instance.learnerId,
      'name': instance.name,
      'class_section_id': instance.classSectionId,
      'register_id': instance.registerId,
      'attendance_state':
          _$MyGradeLearnerAttendanceStateEnumEnumMap[instance.attendanceState]!,
      'signals': instance.signals.map((e) => e.toJson()).toList(),
    };

const _$MyGradeLearnerAttendanceStateEnumEnumMap = {
  MyGradeLearnerAttendanceStateEnum.present: 'present',
  MyGradeLearnerAttendanceStateEnum.absent: 'absent',
  MyGradeLearnerAttendanceStateEnum.late_: 'late',
  MyGradeLearnerAttendanceStateEnum.excused: 'excused',
  MyGradeLearnerAttendanceStateEnum.firstHalf: 'first_half',
  MyGradeLearnerAttendanceStateEnum.secondHalf: 'second_half',
  MyGradeLearnerAttendanceStateEnum.holiday: 'holiday',
  MyGradeLearnerAttendanceStateEnum.unrecorded: 'unrecorded',
  MyGradeLearnerAttendanceStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
