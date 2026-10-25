// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'intervention_detail.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

InterventionDetail _$InterventionDetailFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'InterventionDetail',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'learner_id',
            'session_year_id',
            'grade_level_id',
            'owner_authority',
            'owner_id',
            'started_on',
            'review_on',
            'state',
            'revision',
            'action',
            'outcome',
          ],
        );
        final val = InterventionDetail(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          gradeLevelId: $checkedConvert(
            'grade_level_id',
            (v) => (v as num).toInt(),
          ),
          ownerAuthority: $checkedConvert(
            'owner_authority',
            (v) => $enumDecode(
              _$InterventionDetailOwnerAuthorityEnumEnumMap,
              v,
              unknownValue:
                  InterventionDetailOwnerAuthorityEnum.unknownDefaultOpenApi,
            ),
          ),
          ownerId: $checkedConvert('owner_id', (v) => (v as num).toInt()),
          startedOn: $checkedConvert('started_on', (v) => v as String),
          reviewOn: $checkedConvert('review_on', (v) => v as String),
          state: $checkedConvert(
            'state',
            (v) => $enumDecode(
              _$InterventionDetailStateEnumEnumMap,
              v,
              unknownValue: InterventionDetailStateEnum.unknownDefaultOpenApi,
            ),
          ),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          action: $checkedConvert('action', (v) => v as String),
          outcome: $checkedConvert('outcome', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'sessionYearId': 'session_year_id',
        'gradeLevelId': 'grade_level_id',
        'ownerAuthority': 'owner_authority',
        'ownerId': 'owner_id',
        'startedOn': 'started_on',
        'reviewOn': 'review_on',
      },
    );

Map<String, dynamic> _$InterventionDetailToJson(
  InterventionDetail instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'session_year_id': instance.sessionYearId,
  'grade_level_id': instance.gradeLevelId,
  'owner_authority':
      _$InterventionDetailOwnerAuthorityEnumEnumMap[instance.ownerAuthority]!,
  'owner_id': instance.ownerId,
  'started_on': instance.startedOn,
  'review_on': instance.reviewOn,
  'state': _$InterventionDetailStateEnumEnumMap[instance.state]!,
  'revision': instance.revision,
  'action': instance.action,
  'outcome': instance.outcome,
};

const _$InterventionDetailOwnerAuthorityEnumEnumMap = {
  InterventionDetailOwnerAuthorityEnum.professional: 'professional',
  InterventionDetailOwnerAuthorityEnum.academic: 'academic',
  InterventionDetailOwnerAuthorityEnum.leadership: 'leadership',
  InterventionDetailOwnerAuthorityEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$InterventionDetailStateEnumEnumMap = {
  InterventionDetailStateEnum.active: 'active',
  InterventionDetailStateEnum.completed: 'completed',
  InterventionDetailStateEnum.cancelled: 'cancelled',
  InterventionDetailStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
