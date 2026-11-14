// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_competition_details.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicCompetitionDetails _$AcademicCompetitionDetailsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicCompetitionDetails',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'revision', 'motion'],
    );
    final val = AcademicCompetitionDetails(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      motion: $checkedConvert('motion', (v) => v as String),
      format: $checkedConvert('format', (v) => v as String?),
      rounds: $checkedConvert('rounds', (v) => (v as num?)?.toInt()),
      hostStance: $checkedConvert(
        'host_stance',
        (v) => $enumDecodeNullable(
          _$AcademicCompetitionDetailsHostStanceEnumEnumMap,
          v,
          unknownValue:
              AcademicCompetitionDetailsHostStanceEnum.unknownDefaultOpenApi,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'hostStance': 'host_stance',
  },
);

Map<String, dynamic> _$AcademicCompetitionDetailsToJson(
  AcademicCompetitionDetails instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'motion': instance.motion,
  'format': ?instance.format,
  'rounds': ?instance.rounds,
  'host_stance':
      ?_$AcademicCompetitionDetailsHostStanceEnumEnumMap[instance.hostStance],
};

const _$AcademicCompetitionDetailsHostStanceEnumEnumMap = {
  AcademicCompetitionDetailsHostStanceEnum.proposition: 'proposition',
  AcademicCompetitionDetailsHostStanceEnum.opposition: 'opposition',
  AcademicCompetitionDetailsHostStanceEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
