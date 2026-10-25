// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'intervention_metadata.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

InterventionMetadata _$InterventionMetadataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'InterventionMetadata',
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
      ],
    );
    final val = InterventionMetadata(
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
          _$InterventionMetadataOwnerAuthorityEnumEnumMap,
          v,
          unknownValue:
              InterventionMetadataOwnerAuthorityEnum.unknownDefaultOpenApi,
        ),
      ),
      ownerId: $checkedConvert('owner_id', (v) => (v as num).toInt()),
      startedOn: $checkedConvert('started_on', (v) => v as String),
      reviewOn: $checkedConvert('review_on', (v) => v as String),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$InterventionMetadataStateEnumEnumMap,
          v,
          unknownValue: InterventionMetadataStateEnum.unknownDefaultOpenApi,
        ),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
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

Map<String, dynamic> _$InterventionMetadataToJson(
  InterventionMetadata instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'session_year_id': instance.sessionYearId,
  'grade_level_id': instance.gradeLevelId,
  'owner_authority':
      _$InterventionMetadataOwnerAuthorityEnumEnumMap[instance.ownerAuthority]!,
  'owner_id': instance.ownerId,
  'started_on': instance.startedOn,
  'review_on': instance.reviewOn,
  'state': _$InterventionMetadataStateEnumEnumMap[instance.state]!,
  'revision': instance.revision,
};

const _$InterventionMetadataOwnerAuthorityEnumEnumMap = {
  InterventionMetadataOwnerAuthorityEnum.professional: 'professional',
  InterventionMetadataOwnerAuthorityEnum.academic: 'academic',
  InterventionMetadataOwnerAuthorityEnum.leadership: 'leadership',
  InterventionMetadataOwnerAuthorityEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$InterventionMetadataStateEnumEnumMap = {
  InterventionMetadataStateEnum.active: 'active',
  InterventionMetadataStateEnum.completed: 'completed',
  InterventionMetadataStateEnum.cancelled: 'cancelled',
  InterventionMetadataStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
