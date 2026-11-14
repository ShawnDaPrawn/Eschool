// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_result_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicResultWrite _$AcademicResultWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'AcademicResultWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'state',
            'revision',
            'host_score',
            'guest_score',
            'winner',
            'entrants',
          ],
        );
        final val = AcademicResultWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          state: $checkedConvert(
            'state',
            (v) => $enumDecode(
              _$AcademicResultWriteStateEnumEnumMap,
              v,
              unknownValue: AcademicResultWriteStateEnum.unknownDefaultOpenApi,
            ),
          ),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          hostScore: $checkedConvert('host_score', (v) => (v as num).toInt()),
          guestScore: $checkedConvert('guest_score', (v) => (v as num).toInt()),
          winner: $checkedConvert(
            'winner',
            (v) => $enumDecode(
              _$AcademicResultWriteWinnerEnumEnumMap,
              v,
              unknownValue: AcademicResultWriteWinnerEnum.unknownDefaultOpenApi,
            ),
          ),
          entrants: $checkedConvert(
            'entrants',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      AcademicEntrantResult.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
          reasons: $checkedConvert('reasons', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'hostScore': 'host_score',
        'guestScore': 'guest_score',
      },
    );

Map<String, dynamic> _$AcademicResultWriteToJson(
  AcademicResultWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'state': _$AcademicResultWriteStateEnumEnumMap[instance.state]!,
  'revision': instance.revision,
  'host_score': instance.hostScore,
  'guest_score': instance.guestScore,
  'winner': _$AcademicResultWriteWinnerEnumEnumMap[instance.winner]!,
  'entrants': instance.entrants.map((e) => e.toJson()).toList(),
  'reasons': ?instance.reasons,
};

const _$AcademicResultWriteStateEnumEnumMap = {
  AcademicResultWriteStateEnum.draft: 'draft',
  AcademicResultWriteStateEnum.final_: 'final',
  AcademicResultWriteStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$AcademicResultWriteWinnerEnumEnumMap = {
  AcademicResultWriteWinnerEnum.host: 'host',
  AcademicResultWriteWinnerEnum.guest: 'guest',
  AcademicResultWriteWinnerEnum.draw: 'draw',
  AcademicResultWriteWinnerEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
