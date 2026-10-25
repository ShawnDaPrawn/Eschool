// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_family_record.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineFamilyRecord _$DisciplineFamilyRecordFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineFamilyRecord',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'occurred_on',
        'description',
        'infraction',
        'level',
        'sanction',
        'status',
        'family_reason',
        'revision',
        'acknowledged',
        'detentions',
      ],
    );
    final val = DisciplineFamilyRecord(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      occurredOn: $checkedConvert('occurred_on', (v) => v as String),
      description: $checkedConvert('description', (v) => v as String),
      infraction: $checkedConvert('infraction', (v) => v as String),
      level: $checkedConvert('level', (v) => v as String),
      sanction: $checkedConvert('sanction', (v) => v as String?),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$DisciplineFamilyRecordStatusEnumEnumMap,
          v,
          unknownValue: DisciplineFamilyRecordStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      familyReason: $checkedConvert('family_reason', (v) => v as String?),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      acknowledged: $checkedConvert('acknowledged', (v) => v as bool),
      detentions: $checkedConvert(
        'detentions',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  DisciplineFamilyDetention.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'occurredOn': 'occurred_on',
    'familyReason': 'family_reason',
  },
);

Map<String, dynamic> _$DisciplineFamilyRecordToJson(
  DisciplineFamilyRecord instance,
) => <String, dynamic>{
  'id': instance.id,
  'occurred_on': instance.occurredOn,
  'description': instance.description,
  'infraction': instance.infraction,
  'level': instance.level,
  'sanction': instance.sanction,
  'status': _$DisciplineFamilyRecordStatusEnumEnumMap[instance.status]!,
  'family_reason': instance.familyReason,
  'revision': instance.revision,
  'acknowledged': instance.acknowledged,
  'detentions': instance.detentions.map((e) => e.toJson()).toList(),
};

const _$DisciplineFamilyRecordStatusEnumEnumMap = {
  DisciplineFamilyRecordStatusEnum.pendingReview: 'pending_review',
  DisciplineFamilyRecordStatusEnum.confirmed: 'confirmed',
  DisciplineFamilyRecordStatusEnum.hearing: 'hearing',
  DisciplineFamilyRecordStatusEnum.overturned: 'overturned',
  DisciplineFamilyRecordStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
