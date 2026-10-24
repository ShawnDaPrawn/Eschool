// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_learner_preview.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicLearnerPreview _$AcademicLearnerPreviewFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicLearnerPreview', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['learner_id', 'result']);
  final val = AcademicLearnerPreview(
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    result: $checkedConvert(
      'result',
      (v) => AcademicMarkResult.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id'});

Map<String, dynamic> _$AcademicLearnerPreviewToJson(
  AcademicLearnerPreview instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'result': instance.result.toJson(),
};
