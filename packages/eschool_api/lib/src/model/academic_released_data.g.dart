// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_released_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReleasedData _$AcademicReleasedDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReleasedData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['learner_id', 'results']);
  final val = AcademicReleasedData(
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    results: $checkedConvert(
      'results',
      (v) => (v as List<dynamic>)
          .map(
            (e) => AcademicReleasedResult.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id'});

Map<String, dynamic> _$AcademicReleasedDataToJson(
  AcademicReleasedData instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'results': instance.results.map((e) => e.toJson()).toList(),
};
