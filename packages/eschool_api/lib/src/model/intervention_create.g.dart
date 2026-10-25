// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'intervention_create.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

InterventionCreate _$InterventionCreateFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'InterventionCreate',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'session_year_id',
            'owner_id',
            'action',
            'started_on',
            'review_on',
          ],
        );
        final val = InterventionCreate(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          ownerId: $checkedConvert('owner_id', (v) => (v as num).toInt()),
          action: $checkedConvert('action', (v) => v as String),
          startedOn: $checkedConvert('started_on', (v) => v as String),
          reviewOn: $checkedConvert('review_on', (v) => v as String),
          ownerAuthority: $checkedConvert(
            'owner_authority',
            (v) => $enumDecodeNullable(
              _$InterventionCreateOwnerAuthorityEnumEnumMap,
              v,
              unknownValue:
                  InterventionCreateOwnerAuthorityEnum.unknownDefaultOpenApi,
            ),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'sessionYearId': 'session_year_id',
        'ownerId': 'owner_id',
        'startedOn': 'started_on',
        'reviewOn': 'review_on',
        'ownerAuthority': 'owner_authority',
      },
    );

Map<String, dynamic> _$InterventionCreateToJson(
  InterventionCreate instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'session_year_id': instance.sessionYearId,
  'owner_id': instance.ownerId,
  'action': instance.action,
  'started_on': instance.startedOn,
  'review_on': instance.reviewOn,
  'owner_authority':
      ?_$InterventionCreateOwnerAuthorityEnumEnumMap[instance.ownerAuthority],
};

const _$InterventionCreateOwnerAuthorityEnumEnumMap = {
  InterventionCreateOwnerAuthorityEnum.professional: 'professional',
  InterventionCreateOwnerAuthorityEnum.academic: 'academic',
  InterventionCreateOwnerAuthorityEnum.leadership: 'leadership',
  InterventionCreateOwnerAuthorityEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
