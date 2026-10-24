// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_release_periods_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReleasePeriodsData _$AcademicReleasePeriodsDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReleasePeriodsData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['learner_id', 'periods']);
  final val = AcademicReleasePeriodsData(
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    periods: $checkedConvert(
      'periods',
      (v) => (v as List<dynamic>)
          .map((e) => AcademicReleasePeriod.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id'});

Map<String, dynamic> _$AcademicReleasePeriodsDataToJson(
  AcademicReleasePeriodsData instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'periods': instance.periods.map((e) => e.toJson()).toList(),
};
