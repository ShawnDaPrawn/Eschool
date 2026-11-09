// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'tutor_turn_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TutorTurnWrite _$TutorTurnWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'TutorTurnWrite',
      json,
      ($checkedConvert) {
        $checkKeys(json, requiredKeys: const ['operation_id', 'question']);
        final val = TutorTurnWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          question: $checkedConvert('question', (v) => v as String),
          working: $checkedConvert('working', (v) => v as String?),
          photoIds: $checkedConvert(
            'photo_ids',
            (v) => (v as List<dynamic>?)?.map((e) => e as String).toSet(),
          ),
          turnType: $checkedConvert(
            'turn_type',
            (v) => $enumDecodeNullable(
              _$TutorTurnWriteTurnTypeEnumEnumMap,
              v,
              unknownValue: TutorTurnWriteTurnTypeEnum.unknownDefaultOpenApi,
            ),
          ),
          language: $checkedConvert(
            'language',
            (v) => $enumDecodeNullable(
              _$TutorTurnWriteLanguageEnumEnumMap,
              v,
              unknownValue: TutorTurnWriteLanguageEnum.unknownDefaultOpenApi,
            ),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'photoIds': 'photo_ids',
        'turnType': 'turn_type',
      },
    );

Map<String, dynamic> _$TutorTurnWriteToJson(TutorTurnWrite instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'question': instance.question,
      'working': ?instance.working,
      'photo_ids': ?instance.photoIds?.toList(),
      'turn_type': ?_$TutorTurnWriteTurnTypeEnumEnumMap[instance.turnType],
      'language': ?_$TutorTurnWriteLanguageEnumEnumMap[instance.language],
    };

const _$TutorTurnWriteTurnTypeEnumEnumMap = {
  TutorTurnWriteTurnTypeEnum.ask: 'ask',
  TutorTurnWriteTurnTypeEnum.explainBack: 'explain_back',
  TutorTurnWriteTurnTypeEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$TutorTurnWriteLanguageEnumEnumMap = {
  TutorTurnWriteLanguageEnum.af: 'af',
  TutorTurnWriteLanguageEnum.zu: 'zu',
  TutorTurnWriteLanguageEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
