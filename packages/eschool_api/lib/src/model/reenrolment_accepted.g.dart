// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_accepted.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentAccepted _$ReenrolmentAcceptedFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ReenrolmentAccepted',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'contract_id',
            'learner_id',
            'version',
            'accepted_at',
            'status',
          ],
        );
        final val = ReenrolmentAccepted(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          contractId: $checkedConvert('contract_id', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          version: $checkedConvert('version', (v) => (v as num).toInt()),
          acceptedAt: $checkedConvert(
            'accepted_at',
            (v) => DateTime.parse(v as String),
          ),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$ReenrolmentAcceptedStatusEnumEnumMap,
              v,
              unknownValue: ReenrolmentAcceptedStatusEnum.unknownDefaultOpenApi,
            ),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'contractId': 'contract_id',
        'learnerId': 'learner_id',
        'acceptedAt': 'accepted_at',
      },
    );

Map<String, dynamic> _$ReenrolmentAcceptedToJson(
  ReenrolmentAccepted instance,
) => <String, dynamic>{
  'id': instance.id,
  'contract_id': instance.contractId,
  'learner_id': instance.learnerId,
  'version': instance.version,
  'accepted_at': instance.acceptedAt.toIso8601String(),
  'status': _$ReenrolmentAcceptedStatusEnumEnumMap[instance.status]!,
};

const _$ReenrolmentAcceptedStatusEnumEnumMap = {
  ReenrolmentAcceptedStatusEnum.confirmed: 'confirmed',
  ReenrolmentAcceptedStatusEnum.awaiting: 'awaiting',
  ReenrolmentAcceptedStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
