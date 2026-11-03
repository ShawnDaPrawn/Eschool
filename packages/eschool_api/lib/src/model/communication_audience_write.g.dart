// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_audience_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationAudienceWrite _$CommunicationAudienceWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommunicationAudienceWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'session_year_id',
        'name',
        'active',
        'user_ids',
      ],
    );
    final val = CommunicationAudienceWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      name: $checkedConvert('name', (v) => v as String),
      active: $checkedConvert('active', (v) => v as bool),
      userIds: $checkedConvert(
        'user_ids',
        (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toSet(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'sessionYearId': 'session_year_id',
    'userIds': 'user_ids',
  },
);

Map<String, dynamic> _$CommunicationAudienceWriteToJson(
  CommunicationAudienceWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'session_year_id': instance.sessionYearId,
  'name': instance.name,
  'active': instance.active,
  'user_ids': instance.userIds.toList(),
};
