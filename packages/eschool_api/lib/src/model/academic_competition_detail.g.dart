// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_competition_detail.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicCompetitionDetail _$AcademicCompetitionDetailFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicCompetitionDetail',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'kind',
        'entry',
        'motion',
        'format',
        'rounds',
        'host_stance',
        'my_stance',
        'their_winner',
        'adjudicators',
        'result',
        'reviews',
        'awards',
        'reasoning_feedback',
        'can_edit_details',
        'can_adjudicate',
        'can_submit',
        'can_award',
      ],
    );
    final val = AcademicCompetitionDetail(
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$AcademicCompetitionDetailKindEnumEnumMap,
          v,
          unknownValue: AcademicCompetitionDetailKindEnum.unknownDefaultOpenApi,
        ),
      ),
      entry: $checkedConvert(
        'entry',
        (v) => $enumDecode(
          _$AcademicCompetitionDetailEntryEnumEnumMap,
          v,
          unknownValue:
              AcademicCompetitionDetailEntryEnum.unknownDefaultOpenApi,
        ),
      ),
      motion: $checkedConvert('motion', (v) => v as String),
      format: $checkedConvert('format', (v) => v as String?),
      rounds: $checkedConvert('rounds', (v) => (v as num?)?.toInt()),
      hostStance: $checkedConvert(
        'host_stance',
        (v) => $enumDecodeNullable(
          _$AcademicCompetitionDetailHostStanceEnumEnumMap,
          v,
          unknownValue:
              AcademicCompetitionDetailHostStanceEnum.unknownDefaultOpenApi,
        ),
      ),
      myStance: $checkedConvert(
        'my_stance',
        (v) => $enumDecodeNullable(
          _$AcademicCompetitionDetailMyStanceEnumEnumMap,
          v,
          unknownValue:
              AcademicCompetitionDetailMyStanceEnum.unknownDefaultOpenApi,
        ),
      ),
      theirWinner: $checkedConvert(
        'their_winner',
        (v) => $enumDecodeNullable(
          _$AcademicCompetitionDetailTheirWinnerEnumEnumMap,
          v,
          unknownValue:
              AcademicCompetitionDetailTheirWinnerEnum.unknownDefaultOpenApi,
        ),
      ),
      adjudicators: $checkedConvert(
        'adjudicators',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicCompetitionDetailAdjudicatorsInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      result: $checkedConvert(
        'result',
        (v) => v == null
            ? null
            : AcademicResult.fromJson(v as Map<String, dynamic>),
      ),
      reviews: $checkedConvert(
        'reviews',
        (v) => (v as List<dynamic>)
            .map((e) => ReasoningReview.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      awards: $checkedConvert(
        'awards',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicCompetitionDetailAwardsInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      reasoningFeedback: $checkedConvert(
        'reasoning_feedback',
        (v) => v as bool,
      ),
      canEditDetails: $checkedConvert('can_edit_details', (v) => v as bool),
      canAdjudicate: $checkedConvert('can_adjudicate', (v) => v as bool),
      canSubmit: $checkedConvert('can_submit', (v) => v as bool),
      canAward: $checkedConvert('can_award', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'hostStance': 'host_stance',
    'myStance': 'my_stance',
    'theirWinner': 'their_winner',
    'reasoningFeedback': 'reasoning_feedback',
    'canEditDetails': 'can_edit_details',
    'canAdjudicate': 'can_adjudicate',
    'canSubmit': 'can_submit',
    'canAward': 'can_award',
  },
);

Map<String, dynamic> _$AcademicCompetitionDetailToJson(
  AcademicCompetitionDetail instance,
) => <String, dynamic>{
  'kind': _$AcademicCompetitionDetailKindEnumEnumMap[instance.kind]!,
  'entry': _$AcademicCompetitionDetailEntryEnumEnumMap[instance.entry]!,
  'motion': instance.motion,
  'format': instance.format,
  'rounds': instance.rounds,
  'host_stance':
      _$AcademicCompetitionDetailHostStanceEnumEnumMap[instance.hostStance],
  'my_stance':
      _$AcademicCompetitionDetailMyStanceEnumEnumMap[instance.myStance],
  'their_winner':
      _$AcademicCompetitionDetailTheirWinnerEnumEnumMap[instance.theirWinner],
  'adjudicators': instance.adjudicators.map((e) => e.toJson()).toList(),
  'result': instance.result?.toJson(),
  'reviews': instance.reviews.map((e) => e.toJson()).toList(),
  'awards': instance.awards.map((e) => e.toJson()).toList(),
  'reasoning_feedback': instance.reasoningFeedback,
  'can_edit_details': instance.canEditDetails,
  'can_adjudicate': instance.canAdjudicate,
  'can_submit': instance.canSubmit,
  'can_award': instance.canAward,
};

const _$AcademicCompetitionDetailKindEnumEnumMap = {
  AcademicCompetitionDetailKindEnum.debate: 'debate',
  AcademicCompetitionDetailKindEnum.quiz: 'quiz',
  AcademicCompetitionDetailKindEnum.speech: 'speech',
  AcademicCompetitionDetailKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$AcademicCompetitionDetailEntryEnumEnumMap = {
  AcademicCompetitionDetailEntryEnum.team: 'team',
  AcademicCompetitionDetailEntryEnum.individual: 'individual',
  AcademicCompetitionDetailEntryEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$AcademicCompetitionDetailHostStanceEnumEnumMap = {
  AcademicCompetitionDetailHostStanceEnum.proposition: 'proposition',
  AcademicCompetitionDetailHostStanceEnum.opposition: 'opposition',
  AcademicCompetitionDetailHostStanceEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$AcademicCompetitionDetailMyStanceEnumEnumMap = {
  AcademicCompetitionDetailMyStanceEnum.proposition: 'proposition',
  AcademicCompetitionDetailMyStanceEnum.opposition: 'opposition',
  AcademicCompetitionDetailMyStanceEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$AcademicCompetitionDetailTheirWinnerEnumEnumMap = {
  AcademicCompetitionDetailTheirWinnerEnum.host: 'host',
  AcademicCompetitionDetailTheirWinnerEnum.guest: 'guest',
  AcademicCompetitionDetailTheirWinnerEnum.draw: 'draw',
  AcademicCompetitionDetailTheirWinnerEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
