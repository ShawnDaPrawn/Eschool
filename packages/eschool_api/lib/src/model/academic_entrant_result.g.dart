// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_entrant_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicEntrantResult _$AcademicEntrantResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicEntrantResult', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['learner_id', 'score', 'place']);
  final val = AcademicEntrantResult(
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    score: $checkedConvert('score', (v) => (v as num?)?.toInt()),
    place: $checkedConvert('place', (v) => (v as num?)?.toInt()),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id'});

Map<String, dynamic> _$AcademicEntrantResultToJson(
  AcademicEntrantResult instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'score': instance.score,
  'place': instance.place,
};
