// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_review_v2.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeReviewV2 _$PracticeReviewV2FromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PracticeReviewV2',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'entry_id',
        'state',
        'correct',
        'achieved_units',
        'maximum_units',
        'parts',
        'marked_how',
        'schedule',
        'reviewed_on',
        'solution',
      ],
    );
    final val = PracticeReviewV2(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      entryId: $checkedConvert('entry_id', (v) => (v as num).toInt()),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$PracticeReviewV2StateEnumEnumMap,
          v,
          unknownValue: PracticeReviewV2StateEnum.unknownDefaultOpenApi,
        ),
      ),
      correct: $checkedConvert('correct', (v) => v as bool?),
      achievedUnits: $checkedConvert(
        'achieved_units',
        (v) => (v as num?)?.toInt(),
      ),
      maximumUnits: $checkedConvert('maximum_units', (v) => (v as num).toInt()),
      parts: $checkedConvert(
        'parts',
        (v) =>
            (v as Map<String, dynamic>?)?.map((k, e) => MapEntry(k, e as bool)),
      ),
      markedHow: $checkedConvert(
        'marked_how',
        (v) => $enumDecode(
          _$PracticeReviewV2MarkedHowEnumEnumMap,
          v,
          unknownValue: PracticeReviewV2MarkedHowEnum.unknownDefaultOpenApi,
        ),
      ),
      schedule: $checkedConvert(
        'schedule',
        (v) => v == null
            ? null
            : PracticeSchedule.fromJson(v as Map<String, dynamic>),
      ),
      reviewedOn: $checkedConvert('reviewed_on', (v) => v as String),
      solution: $checkedConvert(
        'solution',
        (v) => PracticeSolution.fromJson(v as Map<String, dynamic>),
      ),
      streakDays: $checkedConvert('streak_days', (v) => (v as num?)?.toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'entryId': 'entry_id',
    'achievedUnits': 'achieved_units',
    'maximumUnits': 'maximum_units',
    'markedHow': 'marked_how',
    'reviewedOn': 'reviewed_on',
    'streakDays': 'streak_days',
  },
);

Map<String, dynamic> _$PracticeReviewV2ToJson(PracticeReviewV2 instance) =>
    <String, dynamic>{
      'id': instance.id,
      'entry_id': instance.entryId,
      'state': _$PracticeReviewV2StateEnumEnumMap[instance.state]!,
      'correct': instance.correct,
      'achieved_units': instance.achievedUnits,
      'maximum_units': instance.maximumUnits,
      'parts': instance.parts,
      'marked_how': _$PracticeReviewV2MarkedHowEnumEnumMap[instance.markedHow]!,
      'schedule': instance.schedule?.toJson(),
      'reviewed_on': instance.reviewedOn,
      'solution': instance.solution.toJson(),
      'streak_days': ?instance.streakDays,
    };

const _$PracticeReviewV2StateEnumEnumMap = {
  PracticeReviewV2StateEnum.marked: 'marked',
  PracticeReviewV2StateEnum.awaitingTeacher: 'awaiting_teacher',
  PracticeReviewV2StateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$PracticeReviewV2MarkedHowEnumEnumMap = {
  PracticeReviewV2MarkedHowEnum.automatic: 'automatic',
  PracticeReviewV2MarkedHowEnum.teacher: 'teacher',
  PracticeReviewV2MarkedHowEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
