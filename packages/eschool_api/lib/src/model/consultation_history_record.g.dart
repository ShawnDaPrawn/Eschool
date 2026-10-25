// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_history_record.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationHistoryRecord _$ConsultationHistoryRecordFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ConsultationHistoryRecord',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'action',
        'revision',
        'actor_id',
        'actor_name',
        'created_at',
        'snapshot',
      ],
    );
    final val = ConsultationHistoryRecord(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      action: $checkedConvert('action', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      actorId: $checkedConvert('actor_id', (v) => (v as num?)?.toInt()),
      actorName: $checkedConvert('actor_name', (v) => v as String?),
      createdAt: $checkedConvert(
        'created_at',
        (v) => DateTime.parse(v as String),
      ),
      snapshot: $checkedConvert(
        'snapshot',
        (v) => ConsultationConfigurationSnapshot.fromJson(
          v as Map<String, dynamic>,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'actorId': 'actor_id',
    'actorName': 'actor_name',
    'createdAt': 'created_at',
  },
);

Map<String, dynamic> _$ConsultationHistoryRecordToJson(
  ConsultationHistoryRecord instance,
) => <String, dynamic>{
  'id': instance.id,
  'action': instance.action,
  'revision': instance.revision,
  'actor_id': instance.actorId,
  'actor_name': instance.actorName,
  'created_at': instance.createdAt.toIso8601String(),
  'snapshot': instance.snapshot.toJson(),
};
