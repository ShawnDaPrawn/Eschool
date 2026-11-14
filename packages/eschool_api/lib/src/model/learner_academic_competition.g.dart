// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_academic_competition.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerAcademicCompetition _$LearnerAcademicCompetitionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'LearnerAcademicCompetition',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'kind',
        'discipline',
        'motion',
        'on_date',
        'starts_at',
        'venue',
        'status',
        'competition',
        'team_label',
        'opponent',
        'stance',
        'result',
        'feedback',
        'awards',
      ],
    );
    final val = LearnerAcademicCompetition(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$LearnerAcademicCompetitionKindEnumEnumMap,
          v,
          unknownValue:
              LearnerAcademicCompetitionKindEnum.unknownDefaultOpenApi,
        ),
      ),
      discipline: $checkedConvert('discipline', (v) => v as String),
      motion: $checkedConvert('motion', (v) => v as String),
      onDate: $checkedConvert('on_date', (v) => v as String),
      startsAt: $checkedConvert('starts_at', (v) => v as String?),
      venue: $checkedConvert('venue', (v) => v as String),
      status: $checkedConvert('status', (v) => v as String),
      competition: $checkedConvert('competition', (v) => v as String?),
      teamLabel: $checkedConvert('team_label', (v) => v as String),
      opponent: $checkedConvert('opponent', (v) => v as String),
      stance: $checkedConvert(
        'stance',
        (v) => $enumDecodeNullable(
          _$LearnerAcademicCompetitionStanceEnumEnumMap,
          v,
          unknownValue:
              LearnerAcademicCompetitionStanceEnum.unknownDefaultOpenApi,
        ),
      ),
      result: $checkedConvert(
        'result',
        (v) => LearnerAcademicCompetitionResult.fromJson(
          v as Map<String, dynamic>,
        ),
      ),
      feedback: $checkedConvert(
        'feedback',
        (v) => (v as List<dynamic>)
            .map(
              (e) => LearnerAcademicCompetitionFeedbackInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      awards: $checkedConvert(
        'awards',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'onDate': 'on_date',
    'startsAt': 'starts_at',
    'teamLabel': 'team_label',
  },
);

Map<String, dynamic> _$LearnerAcademicCompetitionToJson(
  LearnerAcademicCompetition instance,
) => <String, dynamic>{
  'id': instance.id,
  'kind': _$LearnerAcademicCompetitionKindEnumEnumMap[instance.kind]!,
  'discipline': instance.discipline,
  'motion': instance.motion,
  'on_date': instance.onDate,
  'starts_at': instance.startsAt,
  'venue': instance.venue,
  'status': instance.status,
  'competition': instance.competition,
  'team_label': instance.teamLabel,
  'opponent': instance.opponent,
  'stance': _$LearnerAcademicCompetitionStanceEnumEnumMap[instance.stance],
  'result': instance.result.toJson(),
  'feedback': instance.feedback.map((e) => e.toJson()).toList(),
  'awards': instance.awards,
};

const _$LearnerAcademicCompetitionKindEnumEnumMap = {
  LearnerAcademicCompetitionKindEnum.debate: 'debate',
  LearnerAcademicCompetitionKindEnum.quiz: 'quiz',
  LearnerAcademicCompetitionKindEnum.speech: 'speech',
  LearnerAcademicCompetitionKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$LearnerAcademicCompetitionStanceEnumEnumMap = {
  LearnerAcademicCompetitionStanceEnum.proposition: 'proposition',
  LearnerAcademicCompetitionStanceEnum.opposition: 'opposition',
  LearnerAcademicCompetitionStanceEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
