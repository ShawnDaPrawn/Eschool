// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_competition_create.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicCompetitionCreate _$AcademicCompetitionCreateFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicCompetitionCreate',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'kind',
        'motion',
        'activity_group_id',
        'team_label',
        'discipline',
        'on_date',
        'venue',
        'host_is_home',
      ],
    );
    final val = AcademicCompetitionCreate(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$AcademicCompetitionCreateKindEnumEnumMap,
          v,
          unknownValue: AcademicCompetitionCreateKindEnum.unknownDefaultOpenApi,
        ),
      ),
      motion: $checkedConvert('motion', (v) => v as String),
      format: $checkedConvert('format', (v) => v as String?),
      rounds: $checkedConvert('rounds', (v) => (v as num?)?.toInt()),
      hostStance: $checkedConvert(
        'host_stance',
        (v) => $enumDecodeNullable(
          _$AcademicCompetitionCreateHostStanceEnumEnumMap,
          v,
          unknownValue:
              AcademicCompetitionCreateHostStanceEnum.unknownDefaultOpenApi,
        ),
      ),
      activityGroupId: $checkedConvert(
        'activity_group_id',
        (v) => (v as num).toInt(),
      ),
      teamLabel: $checkedConvert('team_label', (v) => v as String),
      discipline: $checkedConvert('discipline', (v) => v as String),
      sharedCompetitionId: $checkedConvert(
        'shared_competition_id',
        (v) => (v as num?)?.toInt(),
      ),
      guestSchoolId: $checkedConvert(
        'guest_school_id',
        (v) => (v as num?)?.toInt(),
      ),
      externalOpponent: $checkedConvert(
        'external_opponent',
        (v) => v as String?,
      ),
      onDate: $checkedConvert('on_date', (v) => v as String),
      startsAt: $checkedConvert('starts_at', (v) => v as String?),
      venue: $checkedConvert('venue', (v) => v as String),
      field: $checkedConvert('field', (v) => v as String?),
      hostIsHome: $checkedConvert('host_is_home', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'hostStance': 'host_stance',
    'activityGroupId': 'activity_group_id',
    'teamLabel': 'team_label',
    'sharedCompetitionId': 'shared_competition_id',
    'guestSchoolId': 'guest_school_id',
    'externalOpponent': 'external_opponent',
    'onDate': 'on_date',
    'startsAt': 'starts_at',
    'hostIsHome': 'host_is_home',
  },
);

Map<String, dynamic> _$AcademicCompetitionCreateToJson(
  AcademicCompetitionCreate instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'kind': _$AcademicCompetitionCreateKindEnumEnumMap[instance.kind]!,
  'motion': instance.motion,
  'format': ?instance.format,
  'rounds': ?instance.rounds,
  'host_stance':
      ?_$AcademicCompetitionCreateHostStanceEnumEnumMap[instance.hostStance],
  'activity_group_id': instance.activityGroupId,
  'team_label': instance.teamLabel,
  'discipline': instance.discipline,
  'shared_competition_id': ?instance.sharedCompetitionId,
  'guest_school_id': ?instance.guestSchoolId,
  'external_opponent': ?instance.externalOpponent,
  'on_date': instance.onDate,
  'starts_at': ?instance.startsAt,
  'venue': instance.venue,
  'field': ?instance.field,
  'host_is_home': instance.hostIsHome,
};

const _$AcademicCompetitionCreateKindEnumEnumMap = {
  AcademicCompetitionCreateKindEnum.debate: 'debate',
  AcademicCompetitionCreateKindEnum.quiz: 'quiz',
  AcademicCompetitionCreateKindEnum.speech: 'speech',
  AcademicCompetitionCreateKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$AcademicCompetitionCreateHostStanceEnumEnumMap = {
  AcademicCompetitionCreateHostStanceEnum.proposition: 'proposition',
  AcademicCompetitionCreateHostStanceEnum.opposition: 'opposition',
  AcademicCompetitionCreateHostStanceEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
