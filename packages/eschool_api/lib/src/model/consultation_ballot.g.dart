// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_ballot.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationBallot _$ConsultationBallotFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ConsultationBallot',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'revision',
            'status',
            'type',
            'title',
            'instructions',
            'session_year_id',
            'anonymous',
            'results_visibility',
            'opens_at',
            'closes_at',
            'ended_at',
            'writable',
            'open',
            'participated',
            'questions',
          ],
        );
        final val = ConsultationBallot(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$ConsultationBallotStatusEnumEnumMap,
              v,
              unknownValue: ConsultationBallotStatusEnum.unknownDefaultOpenApi,
            ),
          ),
          type: $checkedConvert(
            'type',
            (v) => $enumDecode(
              _$ConsultationBallotTypeEnumEnumMap,
              v,
              unknownValue: ConsultationBallotTypeEnum.unknownDefaultOpenApi,
            ),
          ),
          title: $checkedConvert('title', (v) => v as String),
          instructions: $checkedConvert('instructions', (v) => v as String),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          anonymous: $checkedConvert('anonymous', (v) => v as bool),
          resultsVisibility: $checkedConvert(
            'results_visibility',
            (v) => $enumDecode(
              _$ConsultationBallotResultsVisibilityEnumEnumMap,
              v,
              unknownValue:
                  ConsultationBallotResultsVisibilityEnum.unknownDefaultOpenApi,
            ),
          ),
          opensAt: $checkedConvert(
            'opens_at',
            (v) => DateTime.parse(v as String),
          ),
          closesAt: $checkedConvert(
            'closes_at',
            (v) => DateTime.parse(v as String),
          ),
          endedAt: $checkedConvert(
            'ended_at',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
          writable: $checkedConvert('writable', (v) => v as bool),
          open: $checkedConvert('open', (v) => v as bool),
          participated: $checkedConvert('participated', (v) => v as bool),
          questions: $checkedConvert(
            'questions',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      ConsultationQuestion.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'sessionYearId': 'session_year_id',
        'resultsVisibility': 'results_visibility',
        'opensAt': 'opens_at',
        'closesAt': 'closes_at',
        'endedAt': 'ended_at',
      },
    );

Map<String, dynamic> _$ConsultationBallotToJson(ConsultationBallot instance) =>
    <String, dynamic>{
      'id': instance.id,
      'revision': instance.revision,
      'status': _$ConsultationBallotStatusEnumEnumMap[instance.status]!,
      'type': _$ConsultationBallotTypeEnumEnumMap[instance.type]!,
      'title': instance.title,
      'instructions': instance.instructions,
      'session_year_id': instance.sessionYearId,
      'anonymous': instance.anonymous,
      'results_visibility':
          _$ConsultationBallotResultsVisibilityEnumEnumMap[instance
              .resultsVisibility]!,
      'opens_at': instance.opensAt.toIso8601String(),
      'closes_at': instance.closesAt.toIso8601String(),
      'ended_at': instance.endedAt?.toIso8601String(),
      'writable': instance.writable,
      'open': instance.open,
      'participated': instance.participated,
      'questions': instance.questions.map((e) => e.toJson()).toList(),
    };

const _$ConsultationBallotStatusEnumEnumMap = {
  ConsultationBallotStatusEnum.draft: 'draft',
  ConsultationBallotStatusEnum.scheduled: 'scheduled',
  ConsultationBallotStatusEnum.open: 'open',
  ConsultationBallotStatusEnum.closed: 'closed',
  ConsultationBallotStatusEnum.cancelled: 'cancelled',
  ConsultationBallotStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$ConsultationBallotTypeEnumEnumMap = {
  ConsultationBallotTypeEnum.poll: 'poll',
  ConsultationBallotTypeEnum.survey: 'survey',
  ConsultationBallotTypeEnum.election: 'election',
  ConsultationBallotTypeEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$ConsultationBallotResultsVisibilityEnumEnumMap = {
  ConsultationBallotResultsVisibilityEnum.afterClose: 'after_close',
  ConsultationBallotResultsVisibilityEnum.publicLive: 'public_live',
  ConsultationBallotResultsVisibilityEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
