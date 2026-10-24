// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_attendance_today_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicAttendanceTodayData _$AcademicAttendanceTodayDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicAttendanceTodayData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['learner_id', 'on_date', 'entries']);
  final val = AcademicAttendanceTodayData(
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    onDate: $checkedConvert('on_date', (v) => v as String),
    entries: $checkedConvert(
      'entries',
      (v) => (v as List<dynamic>)
          .map(
            (e) => AcademicAttendanceTodayEntry.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id', 'onDate': 'on_date'});

Map<String, dynamic> _$AcademicAttendanceTodayDataToJson(
  AcademicAttendanceTodayData instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'on_date': instance.onDate,
  'entries': instance.entries.map((e) => e.toJson()).toList(),
};
