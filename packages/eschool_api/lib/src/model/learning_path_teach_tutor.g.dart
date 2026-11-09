// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_teach_tutor.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathTeachTutor _$LearningPathTeachTutorFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('LearningPathTeachTutor', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['available', 'session_id', 'reason']);
  final val = LearningPathTeachTutor(
    available: $checkedConvert('available', (v) => v as bool),
    sessionId: $checkedConvert('session_id', (v) => v as String?),
    reason: $checkedConvert(
      'reason',
      (v) => $enumDecodeNullable(
        _$LearningPathTeachTutorReasonEnumEnumMap,
        v,
        unknownValue: LearningPathTeachTutorReasonEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
}, fieldKeyMap: const {'sessionId': 'session_id'});

Map<String, dynamic> _$LearningPathTeachTutorToJson(
  LearningPathTeachTutor instance,
) => <String, dynamic>{
  'available': instance.available,
  'session_id': instance.sessionId,
  'reason': _$LearningPathTeachTutorReasonEnumEnumMap[instance.reason],
};

const _$LearningPathTeachTutorReasonEnumEnumMap = {
  LearningPathTeachTutorReasonEnum.notReady: 'not_ready',
  LearningPathTeachTutorReasonEnum.notAvailable: 'not_available',
  LearningPathTeachTutorReasonEnum.noSchoolMaterial: 'no_school_material',
  LearningPathTeachTutorReasonEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
